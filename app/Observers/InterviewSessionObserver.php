<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\InterviewSessionMail;
use App\Models\InterviewSession;
use App\Services\Notification\EmailNotificationService;

class InterviewSessionObserver
{
    /**
     * Handle the InterviewSession "created" event.
     */
    public function created(InterviewSession $session): void
    {
        // 可选：发送会话开始通知
        // 如果用户开启了开始通知设置
        $this->sendStartedNotification($session);
    }

    /**
     * Handle the InterviewSession "updated" event.
     */
    public function updated(InterviewSession $session): void
    {
        // 状态变为完成时发送报告
        if ($session->isDirty('status') && $session->status === 'completed') {
            $this->sendCompletedNotification($session);
        }
    }

    /**
     * 发送开始通知
     */
    private function sendStartedNotification(InterviewSession $session): void
    {
        // 去重保护：已发送过则跳过
        if ($session->started_notif_sent) {
            return;
        }

        $user = $session->user;

        if (! $user || ! $user->email) {
            return;
        }

        $emailService = app(EmailNotificationService::class);

        if ($emailService->sendInterviewSession(
            $user,
            $session,
            InterviewSessionMail::TYPE_STARTED
        )) {
            $session->update(['started_notif_sent' => true]);
        }
    }

    /**
     * 发送完成通知
     */
    private function sendCompletedNotification(InterviewSession $session): void
    {
        // 去重保护：已发送过则跳过
        if ($session->completed_notif_sent) {
            return;
        }

        $user = $session->user;

        if (! $user || ! $user->email) {
            return;
        }

        $emailService = app(EmailNotificationService::class);

        // 准备报告数据
        $reportData = $session->report ?? [];

        // 添加额外统计
        $reportData['question_stats'] = [
            'total' => $session->question_count ?? 0,
            'answered' => $session->answered_count ?? 0,
            'completion_rate' => $session->question_count > 0
                ? round(($session->answered_count / $session->question_count) * 100, 1)
                : 0,
        ];

        if ($emailService->sendInterviewSession(
            $user,
            $session,
            InterviewSessionMail::TYPE_COMPLETED,
            $reportData
        )) {
            $session->update(['completed_notif_sent' => true]);
        }
    }
}
