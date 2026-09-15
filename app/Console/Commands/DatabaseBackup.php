<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

final class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--disk=local : Storage disk for backup}
                            {--keep=7 : Number of recent backups to retain}';

    protected $description = 'Backup the database using mysqldump';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($config['driver'] !== 'mysql') {
            $this->warn("Database driver '{$config['driver']}' is not supported for mysqldump backup.");

            return self::SUCCESS;
        }

        $backupDir = storage_path('backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = sprintf(
            '%s_%s.sql.gz',
            $config['database'],
            now()->format('Ymd_His')
        );
        $filepath = "{$backupDir}/{$filename}";

        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --user=%s --host=%s --port=%s --single-transaction --quick --lock-tables=false %s | gzip > %s',
            escapeshellarg((string) $config['password']),
            escapeshellarg((string) $config['username']),
            escapeshellarg((string) ($config['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($config['port'] ?? '3306')),
            escapeshellarg((string) $config['database']),
            escapeshellarg($filepath)
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            Log::error('Database backup failed', [
                'error' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ]);
            $this->error('Database backup failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $this->info("Database backup created: {$filename}");
        Log::info('Database backup created', ['file' => $filename]);

        $this->pruneOldBackups($backupDir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $backupDir, int $keep): void
    {
        $files = glob("{$backupDir}/*.sql.gz");
        if ($files === false || count($files) <= $keep) {
            return;
        }

        usort($files, fn (string $a, string $b): int => filemtime($a) <=> filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $keep);
        foreach ($toDelete as $file) {
            unlink($file);
            $this->line('Pruned old backup: '.basename($file));
        }
    }
}
