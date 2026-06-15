<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:prepare-runtime', function () {
    foreach ([
        storage_path('app'),
        storage_path('framework/cache'),
        storage_path('framework/sessions'),
        storage_path('framework/views'),
        storage_path('logs'),
        database_path(),
    ] as $directory) {
        File::ensureDirectoryExists($directory, 0775, true);
    }

    if (config('database.default') === 'sqlite') {
        $database = (string) config('database.connections.sqlite.database');

        if ($database !== '' && $database !== ':memory:') {
            File::ensureDirectoryExists(dirname($database), 0775, true);

            if (!File::exists($database)) {
                File::put($database, '');
                $this->warn('Created missing SQLite database file: ' . $database);
            }
        }
    }

    $this->info('Runtime directories and database path are ready.');

    return 0;
})->purpose('Prepare writable runtime paths before Railway/LAN startup');

Artisan::command('pos:production-preflight {--warn-only}', function () {
    if (!app()->environment('production')) {
        $this->info('Production preflight skipped outside production.');

        return 0;
    }

    $warnOnly = (bool) $this->option('warn-only');
    $failed = false;
    $fail = function (string $message, bool $fatal = false) use (&$failed, $warnOnly): void {
        $failed = true;

        if ($warnOnly && !$fatal) {
            $this->warn($message);

            return;
        }

        $this->error($message);
    };

    $connection = config('database.default');

    if ($connection === 'sqlite' && !filter_var(env('ALLOW_SQLITE_PRODUCTION', false), FILTER_VALIDATE_BOOL)) {
        $fail('Production is using SQLite. Configure PostgreSQL/MySQL for persistent data, or set ALLOW_SQLITE_PRODUCTION=true only for an explicit temporary exception.');
    }

    if (in_array($connection, ['pgsql', 'mysql', 'mariadb'], true)) {
        $database = config('database.connections.' . $connection);
        $host = $database['host'] ?? null;
        $url = $database['url'] ?? null;

        if (blank($url) && in_array($host, ['127.0.0.1', 'localhost', null], true) && !filter_var(env('ALLOW_LOCAL_DB_HOST_PRODUCTION', false), FILTER_VALIDATE_BOOL)) {
            $fail('Production database points to localhost. Attach a managed database and set DATABASE_URL/PGHOST or DB_HOST.');
        }
    }

    if (blank(config('app.key'))) {
        $fail('APP_KEY is missing.', true);
    }

    if (filter_var(env('ENABLE_DEMO_ACCOUNTS', false), FILTER_VALIDATE_BOOL)) {
        $fail('ENABLE_DEMO_ACCOUNTS must be false in production.');
    }

    if (filter_var(env('RUN_DEMO_SEEDERS', false), FILTER_VALIDATE_BOOL)) {
        $fail('RUN_DEMO_SEEDERS must be false in production.');
    }

    if ($failed && !$warnOnly) {
        return 1;
    }

    $failed
        ? $this->warn('Production preflight completed with warnings.')
        : $this->info('Production preflight passed.');

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
