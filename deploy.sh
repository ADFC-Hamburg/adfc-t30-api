#!/bin/bash
T30_SEC_DIR=".t30-secret"

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
MYSQL_ROOT_PW="$(cat ~/${T30_SEC_DIR}/mysql.root.secret)"
T30_PW=$(get_secret t30.db)
T30_ADMIN=$(get_secret t30.admin)
DATABASE="t30paten_$(echo $OLD_VERSION |sed -e 's/\./_/g')"

echo "== Drop Database =="
echo "DROP DATABASE IF EXISTS $DATABASE; " | mysql -u root -p"${MYSQL_ROOT_PW}"

echo "== Create Database =="
mysql -u root -p"${MYSQL_ROOT_PW}" <<EOF
CREATE DATABASE ${DATABASE};
CREATE USER IF NOT EXISTS 't30'@'localhost' IDENTIFIED BY '$T30_PW';
GRANT ALL PRIVILEGES ON ${DATABASE}.* TO 't30'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "== Create Config File =="
cp api.conf.example.php api.conf.php
sed -i -e "s/t30-db-password/${T30_PW}/" api.conf.php
sed -i -e "s/t30-db-user/t30/" api.conf.php
sed -i -e "s/t30-db-name/${DATABASE}/" api.conf.php

composer install

echo 'Call setup.php'

curl -v -H "Content-Type: application/json" -d '{ "resetSecret": "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy", "adminPassword": "${T30_ADMIN}", "fillInTestData": true, "registerTestUser": true}'  "https://tools.adfc-hamburg.de/t30-paten/api/version${VERSION}/setup.php"
