<?php
use PHPMailer\PHPMailer\Exception;

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/ADFC-Hamburg/flexapi/requestutils/jwt.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");


try {
    $jwt = getJWT();
    if ($jwt) {
        FlexAPI::guard()->login($jwt);
    }

    if (FlexAPI::guard()->getUsername() === 'admin') {
        $history = FlexAPI::get('entityMonitor')->history($_GET['entity'],$_GET['id']);
    } else {
        throw(new Exception('Not allowed.', 403));
    }
    $response = [
        'code' => 200,
        'message' => 'History reconstructed.',
        'history' => $history
    ];
} catch (Exception $exc) {
    $response = [
        'code' => $exc->getCode(),
        'message' => 'Could not reconstruct history: '.$exc->getMessage()
    ];
}

http_response_code($response['code']);
echo json_encode($response);
