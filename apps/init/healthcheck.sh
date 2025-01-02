#!/bin/bash

if cgi-fcgi -bind -connect 127.0.0.1:9000 > /dev/null 2>&1; then
    echo "php-fpm is healthy"
    exit 0
else
    echo "php-fpm is not healthy"
    exit 1
fi
