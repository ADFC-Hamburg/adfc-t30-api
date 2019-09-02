<?php


include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/FlexAPI.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/SqlConnection.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/FilterParser.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/accesscontrol/ACL/ACLGuard.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/user-verification/EmailVerificationService.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/services/user-verification/MockVerificationService.php';
include_once __DIR__ . '/t30.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/EntityMonitor.php';

FlexAPI::onEvent('api-defined', function($event) {
    $entityMonitor = new EntityMonitor(FlexAPI::dataModel(), ['institution']);
    FlexAPI::set('entityMonitor', $entityMonitor);
});

FlexAPI::define(function() {
        FlexAPI::config();

        if (FlexAPI::$env === 'prod') {
            $verificationService = new EmailVerificationService(function($address, $url) {
                return sprintf(
                    'Hallo,<br><br>'.
                    'klicke <a href="%s">hier</a>, um Deinen Account zu aktivieren.<br><br>',
                    $url
                );
            });
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
        $institutions = (array) json_decode(file_get_contents(__DIR__."/test/data/institutions.json"), true);
        $new_institutions= [];
        foreach ($institutions as $value) {
          $value['city']='Hamburg';
          $value['street_house_no']=$value['street'].' '.$value['number'];
          $value['position']=[$value['lon'],$value['lat']];
          array_push($new_institutions, $value);
          // code...
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
        FlexAPI::superAccess()->insert('userdata', $userData);
        FlexAPI::guard()->publishResource($username, 'userdata', $username , 'RU');
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
    FlexAPI::superAccess()->insert('userdata', $userData);
    FlexAPI::guard()->publishResource($username, 'userdata', $username , 'RU');
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
