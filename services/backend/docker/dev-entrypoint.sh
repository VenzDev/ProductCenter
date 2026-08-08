#!/bin/sh
set -e

php artisan migrate --force

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
