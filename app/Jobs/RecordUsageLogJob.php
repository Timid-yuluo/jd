<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UsageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 异步记录 AI 使用日志，避免阻塞主请求
 */
final class RecordUsageLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        // 使用日志为非关键数据，归入 tracking 队列避免影响业务
        $this->onQueue('tracking');
    }

    public function handle(): void
    {
        UsageLog::query()->create($this->data);
    }
}
