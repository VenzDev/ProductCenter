#!/bin/sh
set -e

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

php artisan migrate --force

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
