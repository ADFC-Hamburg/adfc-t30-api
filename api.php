<?php


include_once __DIR__ . '/vendor/bensteffen/flexapi/FlexAPI.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/database/SqlConnection.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/database/FilterParser.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/accesscontrol/ACL/ACLGuard.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/services/user-verification/EmailVerificationService.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/services/user-verification/MockVerificationService.php';
include_once __DIR__ . '/t30.php';


FlexAPI::define(function() {
        FlexAPI::setConfig('api');

        // $verificationService = new EmailVerificationService(function($address, $url) {
        //     return sprintf(
        //         'Hallo,<br><br>'.
        //         'klicke <a href="%s">hier</a>, um Deinen Account zu aktivieren.<br><br>',
        //         $url
        //     );
        // });
        $verificationService = new MockVerificationService();

        $dbCredentials = FlexAPI::get('databaseCredentials');
        $databaseConnection = new SqlConnection($dbCredentials['data']);
        $guard = new ACLGuard(new SqlConnection($dbCredentials['guard']), null, $verificationService);


        // $modelFactory = new T30Factory($databaseConnection, $guard);
        // $dataModel = $modelFactory->createDataModel();

        return [
            'factory' => new T30Factory(),
            'connection' => $databaseConnection,
            'guard' => $guard
        ];
});

FlexApi::onSetup(function($request) {
    FlexAPI::dataModel()->reset();
    FlexAPI::guard()->reset();

    FlexAPI::guard()->registerUser('admin', $request['adminPassword'], false);
    FlexAPI::guard()->assignRole('admin','admin');
    
    FlexAPI::guard()->registerUser('guest', '', false);
    FlexAPI::guard()->assignRole('guest','guest');

    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'institution');

    FlexAPI::guard()->allowCRUD('registered', 'cRud', 'userdata');
    FlexAPI::guard()->allowCRUD('registered', 'CRUD', 'institution');
    FlexAPI::guard()->allowCRUD('registered', 'CRUD', 'patenschaft');

    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'userdata'   , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'institution', false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'patenschaft', false);
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
    $mandatory = ['lastName', 'firstName', 'street', 'number', 'city', 'zip'];
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
    FlexAPI::guard()->publishResource($username, 'userdata', $username , 'R');
});

FlexAPI::onEvent('after-user-verification', function($event) {
    FlexAPI::guard()->assignRole('guest', $event['username']);
    FlexAPI::guard()->assignRole('registered', $event['username']);
});


FlexAPI::onEvent('before-user-unregistration', function($event) {
    if (in_array($event['username'], ['admin', 'guest'])) {
        throw(new Exception('User cannot not be unregistered.', 400));
    }
});

FlexAPI::onEvent('after-user-unregistration', function($event) {
    FlexAPI::superAccess()->delete('userdata', ['user' => $event['username']]);
});
