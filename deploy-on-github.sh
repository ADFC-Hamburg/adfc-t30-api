#!/bin/bash
#


VERSION=$(jq -r '.version' composer.json)
MYSQL_ROOT_PW="root"
T30_PW="t30pw"
T30_ADMIN="t30adminpw"
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

cd vendor
ln -s adfc-hamburg ADFC-Hamburg
cd ..
ls -la vendor
mkdir ~/.screen ; chmod 700 ~/.screen
export SCREENDIR=~/.screen
exit
screen -L -d -m /usr/bin/php -S 127.0.0.1:1234
# every second flush log
screen -X logfile flush 1
sleep 2
curl -v -H "Content-Type: application/json" -d '{ "resetSecret": "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy", "adminPassword": "'"${T30_ADMIN}"'", "fillInTestData": true, "registerTestUser": true}'  "http://127.0.0.1:1234/setup.php"
echo $?
screen -X stuff "$(printf '%b' 'this into input\015')"
sleep 1
echo == Log ==
cat screenlog.*
echo == Log ENDE ==
mysql "-u${DATABASE}" "-p${T30_PW}" "${DATABASE}" < geodaten.sql
