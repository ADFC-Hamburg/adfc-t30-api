#!/bin/bash
echo START>php-screen.log
/usr/bin/php -S 127.0.0.1:1234  >>php-screen.log 2>&1
echo END $?>>php-screen.log
