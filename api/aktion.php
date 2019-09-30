<?php
header("Content-type: application/json; charset=utf-8");

$type = filter_input(INPUT_GET, 'type',FILTER_VALIDATE_INT);

if (($type == 1) || ($type == 2)) {
    $aktion_date=mktime(59,59,23,10,20,2019);
} else {
    $aktion_date=mktime(0,0,0,1,1,2000);
}
$now=time();
$format="Y-m-d";
$data = [
    'until' =>date($format,$aktion_date),
    'now' => date($format, $now),
    'reached' => ($aktion_date < $now),
    'pw_check' => 0
];

if ($_GET['password'] == "OKAY") {
    $data['pw_check'] =1;
}
print json_encode($data);
