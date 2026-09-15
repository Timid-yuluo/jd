<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InterviewSession;
use App\Services\Interview\InterviewReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 自动结束超时未活跃的面试会话。
 *
 * 当用户退出面试页面且长时间未回来时，面试会一直卡在 in_progress/paused 状态。
 * 本命令扫描超过指定小时数未活跃的面试，自动标记为完成并生成报告。
 */
final class AutoFinishStaleInterviews extends Command
{
    protected $signature = 'interview:auto-finish-stale
                            {--hours=6 : 超过多少小时未活跃则自动结束}
                            {--limit=100 : 单次最多处理条数}
                            {--dry-run : 仅展示将被处理的面试，不实际执行}';

    protected $description = '自动结束超过指定小时未活跃的面试会话（默认6小时）';

    public function handle(InterviewReportService $reportService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours);

        // 查找所有 in_progress 或 paused 且 updated_at 超过阈值的面试
        $staleSessions = InterviewSession::query()
            ->whereIn('status', [InterviewSession::STATUS_IN_PROGRESS, InterviewSession::STATUS_PAUSED])
            ->where('updated_at', '<=', $cutoff)
            ->limit($limit)
            ->get();

        if ($staleSessions->isEmpty()) {
            $this->info('没有需要处理的超时面试。');

            return self::SUCCESS;
        }

        $this->info(sprintf('找到 %d 场超时面试（超过 %d 小时未活跃）', $staleSessions->count(), $hours));

        $finished = 0;
        $skipped = 0;

        foreach ($staleSessions as $session) {
            // 双重检查：如果 heartbeat 缓存存在且在阈值内，跳过
            $heartbeatKey = "interview:heartbeat:{$session->id}";
            $lastActiveTs = Cache::get($heartbeatKey);

            if (is_numeric($lastActiveTs) && (time() - (int) $lastActiveTs) < ($hours * 3600)) {
                $skipped++;
                $this->line("  [跳过] 面试 #{$session->id} — heartbeat 仍在有效期内");
                continue;
            }

            if ($dryRun) {
                $this->line("  [模拟] 将结束面试 #{$session->id} (用户: {$session->user_id}, 状态: {$session->status})");
                $finished++;
                continue;
            }

            try {
                // 更新已回答题数
                $answeredCount = (int) $session->questions()->whereNotNull('answer')->count();
                $session->forceFill([
                    'status' => InterviewSession::STATUS_COMPLETED,
                    'answered_count' => $answeredCount,
                ])->save();

                // 生成面试报告
                $reportService->persistInterviewReport($session->fresh());

                // 清除 heartbeat 缓存
                Cache::forget($heartbeatKey);

                $finished++;
                $this->line("  [完成] 面试 #{$session->id} 已自动结束，已答 {$answeredCount} 题");

                Log::info('interview_auto_finished_stale', [
                    'interview_id' => $session->id,
                    'user_id' => $session->user_id,
                    'previous_status' => $session->status,
                    'answered_count' => $answeredCount,
                    'stale_hours' => $hours,
                ]);
            } catch (\Throwable $e) {
                $this->error("  [错误] 面试 #{$session->id} 处理失败: {$e->getMessage()}");
                Log::error('interview_auto_finish_stale_error', [
                    'interview_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('处理完毕：结束 %d 场，跳过 %d 场', $finished, $skipped));

        return self::SUCCESS;
    }
}
