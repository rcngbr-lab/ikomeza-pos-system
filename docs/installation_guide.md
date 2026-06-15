# IKOMEZA POS Installation Guide

## Production Database

Use PostgreSQL or MySQL for pilot and production deployments. SQLite is for local testing only.

Required production environment values:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-domain.example
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
# Or use Railway/Postgres variables:
# PGHOST=...
# PGPORT=5432
# PGDATABASE=...
# PGUSER=...
# PGPASSWORD=...
ALLOW_SQLITE_PRODUCTION=false
ALLOW_PRODUCTION_SEEDING=false
RUN_DEMO_SEEDERS=false
ENABLE_DEMO_ACCOUNTS=false
SESSION_SECURE_COOKIE=true
```

## First Install

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Create the first admin through protected environment variables, then remove or rotate them after onboarding:

```env
ADMIN_NAME=Administrator
ADMIN_USERNAME=admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=change-immediately
ADMIN_RESET_PASSWORD=false
```

## Local Demo

Local demo data is opt-in only:

```env
APP_ENV=local
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
ENABLE_DEMO_ACCOUNTS=true
RUN_DEMO_SEEDERS=true
```

Then run:

```bash
php artisan migrate --seed
```

Never enable demo flags in production.

## Railway Database Notes

If Railway shows `connection to server at "127.0.0.1", port 5432 failed`, the app does not have a real PostgreSQL service attached or the service variables are not exposed to the app. Add a Railway PostgreSQL service and expose `DATABASE_URL` or `PGHOST/PGPORT/PGDATABASE/PGUSER/PGPASSWORD` to the IKOMEZA POS service.
