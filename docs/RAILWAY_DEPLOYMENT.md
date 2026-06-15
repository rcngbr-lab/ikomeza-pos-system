# IKOMEZA POS Railway Deployment

## Current Cloud Startup

Railway starts IKOMEZA POS through:

```bash
sh scripts/railway-start.sh
```

The startup script:

1. Clears Laravel cached config and views.
2. Uses Railway PostgreSQL/MySQL variables when they exist.
3. Falls back to runtime SQLite only when no managed database variables are present.
4. Creates missing writable runtime folders and the SQLite file when needed.
5. Runs production preflight checks in warning mode.
6. Runs migrations.
7. Serves Laravel on `0.0.0.0:$PORT`.

If `APP_KEY` is missing, the startup script generates a temporary runtime key so the container can boot. This keeps the site accessible for emergency/demo recovery only. Set a fixed Railway `APP_KEY` variable for stable sessions and password-reset/security behavior.

## Required Production Variables

For a real pilot or production deployment, attach Railway PostgreSQL or MySQL and set one of these groups:

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...
```

or:

```env
DB_CONNECTION=pgsql
PGHOST=...
PGPORT=5432
PGDATABASE=...
PGUSER=...
PGPASSWORD=...
```

Also keep:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://ikomeza-pos-system-production.up.railway.app
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
ENABLE_DEMO_ACCOUNTS=false
RUN_DEMO_SEEDERS=false
```

## Temporary SQLite Mode

If no managed database is attached, Railway now starts with SQLite at:

```text
/app/database/database.sqlite
```

This is only for temporary demo access. It is not safe for commercial production because Railway filesystem data can be lost during redeploys or rebuilds unless a persistent volume is configured.

## If Railway Shows 502

Check the deploy logs for:

- Missing `APP_KEY`
- A temporary APP_KEY warning on every deploy
- PostgreSQL host pointing to `127.0.0.1`
- Migration failures
- Missing PHP extensions
- No process listening on `$PORT`

The app must listen on:

```text
0.0.0.0:${PORT}
```

Do not use `127.0.0.1` or a fixed local port in Railway.
