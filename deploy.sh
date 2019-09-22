#!/bin/bash
#

VERSION=$1

cd /var/www/html/t30-paten/api
git clone --single-branch -b v${VERSION} https://github.com/ADFC-Hamburg/adfc-t30-api  version${VERSION}
cd version${VERSION}
# ./deploy.sh

T30_SEC_DIR="/root/.t30-secret"

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
MYSQL_ROOT_PW="$(cat ${T30_SEC_DIR}/mysql.root.secret)"
T30_PW=$(get_secret t30.db)
T30_ADMIN=$(get_secret t30.admin)
DATABASE="t30paten_$(echo $VERSION |sed -e 's/\./_/g')"

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
sed -i -e "s/http/https/" api.conf.php
sed -i -e "s/ADFC Hamburg/ADFC Hamburg Tempo30 vor sozialen Einrichtungen/" api.conf.php
sed -i -e "s/\/adfc\/api-2019-07\/adfc-t30-api/\/t30-paten\/api\/version${VERSION}/" api.conf.php
sed -i -e 's/"projekt-leiterin-t30@adfc-hamburg.de", "system-admin-t30@adfc-hamburg.de"/ "t30changes2019@sven.anders.hamburg" /' api.conf.php
composer install

echo 'Call setup.php'

curl -v -H "Content-Type: application/json" -d '{ "resetSecret": "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy", "adminPassword": "'"${T30_ADMIN}"'", "fillInTestData": true, "registerTestUser": true}'  "https://tools.adfc-hamburg.de/t30-paten/api/version${VERSION}/setup.php"
