<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ScheduleLog;
use App\Models\UserActionLog;
use Illuminate\Console\Command;

/**
 * 清理过期的定时任务日志和用户操作日志。
 *
 * schedule_logs 表增长较快（每分钟健康检查等任务都会写入），需要定期清理。
 * user_action_logs 同样需要保留期管理。
 */
final class PurgeSystemLogs extends Command
{
    protected $signature = 'purge:system-logs
                            {--schedule-days=30 : 定时任务日志保留天数}
                            {--action-days=90 : 用户操作日志保留天数}
                            {--limit=5000 : 单次最多删除条数}';

    protected $description = '清理过期的定时任务日志和用户操作日志';

    public function handle(): int
    {
        $scheduleDays = max(7, (int) $this->option('schedule-days'));
        $actionDays = max(30, (int) $this->option('action-days'));
        $limit = max(100, (int) $this->option('limit'));

        // 清理定时任务日志
        $scheduleCutoff = now()->subDays($scheduleDays);
        $scheduleDeleted = ScheduleLog::query()
            ->where('created_at', '<', $scheduleCutoff)
            ->limit($limit)
            ->delete();

        $this->info("已清理 {$scheduleDeleted} 条超过 {$scheduleDays} 天的定时任务日志");

        // 清理用户操作日志
        $actionCutoff = now()->subDays($actionDays);
        $actionDeleted = UserActionLog::query()
            ->where('created_at', '<', $actionCutoff)
            ->limit($limit)
            ->delete();

        $this->info("已清理 {$actionDeleted} 条超过 {$actionDays} 天的用户操作日志");

        return self::SUCCESS;
    }
}
