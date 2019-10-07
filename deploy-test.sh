#!/bin/bash

URL="http://127.0.0.1:1234/"
DATA=$( echo '<?php print json_encode(require("api.conf.php"));' |php)
MYSQL_DB=$(echo $DATA |jq  -r '.databaseCredentials.data.database')
MYSQL_USER=$(echo $DATA |jq  -r '.databaseCredentials.data.username')
MYSQL_PW=$(echo $DATA |jq  -r '.databaseCredentials.data.password')
SETUP_PW=$(echo $DATA |jq  -r '.setupSecret')

GEODATEN_URL="https://tools.adfc-hamburg.de/t30-paten/daten/geodaten.sql"
cd import
wget -c $GEODATEN_URL
composer install
php import.php >../test/data/institutions_reshaped.json
cd ..

curl -v -H "Content-Type: application/json" -d '{ "resetSecret": "'$SETUP_PW'", "adminPassword": "geheim", "fillInTestData": true, "registerTestUser": true}' http://127.0.0.1:1234/setup.php
mysql -u"$MYSQL_USER" -p"$MYSQL_PW"  $MYSQL_DB < import/geodaten.sql
