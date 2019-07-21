<?php

include_once __DIR__ . '/../api.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");

FlexAPI::guard()->login([
    'username' => 'guest',
    'password' => ''
]);

$history = FlexAPI::get('entityMonitor')->history($_GET['entity'],$_GET['id']);

echo json_encode($history);
