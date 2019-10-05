#!/bin/bash
#

VERSION=$1
T30_SEC_DIR="~/.t30-secret"
sudo apt update
sudo apt install pwgen

function get_secret {
    DESCR=$1
    PW_FILE="${T30_SEC_DIR}/${DESCR}.secret"
    if [ ! -f "${PW_FILE}" ] ; then
	pwgen -s1 16 > $PW_FILE
    fi
    cat $PW_FILE
}

mkdir -p $T30_SEC_DIR

VERSION=$(jq -r '.version' composer.json)
MYSQL_ROOT_PW="root"
T30_PW=$(get_secret t30.db)
T30_ADMIN=$(get_secret t30.admin)
DATABASE="t30paten"

echo DB: $DATABASE
echo "== Drop Database =="
echo "DROP DATABASE IF EXISTS $DATABASE; " | mysql -u root -p"${MYSQL_ROOT_PW}"
echo "DROP USER IF EXISTS '$DATABASE'@'localhost'; " | mysql -u root -p"${MYSQL_ROOT_PW}"

echo "== Create Database =="
mysql -u root -p"${MYSQL_ROOT_PW}" <<EOF
CREATE DATABASE ${DATABASE};
CREATE USER '${DATABASE}'@'localhost' IDENTIFIED BY '$T30_PW';
GRANT ALL PRIVILEGES ON ${DATABASE}.* TO '${DATABASE}'@'localhost';
FLUSH PRIVILEGES;
EOF
cat  <<EOF
CREATE DATABASE ${DATABASE};
CREATE USER '${DATABASE}'@'localhost' IDENTIFIED BY '$T30_PW';
GRANT ALL PRIVILEGES ON ${DATABASE}.* TO '${DATABASE}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "== Create Config File =="
cp api.conf.example.php api.conf.php
sed -i -e "s/t30-db-password/${T30_PW}/" api.conf.php
sed -i -e "s/t30-db-user/${DATABASE}/" api.conf.php
sed -i -e "s/t30-db-name/${DATABASE}/" api.conf.php
# Use Sendmail
sed -i -e "s/send.one.com//" api.conf.php
sed -i -e "s/adfc@ben-steffen.de/tempo30sozial@hamburg.adfc.de/" api.conf.php
sed -i -e "s/ADFC Hamburg/ADFC Hamburg Tempo30 vor sozialen Einrichtungen/" api.conf.php
sed -i -e "s/\/adfc\/api-2019-07\/adfc-t30-api/\/t30-paten\/api\/version${VERSION}/" api.conf.php
sed -i -e 's/"projekt-leiterin-t30@adfc-hamburg.de", "system-admin-t30@adfc-hamburg.de"/ "t30-changes@hamburg.adfc.de" /' api.conf.php


cat api.conf.php

echo 'Call setup.php'
mkdir ~/.screen ; chmod 700 ~/.screen
export SCREENDIR=~/.screen
screen -L -d -m /usr/bin/php -S 127.0.0.1:1234
sleep 2
curl -v -H "Content-Type: application/json" -d '{ "resetSecret": "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy", "adminPassword": "'"${T30_ADMIN}"'", "fillInTestData": true, "registerTestUser": true}'  "http://127.0.0.1:1234/setup.php"
echo $?
screen -X stuff "$(printf '%b' 'this into input\015')"
echo == Log ==
cat screenlog.*
echo == Log ENDE ==
wget https://tools.adfc-hamburg.de/t30-paten/daten/geodaten.sql
mysql "-u${DATABASE}" "-p${T30_PW}" "${DATABASE}" < geodaten.sql

echo "== Run Tests =="
cd test
cp test/testConfig.example.json test/testConfig.json
npm install
npm test

cd ..
screen -X stuff ^C
sleep 1
screen -X quit
echo == Log ==
cat screenlog.*
echo == Log ENDE ==

# Delete screen
