<?php
header("Content-type: application/json; charset=utf-8");

$config = include(__DIR__."/../api.conf.php");
$id = filter_input(INPUT_GET, 'id',FILTER_VALIDATE_INT);
$email = filter_input(INPUT_GET, 'email',FILTER_VALIDATE_EMAIL);

$dbcfg = $config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);

$statement = $pdo->prepare("SELECT e.entityName,c.timeStamp, (c.user = ?) AS isOwnUser FROM entitychange e, changemetadata c, demandedstreetsection d WHERE d.id=? AND ((e.entityId=d.id AND e.entityName='demandedstreetsection') OR (e.entityId=d.institution AND e.entityName='institution')) AND c.id=e.id ORDER BY c.timeStamp;");

$statement->bindParam(1, $email);
$statement->bindParam(2, $id);


if($statement->execute()) {
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    print json_encode($data);
};
