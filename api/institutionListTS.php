<?php
header("Content-type: application/json; charset=utf-8");

$timestamp = filter_input(INPUT_GET, 'timestamp',FILTER_VALIDATE_INT);

$config = include(__DIR__."/../api.conf.php");

$dbcfg = $config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);

$statement = $pdo->prepare("SELECT i.id,i.status,i.name, i.street_house_no, i.zip, i.city, i.type, ST_X(i.position) as x, ST_Y(i.position) as y, t.district FROM `institution` i,  `township` t WHERE  ST_CONTAINS(`t`.`geom`,`i`.`position`)");
$statement2 = $pdo->prepare("select MAX(timestamp) as timestamp from entitychange e, changemetadata cm where e.entityName=\"institution\" and cm.id=e.id");
function map_func($row) {
  $row['position']=[$row['x'], $row['y']];
  unset($row['x']);
  unset($row['y']);
  return $row;
}
if ($statement2->execute()) {
    $stamp = intval($statement2->fetch(PDO::FETCH_NUM)[0]);
    $data = [];
     if ($stamp != $timestamp) {
         if($statement->execute()) {
             $data = $statement->fetchAll(PDO::FETCH_ASSOC);
         };
     };
     print json_encode([
         'data' => array_map('map_func',$data),
         'timestamp' => $stamp,
         'mystamp' =>$timestamp,
     ]);

};
