#!/bin/bash

echo 'SELECT CONCAT("UPDATE institution SET position=ST_GeomFromText(\"POINT(",ST_X(i1.position)+0.00015," ",ST_Y(i1.position),")\") WHERE id=",i2.id,";")  FROM `institution` i1,  `institution` i2 WHERE i1.position=i2.position and i1.id<i2.id and i1.id<1907'
