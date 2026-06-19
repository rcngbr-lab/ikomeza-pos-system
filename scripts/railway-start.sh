#!/bin/sh
set -eu

echo "Starting FRONTIER POS on Railway..."

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is missing. Set one fixed APP_KEY in Railway Variables."
    echo "Generate locally with: php artisan key:generate --show"
    echo "A changing APP_KEY breaks sessions and causes 419 Page Expired errors."
    exit 1
fi

if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

if [ -n "${APP_URL:-}" ] && printf '%s' "$APP_URL" | grep -q '^https://'; then
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
fi

if [ "${SESSION_DOMAIN:-}" = "null" ] || [ "${SESSION_DOMAIN:-}" = "NULL" ]; then
    unset SESSION_DOMAIN
fi

export SESSION_SAME_SITE="${SESSION_SAME_SITE:-lax}"

php artisan config:clear --no-interaction
php artisan view:clear --no-interaction

has_managed_database=false
if [ -n "${DATABASE_URL:-}" ] || [ -n "${DATABASE_PRIVATE_URL:-}" ] || [ -n "${POSTGRES_URL:-}" ] || [ -n "${POSTGRES_HOST:-}" ] || [ -n "${PGHOST:-}" ] || [ -n "${MYSQLHOST:-}" ] || [ -n "${MYSQL_HOST:-}" ] || [ -n "${MYSQL_URL:-}" ]; then
    has_managed_database=true
fi

if [ "$has_managed_database" = "false" ]; then
    db_connection="${DB_CONNECTION:-sqlite}"
    db_host="${DB_HOST:-}"

    if [ "$db_connection" != "sqlite" ] && { [ -z "$db_host" ] || [ "$db_host" = "127.0.0.1" ] || [ "$db_host" = "localhost" ]; }; then
        echo "No managed database variables found. Falling back to Railway runtime SQLite."
        export DB_CONNECTION=sqlite
    fi
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    export DB_DATABASE="${DB_DATABASE:-/app/database/database.sqlite}"
    export ALLOW_SQLITE_PRODUCTION="${ALLOW_SQLITE_PRODUCTION:-true}"
    echo "WARNING: Railway is using SQLite at ${DB_DATABASE}. Attach PostgreSQL/MySQL for persistent production data."
fi

php artisan pos:prepare-runtime --no-interaction
php artisan pos:production-preflight --warn-only --no-interaction
php artisan migrate --force --no-interaction

echo "Serving FRONTIER POS on 0.0.0.0:${PORT:-8080}"
exec php -d variables_order=EGPCS artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
