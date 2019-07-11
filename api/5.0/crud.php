<?php

include_once __DIR__ . '/../../apiv5_0.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");

echo FlexAPI::crud();

