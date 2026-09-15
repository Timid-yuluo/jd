<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LoginHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 异步写入登录历史记录（避免阻塞主请求）
 */
final class RecordLoginHistoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string, mixed> 登录记录数据 */
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        // 登录历史为非关键数据，归入 tracking 队列避免影响业务
        $this->onQueue('tracking');
    }

    public function handle(): void
    {
        LoginHistory::create([
            'user_id' => $this->data['user_id'],
            'ip_address' => $this->data['ip_address'],
            'user_agent' => $this->data['user_agent'],
            'device_type' => $this->data['device_type'],
            'browser' => $this->data['browser'],
            'os' => $this->data['os'],
            'location' => null,
            'is_success' => $this->data['is_success'],
            'event_type' => $this->data['event_type'],
            'created_at' => now(),
        ]);

        // 清理旧记录
        if (($this->data['cleanup'] ?? false) === true) {
            $this->cleanupOldRecords((int) $this->data['user_id']);
        }
    }

    /**
     * 保留最近 N 条记录，删除其余
     */
    private function cleanupOldRecords(int $userId): void
    {
        $idsToKeep = LoginHistory::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit((int) config('ui.limit.login_history_list', 10))
            ->pluck('id');

        LoginHistory::query()
            ->where('user_id', $userId)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
