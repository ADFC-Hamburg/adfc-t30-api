<?php
header("Content-type: application/json; charset=utf-8");

$aktion_date=mktime(59,59,23,10,20,2019);
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
