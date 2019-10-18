<?php

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/adfc-hamburg/flexapi/requestutils/jwt.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");
header("Access-Control-Allow-Methods: GET, PUT, POST, PATCH, DELETE, OPTIONS, HEAD");

echo FlexAPI::crud();
