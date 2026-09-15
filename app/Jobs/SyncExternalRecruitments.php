<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 异步同步外部招聘信息
 *
 * 职责：在后台执行 scrape 命令，避免 HTTP 请求超时
 */
final class SyncExternalRecruitments implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** 任务超时时间（秒）：抓取任务可能耗时较长 */
    public int $timeout = 1800;

    public function __construct(
        private readonly string $source,
        private readonly int $pages,
        private readonly int $days,
        private readonly int $scrapeDelay,
        private readonly bool $autoApprove,
    ) {}

    public function handle(): void
    {
        $exitCode = 0;

        if ($this->source === 'qiuzhifangzhou-campus') {
            $exitCode = Artisan::call('scrape:qiuzhifangzhou-campus', [
                '--days' => $this->days,
                '--auto-approve' => $this->autoApprove ? 1 : 0,
            ]);
        } elseif ($this->source === 'qiuzhifangzhou-position') {
            $exitCode = Artisan::call('scrape:qiuzhifangzhou-position', [
                '--auto-approve' => $this->autoApprove ? 1 : 0,
            ]);
        } else {
            $exitCode = Artisan::call('scrape:offerstar', [
                '--page' => 1,
                '--pages' => $this->pages,
                '--delay' => $this->scrapeDelay,
                '--auto-approve' => $this->autoApprove ? 1 : 0,
            ]);
        }

        $output = Artisan::output();

        Log::info('外部招聘同步任务完成', [
            'source' => $this->source,
            'exit_code' => $exitCode,
            'output' => $output,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('外部招聘同步任务失败', [
            'source' => $this->source,
            'error' => $exception->getMessage(),
        ]);
    }
}
