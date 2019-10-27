<?php
header("Content-type: application/json; charset=utf-8");

$config = include(__DIR__."/../api.conf.php");

$dbcfg = $config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);

$statement = $pdo->prepare("SELECT i.id,i.status,i.name, i.street_house_no, i.zip, i.city, i.type, ST_X(i.position) as x, ST_Y(i.position) as y, t.district FROM `institution` i,  `township` t WHERE  ST_CONTAINS(`t`.`geom`,`i`.`position`)");
function map_func($row) {
  $row['position']=[$row[x], $row['y']];
  unset($row['x']);
  unset($row['y']);
  return $row;
}
if($statement->execute()) {
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    print json_encode(array_map('map_func',$data));
};
