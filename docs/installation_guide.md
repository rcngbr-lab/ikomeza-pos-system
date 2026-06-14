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
DB_HOST=...
DB_PORT=5432
DB_DATABASE=ikomeza_pos
DB_USERNAME=...
DB_PASSWORD=...
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

