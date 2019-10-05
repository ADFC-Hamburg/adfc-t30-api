#!/bin/bash
echo START>php-screen.log
/usr/bin/php -S 127.0.0.1:1234 2>&1 >>php-screen.log
echo END $?>>php-screen.log
