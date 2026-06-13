<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\BackupRun;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:backup {--name=}', function () {
    if (config('database.default') !== 'sqlite') {
        $this->warn('Automated file backup currently supports SQLite. Use managed database snapshots for MySQL/PostgreSQL.');

        return 1;
    }

    $database = database_path('database.sqlite');

    if (!is_file($database)) {
        $this->error('SQLite database file was not found at ' . $database);

        return 1;
    }

    $backupDir = storage_path('app/backups');

    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }

    $name = $this->option('name') ?: 'backup-' . now()->format('Ymd-His') . '.sqlite';
    $path = $backupDir . DIRECTORY_SEPARATOR . $name;

    copy($database, $path);

    if (Schema::hasTable('backup_runs')) {
        BackupRun::create([
            'backup_name' => $name,
            'path' => $path,
            'size_bytes' => filesize($path) ?: 0,
            'status' => 'COMPLETED',
            'notes' => 'Created by pos:backup',
        ]);
    }

    $this->info('Backup created: ' . $path);

    return 0;
})->purpose('Create a safe SQLite database backup for POS disaster recovery');

Artisan::command('pos:restore {path} {--force}', function (string $path) {
    if (config('database.default') !== 'sqlite') {
        $this->warn('Restore command currently supports SQLite only.');

        return 1;
    }

    if (!$this->option('force')) {
        $this->error('Restore is destructive. Re-run with --force after taking a fresh backup.');

        return 1;
    }

    if (!is_file($path)) {
        $this->error('Backup file not found: ' . $path);

        return 1;
    }

    $database = database_path('database.sqlite');
    DB::disconnect();
    copy($path, $database);

    $this->info('Database restored from: ' . $path);

    return 0;
})->purpose('Restore a SQLite POS database backup when explicitly forced');
