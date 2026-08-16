#!/bin/sh
set -e

# Only runs once, on a brand-new (empty) postgres_data volume — unlike LocalStack's init
# scripts, Postgres skips /docker-entrypoint-initdb.d entirely once the data directory
# already exists. Creates a separate database so `php artisan test` (see phpunit.xml)
# never touches the dev database's data.
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE backend_test;
EOSQL
