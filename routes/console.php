<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:production-preflight', function () {
    if (!app()->environment('production')) {
        $this->info('Production preflight skipped outside production.');

        return 0;
    }

    $connection = config('database.default');

    if ($connection === 'sqlite' && !filter_var(env('ALLOW_SQLITE_PRODUCTION', false), FILTER_VALIDATE_BOOL)) {
        $this->error('Production is using SQLite. Configure PostgreSQL/MySQL or set ALLOW_SQLITE_PRODUCTION=true only for an explicit temporary exception.');

        return 1;
    }

    if (in_array($connection, ['pgsql', 'mysql', 'mariadb'], true)) {
        $database = config('database.connections.' . $connection);
        $host = $database['host'] ?? null;
        $url = $database['url'] ?? null;

        if (blank($url) && in_array($host, ['127.0.0.1', 'localhost', null], true) && !filter_var(env('ALLOW_LOCAL_DB_HOST_PRODUCTION', false), FILTER_VALIDATE_BOOL)) {
            $this->error('Production database points to localhost. Attach a managed database and set DATABASE_URL/PGHOST or DB_HOST.');

            return 1;
        }
    }

    if (blank(config('app.key'))) {
        $this->error('APP_KEY is missing.');

        return 1;
    }

    if (filter_var(env('ENABLE_DEMO_ACCOUNTS', false), FILTER_VALIDATE_BOOL)) {
        $this->error('ENABLE_DEMO_ACCOUNTS must be false in production.');

        return 1;
    }

    if (filter_var(env('RUN_DEMO_SEEDERS', false), FILTER_VALIDATE_BOOL)) {
        $this->error('RUN_DEMO_SEEDERS must be false in production.');

        return 1;
    }

    $this->info('Production preflight passed.');

    return 0;
})->purpose('Block unsafe production startup settings before serving IKOMEZA POS');

Artisan::command('pos:backup {--name=} {--created-by=}', function (BackupService $backupService) {
    $run = $backupService->create(
        name: $this->option('name'),
        createdBy: $this->option('created-by') ? (int) $this->option('created-by') : null
    );

    if ($run->status !== 'COMPLETED') {
        $this->error('Backup failed: ' . $run->notes);

        return 1;
    }

    $this->info('Backup created: ' . $run->path);

    return 0;
})->purpose('Create a verified POS database and storage backup');

Artisan::command('pos:backup-verify {path}', function (string $path, BackupService $backupService) {
    $result = $backupService->verify($path);

    if (!$result['ok']) {
        $this->error($result['message']);

        return 1;
    }

    $this->info($result['message']);

    return 0;
})->purpose('Verify a POS backup manifest and file hashes');

Artisan::command('pos:restore {path} {--force}', function (string $path, BackupService $backupService) {
    if (!$this->option('force')) {
        $this->error('Restore is destructive. Re-run with --force after taking a fresh backup.');

        return 1;
    }

    if (app()->environment('production') && !filter_var(env('ALLOW_PRODUCTION_RESTORE', false), FILTER_VALIDATE_BOOL)) {
        $this->error('Production restore is blocked. Set ALLOW_PRODUCTION_RESTORE=true only during a planned recovery window.');

        return 1;
    }

    $result = $backupService->restore($path);

    if (!$result['ok']) {
        $this->error($result['message']);

        return 1;
    }

    $this->info($result['message']);

    return 0;
})->purpose('Restore a verified POS logical database backup with explicit force protection');

Schedule::command('pos:backup')
    ->dailyAt(env('BACKUP_DAILY_AT', '02:00'))
    ->withoutOverlapping()
    ->onFailure(function () {
        logger()->error('Scheduled IKOMEZA POS backup failed.');
    });
