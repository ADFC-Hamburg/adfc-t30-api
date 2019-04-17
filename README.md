# Test Server

http://ben-steffen.de/t30/api/

## Alle Institutionen:

GET http://ben-steffen.de/t30/api/crud.php?entity=institution

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

Für CRUD-Operationen, für die eine
