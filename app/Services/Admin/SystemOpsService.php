<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 系统运维服务 — 从 SystemOpsController 的数据采集方法抽离
 */
final class SystemOpsService
{
    /**
     * 获取系统信息
     */
    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => Application::VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'debug' => config('app.debug') ? '开启' : '关闭',
        ];
    }

    /**
     * 获取队列统计
     */
    public function getQueueStats(): array
    {
        $pendingJobs = 0;
        $failedJobs = 0;

        if (Schema::hasTable('jobs')) {
            $pendingJobs = DB::table('jobs')->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')->count();
        }

        return [
            'pending' => $pendingJobs,
            'failed' => $failedJobs,
            'connections' => config('queue.connections'),
        ];
    }

    /**
     * 获取失败任务列表（最近50条）
     */
    public function getFailedJobs(int $limit = 50): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'queue' => $job->queue,
                'payload' => json_decode($job->payload, true),
                'exception' => $job->exception,
                'failed_at' => $job->failed_at,
                'display_name' => data_get(json_decode($job->payload, true), 'displayName', 'Unknown'),
                'command' => data_get(json_decode($job->payload, true), 'data.commandName', ''),
            ])
            ->toArray();
    }

    /**
     * 获取缓存统计
     */
    public function getCacheStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores')),
        ];
    }

    /**
     * 获取数据库统计
     */
    public function getDatabaseStats(): array
    {
        $connection = config('database.default');
        $tables = [];
        $totalSize = 0;
        $totalRows = 0;

        if ($connection === 'mysql') {
            try {
                $results = DB::select('
                    SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH + INDEX_LENGTH as size
                    FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = ?
                ', [config('database.connections.mysql.database')]);

                foreach ($results as $row) {
                    $tables[] = [
                        'name' => $row->TABLE_NAME,
                        'rows' => $row->TABLE_ROWS,
                        'size' => formatBytes($row->size),
                    ];
                    $totalRows += $row->TABLE_ROWS;
                    $totalSize += $row->size;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to retrieve database statistics', [
                    'connection' => $connection,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'connection' => $connection,
                    'tables' => [],
                    'total_rows' => 0,
                    'total_size' => 'N/A',
                    'error' => '数据库统计信息暂时无法获取',
                ];
            }
        }

        usort($tables, fn ($a, $b) => $b['rows'] <=> $a['rows']);

        return [
            'connection' => $connection,
            'tables' => array_slice($tables, 0, 10),
            'total_rows' => $totalRows,
            'total_size' => formatBytes($totalSize),
        ];
    }

    /**
     * 解析系统日志
     *
     * @return array<int, array{datetime: string|null, environment: string|null, level: string, message: string}>
     */
    public function parseLogs(int $maxLines = 500): array
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];

        if (! file_exists($logFile)) {
            return $logs;
        }

        $content = file_get_contents($logFile);
        $lines = array_reverse(explode("\n", (string) $content));
        $lines = array_slice($lines, 0, $maxLines);
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s+([^.]+)\.(\w+):\s+(.*)$/';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match($pattern, $line, $matches) === 1) {
                $logs[] = [
                    'datetime' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtolower($matches[3]),
                    'message' => $matches[4],
                ];

                continue;
            }

            $logs[] = [
                'datetime' => null,
                'environment' => null,
                'level' => 'info',
                'message' => $line,
            ];
        }

        return $logs;
    }
}
