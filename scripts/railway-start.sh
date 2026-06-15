#!/usr/bin/env bash
set -euo pipefail

echo "Starting IKOMEZA POS on Railway..."

php artisan config:clear --no-interaction
php artisan view:clear --no-interaction

has_managed_database=false
if [[ -n "${DATABASE_URL:-}" || -n "${DATABASE_PRIVATE_URL:-}" || -n "${POSTGRES_URL:-}" || -n "${PGHOST:-}" || -n "${MYSQLHOST:-}" || -n "${MYSQL_URL:-}" ]]; then
    has_managed_database=true
fi

if [[ "$has_managed_database" == "false" ]]; then
    db_connection="${DB_CONNECTION:-sqlite}"
    db_host="${DB_HOST:-}"

    if [[ "$db_connection" != "sqlite" && ( -z "$db_host" || "$db_host" == "127.0.0.1" || "$db_host" == "localhost" ) ]]; then
        echo "No managed database variables found. Falling back to Railway runtime SQLite."
        export DB_CONNECTION=sqlite
    fi
fi

if [[ "${DB_CONNECTION:-sqlite}" == "sqlite" ]]; then
    export DB_DATABASE="${DB_DATABASE:-/app/database/database.sqlite}"
    export ALLOW_SQLITE_PRODUCTION="${ALLOW_SQLITE_PRODUCTION:-true}"
    echo "WARNING: Railway is using SQLite at ${DB_DATABASE}. Attach PostgreSQL/MySQL for persistent production data."
fi

php artisan pos:prepare-runtime --no-interaction
php artisan pos:production-preflight --warn-only --no-interaction
php artisan migrate --force --no-interaction

echo "Serving IKOMEZA POS on 0.0.0.0:${PORT:-8080}"
php -d variables_order=EGPCS artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
