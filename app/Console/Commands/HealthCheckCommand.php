<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

final class HealthCheckCommand extends Command
{
    protected $signature = 'health:check
                            {--threshold=100 : 队列积压告警阈值}
                            {--silent : 仅在异常时输出}';

    protected $description = '生产环境健康检查（数据库/Redis/队列/存储）';

    private bool $hasError = false;

    public function handle(): int
    {
        $silent = (bool) $this->option('silent');
        $results = [
            'database' => $this->checkDatabase($silent),
            'redis' => $this->checkRedis($silent),
            'queue' => $this->checkQueue($silent),
            'storage' => $this->checkStorage($silent),
        ];

        if (! $this->hasError) {
            if (! $silent) {
                $this->info('✅ 所有健康检查通过');
            }

            return self::SUCCESS;
        }

        $this->error('❌ 健康检查发现异常');
        $this->sendAlertIfNeeded($results);

        return self::FAILURE;
    }

    private function checkDatabase(bool $silent): bool
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($latency > 500) {
                $this->hasError = true;
                if (! $silent) {
                    $this->warn("⚠️  数据库连接延迟过高: {$latency}ms");
                }

                return false;
            }

            if (! $silent) {
                $this->info("✅ 数据库连接正常 ({$latency}ms)");
            }

            return true;
        } catch (\Throwable $e) {
            $this->hasError = true;
            if (! $silent) {
                $this->error("❌ 数据库连接失败: {$e->getMessage()}");
            }

            return false;
        }
    }

    private function checkRedis(bool $silent): bool
    {
        try {
            $start = microtime(true);
            $key = 'health:check:'.time();
            Cache::put($key, true, 10);
            $result = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($result !== true) {
                $this->hasError = true;
                if (! $silent) {
                    $this->error('❌ Redis 读写不一致');
                }

                return false;
            }

            if ($latency > 200) {
                $this->hasError = true;
                if (! $silent) {
                    $this->warn("⚠️  Redis 延迟过高: {$latency}ms");
                }

                return false;
            }

            if (! $silent) {
                $this->info("✅ Redis 连接正常 ({$latency}ms)");
            }

            return true;
        } catch (\Throwable $e) {
            $this->hasError = true;
            if (! $silent) {
                $this->error("❌ Redis 连接失败: {$e->getMessage()}");
            }

            return false;
        }
    }

    private function checkQueue(bool $silent): bool
    {
        try {
            $threshold = (int) $this->option('threshold');
            $connection = config('queue.default', 'sync');

            if ($connection === 'sync') {
                if (! $silent) {
                    $this->info('⏭️  队列使用 sync 驱动，跳过积压检查');
                }

                return true;
            }

            $pendingCount = 0;
            $failedCount = 0;

            if ($connection === 'redis') {
                $queueNames = ['default', 'resume-export', 'interview-eval'];
                foreach ($queueNames as $queue) {
                    $size = Redis::connection()->llen("queues:{$queue}");
                    $pendingCount += $size;
                }

                $failedCount = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            }

            $healthy = true;

            if ($pendingCount > $threshold) {
                $this->hasError = true;
                $healthy = false;
                if (! $silent) {
                    $this->warn("⚠️  队列积压严重: {$pendingCount} 任务等待处理 (阈值: {$threshold})");
                }
            }

            if ($failedCount > 10) {
                $this->hasError = true;
                $healthy = false;
                if (! $silent) {
                    $this->warn("⚠️  近 24 小时失败任务: {$failedCount} 条");
                }
            }

            if ($healthy && ! $silent) {
                $this->info("✅ 队列状态正常 (待处理: {$pendingCount}, 近24h失败: {$failedCount})");
            }

            return $healthy;
        } catch (\Throwable $e) {
            $this->hasError = true;
            if (! $silent) {
                $this->error("❌ 队列检查失败: {$e->getMessage()}");
            }

            return false;
        }
    }

    private function checkStorage(bool $silent): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.default', 'local'));
            $testKey = 'health_check_'.time();
            $disk->put($testKey, 'ok');

            if ($disk->get($testKey) !== 'ok') {
                $this->hasError = true;
                if (! $silent) {
                    $this->error('❌ 存储读写不一致');
                }

                return false;
            }

            $disk->delete($testKey);

            if (! $silent) {
                $this->info('✅ 存储读写正常');
            }

            return true;
        } catch (\Throwable $e) {
            $this->hasError = true;
            if (! $silent) {
                $this->error("❌ 存储检查失败: {$e->getMessage()}");
            }

            return false;
        }
    }

    private function sendAlertIfNeeded(array $results): void
    {
        $throttleKey = 'health-check-alert';
        if (Cache::has($throttleKey)) {
            return;
        }

        $failures = [];
        foreach ($results as $component => $healthy) {
            if (! $healthy) {
                $failures[] = $component;
            }
        }

        if ($failures === []) {
            return;
        }

        Cache::put($throttleKey, true, now()->addMinutes(30));

        Log::alert('Health check failed', ['components' => $failures]);

        $recipients = config('exception-email.recipients', []);
        if (empty($recipients)) {
            return;
        }

        try {
            Notification::route('mail', $recipients)->notify(
                new \App\Notifications\ExceptionNotification(
                    new \RuntimeException('健康检查异常：'.implode(', ', $failures)),
                    ['component' => 'health-check', 'failures' => $failures]
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send health check alert', ['error' => $e->getMessage()]);
        }
    }
}
