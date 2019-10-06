<?php

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/ADFC-Hamburg/flexapi/requestutils/jwt.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Headers, Authorization, X-Requested-With, Credentials");
header("Access-Control-Allow-Methods: GET, OPTIONS");


try {

    $response = [
        'serverity' => 1,
        'code' => 200
    ];

    $jwt = getJWT();
    FlexAPI::guard()->login($jwt);

    $emailId = filter_input(INPUT_GET, 'emailId', FILTER_VALIDATE_INT);

    $userData = FlexAPI::superAccess()->read('userdata', [
        'filter' => [ 'user' => FlexAPI::guard()->getUsername() ],
        'flatten' => 'singleResult'
    ]);
    $demandEmail = FlexAPI::superAccess()->read('email', [
        'filter' => [
            'id' => $emailId,
            'person' => $userData['id']
        ],
        'references' => ['format' => 'data'],
        'flatten' => 'singleResult'
    ]);

    if (!$demandEmail) {
        throw(new Exception('Keine Forderungs-Mail gefunden.', 400));
    }

    if ($demandEmail['demanded_street_section']['mail_sent']) {
        throw(new Exception('Für den Straßenabschnitt wurde bereits eine Forderungsmail verschickt.', 400));
    }

    $config = include(__DIR__."/../api.conf.php");
    $dbConnection = new PdoPreparedConnection($config['databaseCredentials']['data']);
    
    $statement = $dbConnection->executeQuery(""
        . "SELECT policedepartment.email FROM policedepartment"
        . " JOIN institution ON ST_CONTAINS(policedepartment.geom, institution.position)"
        . " WHERE institution.id = :institution",
        [ 'institution' => $demandEmail['demanded_street_section']['institution'] ]
    );
    $policeDepartment = $statement->fetchAll();
    if (!count($policeDepartment)) {
        throw(new Exception('Es konnte kein zuständiges Polizeikommissariat gefunden werden.', 400));
    }
    $policeDepartment = $policeDepartment[0];
    // $pdEmail = $policeDepartment['email'];
    // $pdEmail = str_replace('polizei.hamburg.de', 'sven.anders.hamburg', $pdEmail);
    $pdEmail = 'floderflo@web.de';

    $demandMessage = implode("\n\n", [
        $demandEmail['mail_start'],
        $demandEmail['mail_body'],
        $demandEmail['mail_end']
    ]);

    $response['message'] = 'Send demand to: '.$pdEmail;
    

    $mailService = new SmtpMailService($config['mailing']['smtp'], [
        'address' => $config['defaultFrom']['address'],
        'name' => $userData['first_name']." ".$userData['last_name']
    ]);
    $mailService->send(
        $pdEmail,
        $demandEmail['mail_subject'],
        nl2br($demandMessage),   // HTML
        $demandMessage,          // plain
        $userData['user']        // CC
    );

    FlexAPI::superAccess()->update('demandedstreetsection', [
        'id' => $demandEmail['demanded_street_section']['id'],
        'mail_sent' => true
    ]);

} catch (Exception $exc) {
    $responseCode = $exc->getCode();
    $response = [
        'serverity' => 3,
        'message' => $exc->getMessage(),
        'code' => $exc->getCode()
    ];

}

echo json_encode($response, JSON_UNESCAPED_UNICODE);