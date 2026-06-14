<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class BackupService
{
    private const EXCLUDED_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'jobs',
        'job_batches',
        'sessions',
    ];

    public function create(?string $name = null, ?int $createdBy = null): BackupRun
    {
        $startedAt = now();
        $name = $this->normalizeBackupName($name);
        $directory = storage_path('app/backups/' . $name);

        File::ensureDirectoryExists($directory, 0775, true);

        $run = BackupRun::create([
            'backup_name' => $name,
            'path' => $directory,
            'size_bytes' => 0,
            'status' => 'RUNNING',
            'created_by' => $createdBy,
            'notes' => 'Backup started at ' . $startedAt->toDateTimeString(),
        ]);

        try {
            $databasePath = $directory . DIRECTORY_SEPARATOR . 'database.ndjson.gz';
            $databaseHash = $this->writeDatabaseBackup($databasePath);
            $storage = $this->writeStorageBackup($directory);

            $manifest = [
                'backup_name' => $name,
                'app' => config('app.name'),
                'environment' => app()->environment(),
                'database_connection' => config('database.default'),
                'created_at' => $startedAt->toIso8601String(),
                'database' => [
                    'file' => 'database.ndjson.gz',
                    'sha256' => $databaseHash,
                ],
                'storage' => $storage,
                'restore_warning' => 'Restore is destructive. Verify first and run during a maintenance window.',
            ];

            File::put(
                $directory . DIRECTORY_SEPARATOR . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $size = collect(File::allFiles($directory))
                ->sum(fn ($file) => $file->getSize());

            $run->update([
                'size_bytes' => $size,
                'status' => 'COMPLETED',
                'notes' => 'Backup completed and manifest generated.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            $run->update([
                'status' => 'FAILED',
                'notes' => $exception->getMessage(),
            ]);
        }

        return $run->refresh();
    }

    public function verify(string $path): array
    {
        $directory = $this->resolveBackupDirectory($path);
        $manifestPath = $directory . DIRECTORY_SEPARATOR . 'manifest.json';

        if (!is_file($manifestPath)) {
            return ['ok' => false, 'message' => 'Backup manifest not found: ' . $manifestPath];
        }

        $manifest = json_decode((string) File::get($manifestPath), true);

        if (!is_array($manifest)) {
            return ['ok' => false, 'message' => 'Backup manifest is invalid JSON.'];
        }

        $databaseFile = $directory . DIRECTORY_SEPARATOR . ($manifest['database']['file'] ?? 'database.ndjson.gz');

        if (!is_file($databaseFile)) {
            return ['ok' => false, 'message' => 'Database backup file is missing.'];
        }

        $expectedHash = $manifest['database']['sha256'] ?? null;
        $actualHash = hash_file('sha256', $databaseFile);

        if (!$expectedHash || !hash_equals($expectedHash, $actualHash)) {
            return ['ok' => false, 'message' => 'Database backup hash verification failed.'];
        }

        return ['ok' => true, 'message' => 'Backup verified successfully: ' . $directory];
    }

    public function restore(string $path): array
    {
        $verification = $this->verify($path);

        if (!$verification['ok']) {
            return $verification;
        }

        $directory = $this->resolveBackupDirectory($path);
        $manifest = json_decode((string) File::get($directory . DIRECTORY_SEPARATOR . 'manifest.json'), true);
        $databaseFile = $directory . DIRECTORY_SEPARATOR . ($manifest['database']['file'] ?? 'database.ndjson.gz');
        $tables = $this->readDatabaseBackup($databaseFile);

        DB::transaction(function () use ($tables) {
            Schema::disableForeignKeyConstraints();

            try {
                foreach (array_reverse(array_keys($tables)) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                foreach ($tables as $table => $rows) {
                    if (!Schema::hasTable($table) || $rows === []) {
                        continue;
                    }

                    foreach (array_chunk($rows, 250) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }

                    $this->resetIdentity($table);
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        return ['ok' => true, 'message' => 'Backup restored successfully from: ' . $directory];
    }

    private function writeDatabaseBackup(string $path): string
    {
        $handle = gzopen($path, 'wb9');

        if (!$handle) {
            throw new \RuntimeException('Unable to open database backup file for writing.');
        }

        foreach ($this->tableNames() as $table) {
            DB::table($table)
                ->orderBy($this->tableHasColumn($table, 'id') ? 'id' : $this->firstColumn($table))
                ->chunk(500, function ($rows) use ($handle, $table) {
                    gzwrite($handle, json_encode([
                        'table' => $table,
                        'rows' => $rows->map(fn ($row) => (array) $row)->all(),
                    ], JSON_UNESCAPED_SLASHES) . "\n");
                });
        }

        gzclose($handle);

        return hash_file('sha256', $path);
    }

    private function readDatabaseBackup(string $path): array
    {
        $handle = gzopen($path, 'rb');

        if (!$handle) {
            throw new \RuntimeException('Unable to open database backup file for reading.');
        }

        $tables = [];

        while (!gzeof($handle)) {
            $line = trim((string) gzgets($handle));

            if ($line === '') {
                continue;
            }

            $entry = json_decode($line, true);

            if (!is_array($entry) || empty($entry['table']) || !isset($entry['rows'])) {
                throw new \RuntimeException('Database backup contains an invalid record.');
            }

            $tables[$entry['table']] ??= [];
            $tables[$entry['table']] = array_merge($tables[$entry['table']], $entry['rows']);
        }

        gzclose($handle);

        return $tables;
    }

    private function writeStorageBackup(string $directory): array
    {
        $source = storage_path('app/public');

        if (!is_dir($source)) {
            return ['status' => 'SKIPPED', 'reason' => 'storage/app/public does not exist'];
        }

        if (!class_exists(ZipArchive::class)) {
            return ['status' => 'SKIPPED', 'reason' => 'ZipArchive PHP extension is not installed'];
        }

        $zipPath = $directory . DIRECTORY_SEPARATOR . 'storage-public.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create storage backup archive.');
        }

        $fileCount = 0;

        foreach (File::allFiles($source) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getPathname(), $relative);
            $fileCount++;
        }

        $zip->close();

        if ($fileCount === 0 || !is_file($zipPath)) {
            if (is_file($zipPath)) {
                File::delete($zipPath);
            }

            return ['status' => 'SKIPPED', 'reason' => 'storage/app/public has no files to archive'];
        }

        return [
            'status' => 'COMPLETED',
            'file' => 'storage-public.zip',
            'sha256' => hash_file('sha256', $zipPath),
        ];
    }

    private function tableNames(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn ($table) => in_array($table, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function firstColumn(string $table): string
    {
        $columns = Schema::getColumns($table);

        return $columns[0]['name'] ?? 'created_at';
    }

    private function resetIdentity(string $table): void
    {
        if (!Schema::hasColumn($table, 'id')) {
            return;
        }

        $maxId = (int) DB::table($table)->max('id');
        $driver = DB::getDriverName();

        try {
            if ($driver === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), ?, true)", [max($maxId, 1)]);
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` AUTO_INCREMENT = ' . max($maxId + 1, 1));
            } elseif ($driver === 'sqlite') {
                DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $maxId]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function normalizeBackupName(?string $name): string
    {
        $name = $name ?: 'backup-' . now()->format('Ymd-His');

        return preg_replace('/[^A-Za-z0-9_.-]/', '-', $name) ?: 'backup-' . now()->format('Ymd-His');
    }

    private function resolveBackupDirectory(string $path): string
    {
        if (is_dir($path)) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        $candidate = storage_path('app/backups/' . trim($path, '\\/'));

        return is_dir($candidate) ? $candidate : rtrim($path, DIRECTORY_SEPARATOR);
    }
}
