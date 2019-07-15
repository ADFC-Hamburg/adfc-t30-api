<?php

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/bensteffen/flexapi/requestutils/jwt.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");

echo FlexAPI::crud();

