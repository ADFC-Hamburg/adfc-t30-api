<?php


include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/FlexAPI.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/SqlConnection.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/FilterParser.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/accesscontrol/ACL/ACLGuard.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/mail/SmtpMailService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/token/RandomBytesTokenService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/token/AlphaNumericTokenService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/user-verification/EmailVerificationService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/user-verification/TokenVerificationService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/user-verification/MockVerificationService.php';
include_once __DIR__ . '/t30.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/EntityMonitor.php';

FlexAPI::onEvent('api-defined', function($event) {
    $notification = [
        'from' => 'monitor',
        'to' => FlexAPI::get('reportDataChangesTo'),
        'subject' => function($entityName) { return utf8_decode("T30-Paten: Änderung von '$entityName'"); },
        'body' => function($entityName, $entityId, $metaData, $fieldChanges) {
            $changes = array_map(function($change) {
                return "   * Attribut '".$change['fieldName']."': ".$change['oldValue']." => ".$change['newValue'];
            }, $fieldChanges);
            $at = $metaData['timeStamp'];
            return utf8_decode("".
                "<br><br>Hallo,".
                "<br><br>am ".date('d.m.Y', $at)." um ".date('H:i:s', $at).
                "<br><br>wurde(n) durch '".$metaData['user']."'".
                "<br><br>im Datensatz '$entityName' mit ID ".$entityId." folgendende Änderung(en) durchgeführt:".
                "<br><br><br>".implode('<br><br>', $changes).
                "<br><br>");
        }
    ];
    $entityMonitor = new EntityMonitor(FlexAPI::dataModel(), ['institution'], $notification);
    FlexAPI::set('entityMonitor', $entityMonitor);
});

FlexAPI::define(function() {
        FlexAPI::config();

        if (FlexAPI::$env === 'prod') {
            $mailConfig = FlexAPI::get('mailing');
            // $tokenService = new RandomBytesTokenService();
            $tokenService = new AlphaNumericTokenService(); // produziert besser zu merkende Tokens
            $verificationService = new TokenVerificationService(
                FlexAPI::get('userVerification'),
                function($token) { return "Hallo,<br>Ihr Aktivierungs-Code lautet: $token<br>"; },
                new SmtpMailService($mailConfig['smtp'], $mailConfig['from']['verification']),
                $tokenService
            );
        }
        if (FlexAPI::$env === 'dev' || FlexAPI::$env === 'test') {
            $verificationService = new MockVerificationService(); // for auto-testing
        }

        $dbCredentials = FlexAPI::get('databaseCredentials');
        $databaseConnection = new SqlConnection($dbCredentials['data']);
        $guard = new ACLGuard(new SqlConnection($dbCredentials['guard']), null, $verificationService);

        return [
            'factory' => new T30Factory(),
            'connection' => $databaseConnection,
            'guard' => $guard
        ];
});

FlexApi::onSetup(function($request) {
    FlexAPI::dataModel()->reset();
    FlexAPI::guard()->reset();
    FlexAPI::get('entityMonitor')->reset();

    FlexAPI::guard()->registerUser('admin', $request['adminPassword'], false);
    FlexAPI::guard()->assignRole('admin','admin');

    FlexAPI::guard()->registerUser('guest', '', false);
    FlexAPI::guard()->assignRole('guest','guest');

    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'street', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'institution', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'policedepartment', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'demandedstreetsection', false);

    FlexAPI::guard()->allowCRUD('registered', 'CRUd', 'userdata', true);
    FlexAPI::guard()->allowCRUD('registered', 'CRUd', 'institution', false);
    FlexAPI::guard()->allowCRUD('registered', 'CRUd', 'demandedstreetsection', false);

    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'street'               , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'userdata'             , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'institution'          , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'policedepartment'     , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'email'                , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'demandedstreetsection', false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'districthamburg'      , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'relationtoinstitution', false);

    FlexAPI::superAccess()->insert('districthamburg', include('./data/districtshamburg.php'));

    if (array_key_exists('fillInTestData', $request) && $request['fillInTestData']) {
        $institutions = (array) json_decode(file_get_contents(__DIR__."/test/data/institutions_reshaped.json"), true);
        if ($request['fillInTestData'] === true) {
            $size = count($institutions);
        } else {
            $size = $request['fillInTestData'];
        }
        $new_institutions= [];
        foreach ($institutions as $index => $value) {
            if ($index >= $size) {
                break;
            }
            array_push($new_institutions, $value);
        }
        FlexAPI::superAccess()->insert('institution', $new_institutions);
    }

    if (array_key_exists('registerTestUser', $request) && $request['registerTestUser']) {
        $username = 'max-muster@some-provider.de';
        $password = 'geheim';
        $userData = [
            'user' => $username,
            'firstName' => 'Max',
            'lastName' => 'Muster',
            'street_house_no' => 'Fakestreet 123',
            'city' => 'Hamburg',
            'zip' => 22666
        ];
        FlexAPI::guard()->registerUser($username, $password , false);
        $id = FlexAPI::superAccess()->insert('userdata', $userData);
        FlexAPI::guard()->publishResource($username, 'userdata', $id , 'RU');
        FlexAPI::guard()->assignRole('guest', $username);
        FlexAPI::guard()->assignRole('registered', $username);
    }
});

FlexAPI::onEvent('before-crud', function($event) {
    $jwt = getJWT();
    if (!$jwt) {
        FlexAPI::guard()->login([
            'username' => 'guest',
            'password' => ''
        ]);
    } else {
        FlexAPI::guard()->login($jwt);
    }
});

FlexAPI::onEvent('before-user-registration', function($event) {
    if (!filter_var($event['request']['username'], FILTER_VALIDATE_EMAIL)) {
        throw(new Exception('User name must be a valid email address.', 400));
    }
    if (!array_key_exists('userData', $event['request'])) {
        throw(new Exception('Missing user data.', 400));
    }
    $userData = (array) $event['request']['userData'];
    $mandatory = ['lastName', 'firstName'];
    foreach ($mandatory as $key) {
        if (!array_key_exists($key, $userData) && !$userData[$key]) {
            throw(new Exception('Bad user data field "'.$key.'".', 400));
        }
    }
});

FlexAPI::onEvent('after-user-registration', function($event) {
    $userData = (array) $event['request']['userData'];
    $username = $event['request']['username'];
    $userData['user'] = $username;
    $id = FlexAPI::superAccess()->insert('userdata', $userData);
    FlexAPI::guard()->publishResource($username, 'userdata', $id , 'RU');
});

FlexAPI::onEvent('after-user-verification', function($event) {
    FlexAPI::guard()->assignRole('guest', $event['response']['username']);
    FlexAPI::guard()->assignRole('registered', $event['response']['username']);
    // if (array_key_exists('forwardTo', $event['response'])) {
    //     FlexAPI::navigateTo($event['response']['forwardTo']);
    // }
});


FlexAPI::onEvent('before-user-unregistration', function($event) {
    if (in_array($event['username'], ['admin', 'guest'])) {
        throw(new Exception('User cannot not be unregistered.', 400));
    }
});

FlexAPI::onEvent('after-user-unregistration', function($event) {
    FlexAPI::superAccess()->delete('userdata', ['user' => $event['username']]);
});
