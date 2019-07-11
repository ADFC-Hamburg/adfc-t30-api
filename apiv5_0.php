<?php

include_once __DIR__ . '/vendor/bensteffen/flexapi/FlexAPI.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/database/SqlConnection.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/database/FilterParser.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/accesscontrol/ACL/ACLGuard.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/services/user-verification/EmailVerificationService.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/services/user-verification/MockVerificationService.php';
include_once __DIR__ . '/t30v5_0.php';
include_once __DIR__ . '/EntityMonitor.php';

FlexAPI::onEvent('api-defined', function($event) {
    $entityMonitor = new EntityMonitor(FlexAPI::dataModel(), ['einrichtung']);
    FlexAPI::set('entityMonitor', $entityMonitor);
});

FlexAPI::define(function() {
        FlexAPI::setConfig('apiv5_0');

        // $verificationService = new EmailVerificationService(function($address, $url) {
        //     return sprintf(
        //         'Hallo,<br><br>'.
        //         'klicke <a href="%s">hier</a>, um Deinen Account zu aktivieren.<br><br>',
        //         $url
        //     );
        // });
        $verificationService = new MockVerificationService(); // for auto-testing

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

    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'einrichtung', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'bezirkhamburg', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'einrichtungsart', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'einrichtungsquelle', false);
    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'zeitlichebeschraenkung', false);

    FlexAPI::guard()->allowCRUD('registered', 'CRUd', 'einrichtung', false);
    FlexAPI::guard()->allowCRUD('registered', 'cRUd', 'person');
    FlexAPI::guard()->allowCRUD('registered', 'CRUD', 'beziehungzureinrichtung');

    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'person'   , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'einrichtung', false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'beziehungzureinrichtung', false);

    if (array_key_exists('fillInTestData', $request) && $request['fillInTestData']) {
        $institutions = (array) json_decode(file_get_contents(__DIR__."/api/5.0/data/einrichtungen.json"), true);
        FlexAPI::superAccess()->insert('einrichtung', $institutions);
    }

    $index = (array) json_decode(file_get_contents(__DIR__."/api/5.0/data/bezirkehamburg.json"), true);
    FlexAPI::superAccess()->insert('bezirkhamburg', $index);

    $index = (array) json_decode(file_get_contents(__DIR__."/api/5.0/data/einrichtungsart.json"), true);
    FlexAPI::superAccess()->insert('einrichtungsart', $index);

    $index = (array) json_decode(file_get_contents(__DIR__."/api/5.0/data/einrichtungsquelle.json"), true);
    FlexAPI::superAccess()->insert('einrichtungsquelle', $index);

    $index = (array) json_decode(file_get_contents(__DIR__."/api/5.0/data/zeitlichebeschraenkung.json"), true);
    FlexAPI::superAccess()->insert('zeitlichebeschraenkung', $index);


    if (array_key_exists('registerTestUser', $request) && $request['registerTestUser']) {
        $username = 'max-muster@some-provider.de';
        $password = 'geheim';
        $userData = [
            'user' => $username,
            'vorname' => 'Max',
            'nachname' => 'Muster',
            'strasse' => 'Fakestreet 123a',
            'plz' => '22666',
            'ort' => 'Hamburg'
        ];
        FlexAPI::guard()->registerUser($username, $password , false);
        FlexAPI::superAccess()->insert('person', $userData);
        FlexAPI::guard()->publishResource($username, 'person', $username , 'RU');
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
    if (!preg_match('/^[\w-\.]+@[-\w]+\.[\w]+$/', $event['request']['username'])) {
        throw(new Exception('User name must be a valid email address.', 400));
    }
    if (!array_key_exists('userData', $event['request'])) {
        throw(new Exception('Missing user data.', 400));
    }
    $userData = (array) $event['request']['userData'];
    $mandatory = ['firstname', 'lastname', 'adresse'];
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
    FlexAPI::superAccess()->insert('person', $userData);
    FlexAPI::guard()->publishResource($username, 'person', $username , 'RU');
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
    FlexAPI::superAccess()->delete('person', ['user' => $event['username']]);
});
