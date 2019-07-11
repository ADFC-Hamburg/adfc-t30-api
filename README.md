# Mysql Datenbank Setup

( später solltes das auf Postgresql umgestellt werden )

mysql -u root

```sql
CREATE DATABASE t30;
CREATE USER 't30'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON t30.* TO 't30'@'localhost';
FLUSH PRIVILEGES;
```

Das Passwort und den User in der local.env.json anpassen.


```bash
cp local.env.json.example local.env.json
# Change password in local.env.json
php -S 127.0.0.1:1234
curl -H "Content-Type: application/json" -d '{ "resetSecret": "reset!"}'  http:/127.0.0.1:1234/setup.php
```


# Test Server

Moin Leute,

stand 13.6.2019 gibt es jetzt eine API-Version zum testen unter:

 * http://ben-steffen.de/t30/api/
 
## Setup

Leert alle Tabellen, erzeugt Benutzer "admin" und "guest", setzt für admin das Passwort **adminPassword** und setzt CRUD-Berechtigungen.

POST https://ben-steffen.de/t30/setup.php

``` json
{
	"resetSecret": "<bekommt ihr per Mail>",
	"adminPassword": "<enter admin password here>"
}
```

Optional kann man Test-Daten (>2000 Institutionen) und/oder einen Test-User einfügen lassen:
``` json
{
	"resetSecret": "<bekommt ihr per Mail>",
	"adminPassword": "<enter admin password here>",
	"fillInTestData": true,
	"registerTestUser": true
}
```

Für Produktion wäre meine Überlegung, die setup.php vom Server nach dem ersten Verwenden autom. löschen zu lassen.

## Alle Institutionen:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution

### Filtern

z.B. alle Institutionen im Bezirk Altona:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution&filter=[district,con,'altona']

z.B. alle Institutionen mit PLZ 22769 UND mit "kita" (case-ins.) im Namen:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution&filter=[zip,22769]and[name,con,'kita']

## User registrieren

Um einen neuen Benutzer zu registieren:

POST https://ben-steffen.de/t30/api/portal.php

``` json
{
	"concern": "register",
	"username": "max-muster@some-provider.de",
	"password": "geheim"
	"userData": {
		"firstName": "Max",
		"lastName": "Muster",
		"street": "Fakestreet",
		"number": "123",
		"city": "Hamburg",
		"zip": 22666
	}
}
```

Nach diesem Request wird eine Email mit Aktivierungs-Link an die angegeben Email-Adresse geschickt. Einloggen ist erst nach klicken des Links möglich.

## User einloggen

Folgender Request erzeugt ein JWT (JSON Web Token):

POST https://ben-steffen.de/t30/api/portal.php

``` json
{
	"concern": "login",
	"username": "max-muster@some-provider.de",
	"password": "geheim",
}
```

**Für CRUD-Operationen, für die eine Authentifizierung benötigen, den JSON Web Token (JWT) im Response-Body entnehmen und bei allen Requests, die eine Authentfizierung benötigen, in den Request-Header "Access-Control-Allow-Credentials" schreiben.**

## Patenschaft posten

POST https://ben-steffen.de/t30/api/crud.php?entity=patenschaft

``` json
{
	"institution": 6,
	"relationship": "Lehrer"
}
```

Die Relation zum Beutzer wird automatisch gesetzt.

## Institut updaten

PUT https://ben-steffen.de/t30/api/crud.php?entity=institution

``` json
{
	"id": 1,
	"number": "33",
	"zip": "21078"
}
```

# TODOS


 - [ ] Pagination
 - [ ] Sortierung
 - [x] Validierung der Email 
 - [x] Wer darf Instiutionen anlegen? -> Nur Admin und Registrierte
 - [ ] Änderungen loggen (und Stände wiederherstellen)
 - [ ] Tabelle mit austehenden Verify-Tokens autom. regelmäßig aufräumen.
