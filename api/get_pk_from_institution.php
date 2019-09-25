<?php
header("Content-type: application/json; charset=utf-8");

$config = include(__DIR__."/../api.conf.php");
$id = filter_input(INPUT_GET, 'id',FILTER_VALIDATE_INT);
$dbcfg=$config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);


$statement = $pdo->prepare("SELECT p.id as id ,p.name as name ,p.email as email,i.name as institution FROM policedepartment p, institution i WHERE ST_CONTAINS(p.geom, i.position) AND i.id=?;");


$statement->bindParam(1, $id);


if($statement->execute()) {
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    print json_encode($data);
};
