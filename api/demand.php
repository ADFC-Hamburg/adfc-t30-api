<?php

include_once __DIR__ . '/../api.php';
include_once __DIR__ . '/../vendor/adfc-hamburg/flexapi/requestutils/jwt.php';
include_once __DIR__ . '/../T30MailFactory.php';

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

    $section = $demandEmail['demanded_street_section'];

    $institution = FlexAPI::superAccess()->read('institution', [
        'filter' => [ 'id' => $section['institution'] ],
        'flatten' => 'singleResult'
    ]);

    $sonderAktion = FlexAPI::get('sonderAktion');
    if (time() < $sonderAktion['expires']) {
        $password = filter_input(INPUT_GET, 'sonderAktion');
        if (in_array($institution['type'], [1, 2]) && $password !== $sonderAktion['password']) {
            throw(new Exception('Falsches Aktions-Passwort.', 400));
        }
    }

    if ($section['mail_sent']) {
        throw(new Exception('Für den Straßenabschnitt wurde bereits eine Forderungsmail verschickt.', 400));
    }

    $config = include(__DIR__."/../api.conf.php");
    $dbConnection = new PdoPreparedConnection($config['databaseCredentials']['data']);

    $statement = $dbConnection->executeQuery(""
        . "SELECT policedepartment.email FROM policedepartment"
        . " JOIN institution ON ST_CONTAINS(policedepartment.geom, institution.position)"
        . " WHERE institution.id = :institution",
        [ 'institution' => $section['institution'] ]
    );
    $policeDepartment = $statement->fetchAll();
    if (!count($policeDepartment)) {
        throw(new Exception('Es konnte kein zuständiges Polizeikommissariat gefunden werden.', 400));
    }
    $policeDepartment = $policeDepartment[0];
    $pdEmail = $policeDepartment['email'];
    // zum TEST:
    $pdEmail = str_replace('polizei.hamburg.de', 'sven.anders.hamburg', $pdEmail);

    $demandMessage = implode("\n\n", [
        T30MailFactory::demandHeader($userData[user],$config['defaultFrom']['address']),
        $demandEmail['mail_start'],
        $demandEmail['mail_body'],
        $demandEmail['mail_end'],
        T30MailFactory::demandFooter(),
    ]);

    $response['message'] = 'Send demand to: '.$pdEmail;

    $demandFrom = $config['demandFrom'];
    $mailService = new SmtpMailService($config['mailing']['smtp'], $demandFrom);

    $mailService->addReplyTo($userData['user'], $userData['firstName']." ".$userData['lastName']);
    $mailService->addReplyTo($config['defaultFrom']['address'], $config['defaultFrom']['name']);
    $mailService->send(
        $pdEmail,
        $demandEmail['mail_subject'],
        null,            // no HTML
        $demandMessage,  // plain
        [$userData['user'], $config['defaultFrom']['address']]        // CC
    );

    FlexAPI::superAccess()->update('demandedstreetsection', [
        'id' => $section['id'],
        'mail_sent' => true,
        'status' => DemandedStreetSection::STATUS_T30_FORDERUNG
    ]);

    FlexAPI::superAccess()->update('email', [
        'id' => $emailId,
        'sent_on' => date('Y-m-d h:i:s'),
        'mail_send' => true
    ]);

    $emailsWithSameSection = FlexAPI::superAccess()->read('email', [
        'filter' => [ 'demanded_street_section' => $section['id'] ],
        'references' => ['format' => 'data'],
        'selection' => ['id', 'person']
    ]);
    $otherEmails = array_filter($emailsWithSameSection, function($m) use($emailId) { return $m['id'] != $emailId; });
    $otherEmails = array_map(function($m) { return $m['person']['user']; }, $otherEmails);

    $mailService = new SmtpMailService($config['mailing']['smtp'], $config['defaultFrom']);
    foreach ($otherEmails as $to) {
        $mailService->send(
            $to,
            'Tempo-30-Forderung: Jemand anderes war schneller.',
            null, // no HTML
            T30MailFactory::makeDemandSentNotificationMail($demandEmail, $institution, 'plain')
        );
    }

} catch (Exception $exc) {
    $responseCode = $exc->getCode();
    $response = [
        'serverity' => 3,
        'message' => $exc->getMessage(),
        'code' => $exc->getCode()
    ];

}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
