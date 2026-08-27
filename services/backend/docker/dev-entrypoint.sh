#!/bin/sh
set -e

php artisan migrate --force
php artisan search:install-index
php artisan filament:assets

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
