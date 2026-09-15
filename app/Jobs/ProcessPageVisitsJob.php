<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PageVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 异步批量写入页面访问记录，避免阻塞主请求。
 *
 * 该任务接收一批已组装完成的 PageVisit 数据，
 * 通过批量 insert 写入数据库，失败时记录日志并丢弃（访问统计为非关键数据）。
 */
final class ProcessPageVisitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** 最多重试次数（访问记录非关键数据，重试一次即可） */
    public int $tries = 1;

    /** 任务超时时间（秒） */
    public int $timeout = 30;

    /**
     * @param array<int, array<string, mixed>> $rows 已组装完成的 PageVisit 行数据
     */
    public function __construct(
        private readonly array $rows,
    ) {
        // 使用独立队列，便于按业务分类路由（tracking 队列）
        $this->onQueue('tracking');
    }

    /**
     * 执行批量写入。
     */
    public function handle(): void
    {
        if ($this->rows === []) {
            return;
        }

        try {
            // 分块写入，避免单次 insert 过大导致 MySQL max_allowed_packet 问题
            foreach (array_chunk($this->rows, 200) as $chunk) {
                PageVisit::query()->insert($chunk);
            }
        } catch (\Throwable $e) {
            // 访问统计为非关键数据，仅记录日志，不抛出以避免无限重试
            Log::debug('ProcessPageVisitsJob batch insert failed', [
                'count' => count($this->rows),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
