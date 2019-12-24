<?php
header("Content-type: application/json; charset=utf-8");

$timestamp = filter_input(INPUT_GET, 'timestamp',FILTER_VALIDATE_INT);

$statusArr=['unklar','forderung','ok','fehlt','abgelehnt','angeordnet'];
$bezirkArr=["Altona", "Bergedorf", "Eimsbüttel", "Hamburg-Mitte","Hamburg-Nord" , "Harburg", "Wandsbek" ];
$config = include(__DIR__."/../api.conf.php");

$dbcfg = $config['databaseCredentials']['data'];

$pdo = new PDO('mysql:host='.$dbcfg['host'].';dbname='.$dbcfg['database'], $dbcfg['username'], $dbcfg['password']);

function statusQuery($queryStr) {
    global $statusArr,$pdo;
    $statement = $pdo->prepare($queryStr);
    $statement->execute();
    $rtn=[];
    foreach($statusArr as $statusStr) {
           $rtn[$statusStr]=0;
    }
    $all= $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach($all as $row) {
        $statusStr=$statusArr[$row['status']];
        $rtn[$statusStr]=intval($row['count'], 10);
    }
    return $rtn;
}

function countQuery($queryStr) {
    global $pdo;
    $statement = $pdo->prepare($queryStr);
    $statement->execute();
    $all= $statement->fetchAll(PDO::FETCH_ASSOC);
    return intval($all[0]['count'], 10);
}

$data = [];
$data['all']=[];
$data['all']['streetSection']=statusQuery("select status,Count(id) as count from demandedstreetsection group by status ");
$data['institution'] = statusQuery("select status,Count(id) as count from institution group by status ");
$data['all']['user'] = countQuery("select COUNT(*) as count FROM user");

$data['all']['email']=[];
$data['all']['email']['send'] = countQuery("select COUNT(*) as count from email where mail_send");
$data['all']['email']['prepared'] = countQuery("select COUNT(*) as count from email where not mail_send");


foreach($bezirkArr as $bezirk) {
    $data[$bezirk]=[];
    $data[$bezirk]['institution']= statusQuery("select status, Count(i.id) as count from districthamburg d , institution i where ST_CONTAINS(d.geom,i.position) and d.name=\"".$bezirk."\" group by i.status");
    $data[$bezirk]['streetsecion']= statusQuery("select  s.status, Count(i.id) as count from districthamburg d , institution i, demandedstreetsection s where ST_CONTAINS(d.geom,i.position) and d.name=\"".$bezirk."\" and s.institution=i.id group by s.status ");
}
print json_encode($data, JSON_PRETTY_PRINT);
