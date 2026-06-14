# Admin Manual

## Admin Responsibilities

- Manage users, roles, permissions, branches, products, stores, taxes, backups, and audit logs.
- Review unmatched payments on `/payments/reconciliation`.
- Review backups on `/backups`.
- Review system errors on `/system/errors`.
- Keep `ALLOW_PRODUCTION_SEEDING=false` in production.

## Branch Controls

Every operational query should be branch-scoped. Admin can view all branches, but reports should be filtered to one branch unless the purpose is explicitly group-level reporting.

## Production Do Not Do

- Do not run `php artisan migrate:fresh` on production.
- Do not enable `RUN_DEMO_SEEDERS` in production.
- Do not use SQLite in production unless explicitly accepting emergency/demo risk with `ALLOW_SQLITE_PRODUCTION=true`.

