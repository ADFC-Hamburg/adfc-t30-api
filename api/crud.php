<?php

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/bensteffen/flexapi/requestutils/jwt.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    echo $method;
    die;
}

$jwt = getJWT();
if (!$jwt) {
    FlexAPI::guard()->login([
        'username' => 'guest',
        'password' => ''
    ]);
} else {
    FlexAPI::guard()->login($jwt);
}
// FlexAPI::guard()->login(['username' => 'floderflo', 'password' => '123']);
// FlexAPI::guard()->login(['username' => 'bensteffen', 'password' => 'abc']);
// FlexAPI::guard()->login(['username' => 'admin', 'password' => 'pw']);

include_once __DIR__ . '/../vendor/bensteffen/flexapi/crud.php';

?>