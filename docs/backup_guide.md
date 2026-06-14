# Backup Guide

IKOMEZA POS includes a logical backup command that writes a compressed database dump, optional public storage archive, manifest, hashes, and a `backup_runs` log record.

## Manual Backup

Admins can run:

```bash
php artisan pos:backup --name=manual-before-maintenance
```

Or use the admin screen:

```text
/backups
```

## Scheduled Backup

The scheduler runs `pos:backup` daily at `BACKUP_DAILY_AT`, default `02:00`.

Production servers must run Laravel scheduler:

```bash
php artisan schedule:work
```

or a cron equivalent:

```cron
* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1
```

## Verification

Verify a backup before trusting it:

```bash
php artisan pos:backup-verify storage/app/backups/backup-folder
```

Failures are logged in `backup_runs` and visible on `/backups`.

