<?php

return [
    "environment" => "example",
    "maxUserNameLength" => 128,
    "jwtValidityDuration" => 3600,
    "databaseCredentials" => [
        "data" => [
            "host" => "localhost",
            "database" => "t30",
            "username" => "root",
            "password" => ""
        ],
        "guard" => [
            "host" => "localhost",
            "database" => "t30",
            "username" => "root",
            "password" => ""
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
    "basePath" => "/adfc/api-2019-07/adfc-t30-api",
    "apiPath" => "/api",
    "appRoot" => "./..",
    "setupSecret" => "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy",
    "jwtSecret" => "rtgTWs]N;)%Bz^,z1?SH2 =>9su=AOq2.<Q&FhJ!yOMR|J8ox5$2QCH6HP!Q.R!)8n",
    "userVerification" => [
        "enabled" => true,
        "validityDuration" => 86000
    ]
];
