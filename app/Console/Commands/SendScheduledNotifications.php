<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 发送定时通知命令
 */
class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送所有已到时间的定时通知';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('正在检查定时通知...');

        // 获取所有待发送的定时通知
        $notifications = AdminNotification::pendingScheduled()->get();

        if ($notifications->isEmpty()) {
            $this->info('没有需要发送的定时通知。');

            return self::SUCCESS;
        }

        $this->info("找到 {$notifications->count()} 条待发送的定时通知。");

        $successCount = 0;
        $failCount = 0;

        foreach ($notifications as $notification) {
            try {
                $this->info("正在发送通知: {$notification->title}");

                // 派发队列作业
                SendNotificationJob::dispatch($notification);

                $successCount++;
            } catch (\Exception $e) {
                Log::error('定时通知发送失败', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("发送失败: {$notification->title} - {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->info("发送完成：成功 {$successCount} 条，失败 {$failCount} 条");

        return self::SUCCESS;
    }
}
