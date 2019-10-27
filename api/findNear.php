<?php
header("Content-type: application/json; charset=utf-8");

$config = include(__DIR__."/../api.conf.php");
$lat = filter_input(INPUT_GET, 'lat',FILTER_VALIDATE_FLOAT);
$lon = filter_input(INPUT_GET, 'lon',FILTER_VALIDATE_FLOAT);

$dbcfg = $config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$point="POINT(".$lon." ".$lat.")";

$statement = $pdo->prepare('select id, name, street_house_no, zip, city from institution where  ST_Within(position, ST_BUFFER(ST_GeometryFromText(?),0.001))');

$statement->bindParam(1, $point);


if($statement->execute()) {
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    print json_encode($data);
};
#$statement->debugDumpParams();
#print_r($statement->errorInfo());
