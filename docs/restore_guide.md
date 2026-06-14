# Restore Guide

Restore is intentionally guarded because it replaces live data.

## Restore Safety Rules

- Always create a fresh backup before restore.
- Verify the backup manifest before restore.
- Stop POS users from transacting during restore.
- Production restore requires `ALLOW_PRODUCTION_RESTORE=true`.
- Run restore during a maintenance window only.

## Restore Command

```bash
php artisan pos:backup-verify storage/app/backups/backup-folder
php artisan pos:restore storage/app/backups/backup-folder --force
php artisan migrate --force
php artisan pos:backup --name=post-restore-checkpoint
```

## Post-Restore Checks

- Login as admin.
- Open dashboard.
- Verify products, stock, recent sales, payments, shifts, and audit logs.
- Print one receipt from an existing sale.
- Run `/reports/tax` for current month.
- Confirm branch filters still isolate data.

