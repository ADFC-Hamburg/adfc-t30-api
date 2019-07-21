# Mysql Datenbank Setup

( später solltes das auf Postgresql umgestellt werden )

mysql -u root

```sql
CREATE DATABASE t30;
CREATE USER 't30'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON t30.* TO 't30'@'localhost';
FLUSH PRIVILEGES;
```

# Deployment

## PHP-Packages installieren

eventl. vendor/ und composer.lock löschen und "composer clear-cache" ausführen. Durch das Einbinden von flexapi in der Version "@dev" scheint die Caching-Problematik gelöst. Nicht von der Ausgabe, die das Gegenteil behauptet, verwirren lassen.

```bash
composer install
```

## Konfig Datei "api.conf.php" erzeugen

Beispiel-Konfig: "api.conf.example.php"

## Einstellung in "api.conf.php" anpassen

"databaseCredentials": Der Einfachheit halber identisch "data" und "guard" identisch

"mailing": smtp-Server Credentials anpassen

"basePath": Pfad auf dem Server, wo die API liegt. Wird benötigt um korrekte URLs bauen zu können.

"setupSecret":Setup Secret. Dieses muss dann auch beim Aufruf der setup.php zur Authorisierung des Setups mitgesendet werden.

"jwtSecret": Secret zur erzeugen aller JWTs

 
## Setup

API wird durch aufruf der setup.php initialisiert. Der Code, der beim Setup ausgeführt wird, befindet sich in der "api.php" in der Methode "onSetup".

POST /setup.php

``` json
{
	"resetSecret": "<setup secret>",
	"adminPassword": "<admin password>"
}
```

Optional kann man Test-Daten (>2000 Institutionen) und/oder einen Test-User einfügen lassen:
``` json
{
	"resetSecret": "<setup secret>",
	"adminPassword": "<admin password>",
	"fillInTestData": true,
	"registerTestUser": true
}
```

```bash
cp local.env.json.example local.env.json
# Change password in local.env.json
php -S 127.0.0.1:1234
curl -H "Content-Type: application/json" -d '{ "resetSecret": "<setup secret>", "adminPassword": "<admin password>"}'  http:/127.0.0.1:1234/setup.php
```

Für Produktion wäre meine Überlegung, die setup.php vom Server nach dem ersten Verwenden autom. löschen zu lassen.

## Alle Institutionen:

GET /api/crud.php?entity=institution

### Filtern

z.B. alle Institutionen im Bezirk Altona:

GET /api/crud.php?entity=institution&filter=[district,con,'altona']

z.B. alle Institutionen mit PLZ 22769 UND mit "kita" (case-ins.) im Namen:

GET /api/crud.php?entity=institution&filter=[zip,22769]and[name,con,'kita']

## User registrieren

Um einen neuen Benutzer zu registieren:

POST /api/portal.php

``` json
{
	"concern": "register",
	"username": "max-muster@some-provider.de",
	"password": "geheim",
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

POST /api/portal.php

``` json
{
	"concern": "login",
	"username": "max-muster@some-provider.de",
	"password": "geheim",
}
```

**Für CRUD-Operationen, für die eine Authentifizierung benötigen, den JSON Web Token (JWT) im Response-Body entnehmen und bei allen Requests, die eine Authentfizierung benötigen, in den Request-Header "Access-Control-Allow-Credentials" schreiben.**

## Passwort ändern

Falls User schon angemeldet (gültiger JWT wird mitgesendet), kann das Paswort direkt geändert werden:

POST /api/portal.php

``` json
{
	"concern": "passwordChange",
	"newPassword": "sicherer"
}
```

Falls User sein Passwort vergessen hat, wird ein JWT mit dem neuen Passwort erzeugt und an die Email-Adresse gesendet.

POST /api/portal.php

``` json
{
	"concern": "passwordChange",
	"email": "floderflo@gmx.de",
	"newPassword": "unvergesslich"
}
```


## Patenschaft posten

POST /api/crud.php?entity=patenschaft

``` json
{
	"institution": 6,
	"relationship": "Lehrer"
}
```

Die Relation zum Beutzer wird automatisch gesetzt.

## Institut updaten

PUT /api/crud.php?entity=institution

``` json
{
	"id": 1,
	"number": "33",
	"zip": "21078"
}
```

## Änderungsverlauf ("Monitoring")

Alle Änderungen für die Entität 'Institution' werden aufgezeichnet. Der Admin (JWT des Admins wird mitgeschickt) kann den gesamten Verlauf (hier für Id = 1) folgendermaßen abgerufen:

GET /api/monitor.php?entity=institution&id=1


# TODOS

 - [X] Pagination
 - [X] Sortierung
 - [x] Validierung der Email 
 - [x] Wer darf Instiutionen anlegen? -> Nur Admin und Registrierte
 - [x] Änderungen loggen (und Stände wiederherstellen)
 - [ ] Tabelle mit austehenden Verify-Tokens autom. regelmäßig aufräumen.
