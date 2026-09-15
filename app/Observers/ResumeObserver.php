<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Resume;
use App\Services\Notification\EmailNotificationService;
use Illuminate\Support\Facades\Cache;

class ResumeObserver
{
    /**
     * Handle the Resume "created" event.
     */
    public function created(Resume $resume): void
    {
        $this->clearStatsCache($resume->user_id);
    }

    /**
     * Handle the Resume "updated" event.
     */
    public function updated(Resume $resume): void
    {
        $this->clearStatsCache($resume->user_id);

        // 检查是否是优化完成（ATS评分从无到有或有显著提升）
        if ($resume->isDirty('ats_score') || $resume->isDirty('optimized_text')) {
            $oldScore = $resume->getOriginal('ats_score') ?? 0;
            $newScore = $resume->ats_score ?? 0;

            // ATS评分从无变有，或有显著提升时发送通知
            if (($oldScore === 0 || $oldScore === null) && $newScore > 0) {
                $this->sendCompletedNotification($resume);
            }
        }
    }

    /**
     * Handle the Resume "deleted" event.
     */
    public function deleted(Resume $resume): void
    {
        $this->clearStatsCache($resume->user_id);
    }

    /**
     * Handle the Resume "restored" event.
     */
    public function restored(Resume $resume): void
    {
        $this->clearStatsCache($resume->user_id);
    }

    /**
     * 清除用户简历统计缓存
     */
    private function clearStatsCache(int $userId): void
    {
        Cache::forget("resume_stats:{$userId}");
    }

    /**
     * 发送优化完成通知
     */
    private function sendCompletedNotification(Resume $resume): void
    {
        $user = $resume->user;

        if (! $user || ! $user->email) {
            return;
        }

        $emailService = app(EmailNotificationService::class);

        // 准备统计数据
        $stats = [];

        if ($resume->highlights) {
            $stats['亮点数量'] = count($resume->highlights);
        }

        if ($resume->content_structured) {
            $stats['简历模块'] = count($resume->content_structured);
        }

        $emailService->sendResumeCompleted($user, $resume, $stats);
    }
}
