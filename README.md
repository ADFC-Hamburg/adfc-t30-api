# Test Server

http://ben-steffen.de/t30/api/

## Alle Institutionen:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution

### Filtern

z.B. alle Institutionen im Bezirk Altona:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution&filter=[district,con,'altona']

z.B. alle Institutionen mit PLZ 22769 UND mit "kita" (case-ins.) im Namen:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution&filter=[zip,22769]and[name,con,'kita']

## User registrieren

POST https://ben-steffen.de/t30/api/portal.php

``` json
{
	"concern": "register",
	"username": "floderflo@gmx.de",
	"password": "123"
}
```
## User einloggen

POST https://ben-steffen.de/t30/api/portal.php

``` json
{
	"concern": "login",
	"username": "floderflo@gmx.de",
	"password": "123"
}
```

Für CRUD-Operationen, für die eine Authentifizierung benötigen, den JSON Web Token (JWT) im Response-Body kopieren und bei den Requests in den Request-Header "Access-Control-Allow-Credentials" schreiben.

## Benutzerdaten posten

POST https://ben-steffen.de/t30/api/crud.php?entity=userdata

``` json
{
	"firstName": "Flo",
	"lastName": "Rian",
	"street": "Musterstr.",
	"number": "11",
	"city": "Hamburg",
	"zip": "22222",
	"phone": "0212-22222"
}
```

Benutzer-Daten können nur einmal pro Benutzer gepostet werden. Um eine Patenschaft zu erstellen, müssen die Benutzerdaten gepostet sein.

## Patenschaft posten

POST https://ben-steffen.de/t30/api/crud.php?entity=patenschaft

``` json
{
	"institution": 6,
	"relationship": "Lehrer"
}
```
Die Relation zum Beutzer wird automatisch gesetzt.
