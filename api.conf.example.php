<?php

return [
    "environment" => "example",
    "maxUserNameLength" => 128,
    "jwtValidityDuration" => 3600,
    "jwtValidityExtension" => 3600,
    "databaseCredentials" => [
        "data" => [
            "driver" => "mysql",
            "host" => "localhost",
            "database" => "t30-db-name",
            "username" => "t30-db-user",
            "password" => "t30-db-password"
        ],
        "guard" => [
            "driver" => "mysql",
            "host" => "localhost",
            "database" => "t30-db-name",
            "username" => "t30-db-user",
            "password" => "t30-db-password"
        ]
    ],
    "mailing" => [
        "smtp" => [
            "host" => "send.one.com",
            "port" => 25,
            "username" => "t30@adfc-hamburg.de",
            "password" => "<password>"
        ],
        "from" => [
            "verification" => [
                "address" => "adfc@ben-steffen.de",
                "name" => "ADFC Hamburg"
            ]
        ]
    ],
    "defaultFrom" => [
      "address" => "adfc@ben-steffen.de",
      "name" => "ADFC Hamburg"
    ],
    "defaultUrlScheme" => 'http',
    "basePath" => "/adfc/api-2019-07/adfc-t30-api",
    "apiPath" => "/api",
    "appRoot" => "./..",
    "frontendBaseUrl" => "http://tools.adfc-hamburg.de/t30-paten/latest",
    "setupSecret" => "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy",
    "jwtSecret" => "rtgTWs]N;)%Bz^,z1?SH2 =>9su=AOq2.<Q&FhJ!yOMR|J8ox5$2QCH6HP!Q.R!)8n",
    "userVerification" => [
        "enabled" => true,
        "validityDuration" => 86000
    ],
    "passwordChangeValidityDuration" => 24*3600,
    "reportDataChangesTo" => ["projekt-leiterin-t30@adfc-hamburg.de", "system-admin-t30@adfc-hamburg.de"],
    "sonderAktion" => [
        "expires" => strtotime('2019-12-01 00:00:00'),
        "password" => "aktion"
    ]
];
