<?php


include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/FlexAPI.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/SqlConnection.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/database/FilterParser.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/accesscontrol/ACL/ACLGuard.php';
include_once __DIR__ . '/t30.php';


FlexAPI::define(function() {

        FlexAPI::set('environment', 'local');
        // FlexAPI::set('environment', 'one_dot_com');
        FlexAPI::set('basePath', '/adfc/adfc-t30-paten-backend');
        FlexAPI::set('apiPath', '/api');
        FlexAPI::set('appRoot', './..');
        FlexApi::set('resetSecret', 'reset!');

        $environment = FlexAPI::get('environment');
        $connectionConfig = (array) json_decode(file_get_contents(__DIR__."/$environment.env.json"), true);
        $databaseConnection = new SqlConnection($connectionConfig['data']);
        $guard = new ACLGuard(new SqlConnection($connectionConfig['guard']));
        
        $modelFactory = new T30Factory($databaseConnection, $guard);
        $dataModel = $modelFactory->createDataModel();

        return [
            'dataModel' => $dataModel,
            'guard' => $guard
        ];
});

FlexApi::onSetup(function() {
    FlexAPI::guard()->registerUser('admin', 'password');
    FlexAPI::guard()->assignRole('admin','admin');
    
    FlexAPI::guard()->registerUser('guest', '');
    FlexAPI::guard()->assignRole('guest','guest');

    FlexAPI::guard()->allowCRUD('guest', 'cRud', 'institution');

    FlexAPI::guard()->allowCRUD('registered', 'CRUd', 'userdata');
    FlexAPI::guard()->allowCRUD('registered', 'CRUD', 'institution');
    FlexAPI::guard()->allowCRUD('registered', 'CRUD', 'patenschaft');

    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'userdata'   , false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'institution', false);
    FlexAPI::guard()->allowCRUD('admin', 'CRUD', 'patenschaft', false);
});


FlexAPI::onEvent('before-user-registration', function($event) {
    if (!preg_match('/^[\w-\.]+@[-\w]+\.[\w]+$/', $event['username'])) {
        throw(new Exception('User name must be a valid email address.', 400));
    }
});

FlexAPI::onEvent('after-user-registration', function($event) {
    FlexAPI::guard()->assignRole('guest', $event['username']);
    FlexAPI::guard()->assignRole('registered', $event['username']);
});

FlexAPI::onEvent('before-user-unregistration', function($event) {
    if ($event['username'] === 'guest') {
        throw(new Exception('User cannot not be unregistered.', 400));
    }
});

FlexAPI::onEvent('after-user-unregistration', function($event) {
    $userdata = FlexAPI::dataModel()->read('userdata', ['flatten' => 'singleResult']);
    $connection = FlexAPI::dataModel()->getConnection();
    $filterParser = new FilterParser();
    $filter = $filterParser->parseFilter(['id' => $userdata['id']]);
    $connection->deleteFromDatabase(FlexAPI::dataModel()->getEntity('userdata'), $filter);
});

