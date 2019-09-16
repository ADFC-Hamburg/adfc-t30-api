#!/bin/bash

DB=t30
DB_USER=t30
DB_PASSWORD=$(cat t30_db.secret)
# http://suche.transparenz.hamburg.de/dataset/polizeikommissariate-hamburg1?forceWeb=true
# http://suche.transparenz.hamburg.de/dataset/alkis-verwaltungsgrenzen-hamburg?forceWeb=true


PK_URL="https://geodienste.hamburg.de/HH_WFS_PKGrenzen?SERVICE=WFS&VERSION=1.1.0&REQUEST=GetFeature&typename=app:pk_grenzen"

GRENZEN_URL="http://archiv.transparenz.hamburg.de/hmbtgarchive/HMDK/alkisverwaltungsgrenzen_hh_2014-09-17_3988_snap_1_10817_snap_1.ZIP"


wget -nc $PK_URL -O PK_GRENZEN.gml
wget -nc $GRENZEN_URL -O alkis_grenzen.zip

unzip -n alkis_grenzen.zip
ogr2ogr -t_srs WGS84 PK_Grenzen.shp PK_GRENZEN.gml
rm -rf ALKIS
ogr2ogr -t_srs WGS84 -s_srs EPSG:25832 ALKIS ALKISVerwaltungsgrenzen.gml
shp2pgsql -w PK_Grenzen.shp >PK_Grenzen.sql
shp2pgsql -w ALKIS/Bezirke.shp >Bezirke.sql
shp2pgsql -w ALKIS/Postleitzahlen.shp > PLZ.sql
shp2pgsql -w ALKIS/Stadtteile.shp > Stadtteile.sql

cat > out.sql <<EOF
DROP TABLE IF EXISTS bezirke;
DROP TABLE IF EXISTS districthamburg;

DROP TABLE IF EXISTS postleitzahlen;
DROP TABLE IF EXISTS zipcodes;

DROP TABLE IF EXISTS pk_grenzen;
DROP TABLE IF EXISTS policedepartment;

DROP TABLE IF EXISTS stadtteile;
DROP TABLE IF EXISTS township;



CREATE TABLE bezirke (gid serial,
gml_id varchar(80),
objectid int4,
bezirk int4,
bezirk_nam varchar(13),
shp_length numeric,
shp_area numeric);

ALTER TABLE bezirke ADD geom mediumblob;
ALTER TABLE bezirke ADD geom_new multipolygon;

CREATE TABLE pk_grenzen (gid serial,
gml_id varchar(80),
polizeirev int4,
bemerkung varchar(80),
pk varchar(80),
region varchar(80),
berechnung varchar(80),
stand_der_ varchar(80),
drehung int4,
zusatz varchar(80),
vd varchar(80));
ALTER TABLE pk_grenzen ADD PRIMARY KEY (gid);

ALTER TABLE pk_grenzen ADD geom mediumblob;
ALTER TABLE pk_grenzen ADD geom_new multipolygon;
ALTER TABLE pk_grenzen ADD email VARCHAR(80);

ALTER TABLE pk_grenzen ADD street_house_no VARCHAR(255);
ALTER TABLE pk_grenzen ADD zip VARCHAR(5);
ALTER TABLE pk_grenzen ADD city VARCHAR(20);
ALTER TABLE pk_grenzen ADD phone VARCHAR(20);


CREATE TABLE postleitzahlen (gid serial,
gml_id varchar(80),
objectid int4,
plz int4,
shape_leng numeric,
shape_area numeric);
ALTER TABLE postleitzahlen ADD PRIMARY KEY (gid);

ALTER TABLE postleitzahlen ADD geom mediumblob;
ALTER TABLE postleitzahlen ADD geom_new multipolygon;


CREATE TABLE stadtteile (gid serial,
gml_id varchar(80),
objectid int4,
stadtteil varchar(20),
bezirk varchar(13),
shp_length numeric,
shp_area numeric);
ALTER TABLE stadtteile ADD PRIMARY KEY (gid);

ALTER TABLE stadtteile ADD geom mediumblob;
ALTER TABLE stadtteile ADD geom_new multipolygon;

EOF

grep -h INSERT Bezirke.sql PK_Grenzen.sql PLZ.sql Stadtteile.sql | sed -e 's/"/`/g'  >> out.sql

cat >> out.sql <<EOF
UPDATE bezirke SET geom_new=ST_GeomFromText(geom);
UPDATE postleitzahlen SET geom_new=ST_GeomFromText(geom);
UPDATE pk_grenzen SET geom_new=ST_GeomFromText(geom);
UPDATE stadtteile SET geom_new=ST_GeomFromText(geom);

ALTER TABLE bezirke DROP geom;
ALTER TABLE postleitzahlen DROP geom;
ALTER TABLE pk_grenzen DROP geom;
ALTER TABLE stadtteile DROP geom;

ALTER TABLE bezirke CHANGE geom_new geom multipolygon;
ALTER TABLE postleitzahlen CHANGE geom_new geom multipolygon;
ALTER TABLE pk_grenzen CHANGE geom_new geom multipolygon;
ALTER TABLE stadtteile CHANGE geom_new geom multipolygon;


ALTER TABLE bezirke DROP gid;
ALTER TABLE bezirke DROP gml_id;
ALTER TABLE bezirke DROP shp_length;
ALTER TABLE bezirke DROP shp_area;

ALTER TABLE bezirke CHANGE bezirk_nam name varchar(13);
ALTER TABLE bezirke CHANGE bezirk id int4;
ALTER TABLE bezirke ADD PRIMARY KEY (id);
ALTER TABLE bezirke RENAME TO districthamburg;


ALTER TABLE pk_grenzen DROP gid;
ALTER TABLE pk_grenzen DROP gml_id;
ALTER TABLE pk_grenzen DROP berechnung;
ALTER TABLE pk_grenzen DROP drehung;
ALTER TABLE pk_grenzen DROP bemerkung;
ALTER TABLE pk_grenzen CHANGE polizeirev id int4;
ALTER TABLE pk_grenzen CHANGE pk name varchar(20);
ALTER TABLE pk_grenzen CHANGE stand_der_ changed_date varchar(30);

ALTER TABLE pk_grenzen ADD PRIMARY KEY (id);
UPDATE pk_grenzen SET email=CONCAT(LOWER(REPLACE(name,' ','')),'@polizei.hamburg.de');
ALTER TABLE pk_grenzen RENAME TO policedepartment;


ALTER TABLE stadtteile DROP gid;
ALTER TABLE stadtteile DROP gml_id;
ALTER TABLE stadtteile DROP shp_length;
ALTER TABLE stadtteile DROP shp_area;
ALTER TABLE stadtteile CHANGE objectid id int4;
ALTER TABLE stadtteile ADD PRIMARY KEY (id);
ALTER TABLE stadtteile CHANGE bezirk district varchar(30);
ALTER TABLE stadtteile CHANGE stadtteil name varchar(20);
ALTER TABLE stadtteile RENAME TO township;

DELETE FROM postleitzahlen WHERE gml_id="F6__171";
ALTER TABLE postleitzahlen DROP gid;
ALTER TABLE postleitzahlen DROP gml_id;
ALTER TABLE postleitzahlen DROP objectid;
ALTER TABLE postleitzahlen DROP shape_leng;
ALTER TABLE postleitzahlen DROP shape_area;
ALTER TABLE postleitzahlen CHANGE plz zip VARCHAR(5);
ALTER TABLE postleitzahlen ADD PRIMARY KEY (zip);
ALTER TABLE postleitzahlen RENAME TO zipcodes;

EOF
mysql -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB}" <out.sql
mysqldump -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB}" zipcodes township districthamburg policedepartment >dump.sql
