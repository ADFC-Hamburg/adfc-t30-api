<?php
header("Content-type: application/json; charset=utf-8");

$config = include(__DIR__."/../api.conf.php");
$composer = json_decode(file_get_contents("../composer.json"), true);
$data = [
  "version" => $composer['version'],
  "jwtValidityDuration" => $config["jwtValidityDuration"],
  "passwordChangeValidityDuration" => $config['passwordChangeValidityDuration'],
  "userVerificationValidityDuration" => $config['userVerification']['validityDuration'],
  "author" => $composer['authors'],
  "time" => time(),
  "git" => explode(",",trim(exec('git log --pretty="%h,%H,%ct" -n1 HEAD'))),
  "antwort" => 42, // Douglas Adams
];
print json_encode($data);
