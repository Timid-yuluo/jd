<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Feedback;
use App\Models\FeedbackReward;
use App\Models\UserCredit;
use App\Models\UserNotification;

final class UserNotificationService
{
    public function createSiteNotification(
        int $userId,
        string $title,
        string $content,
        string $type = 'system',
        string $channel = 'site',
    ): UserNotification {
        $notification = UserNotification::query()->create([
            'user_id' => $userId,
            'admin_notification_id' => null,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'channel' => $channel,
            'read_at' => null,
        ]);

        UserNotification::clearUnreadCache($userId);

        return $notification;
    }

    public function sendFeedbackRewardGranted(Feedback $feedback, FeedbackReward $reward): UserNotification
    {
        $quotaLabel = UserCredit::quotaLabel($reward->quota_key);
        $validityText = $reward->validity_days && $reward->validity_days > 0
            ? '，有效期 '.$reward->validity_days.' 天'
            : '';
        $detailUrl = route('feedback.show', ['feedback' => $feedback->id]);
        $creditsUrl = route('user.membership.credits');

        $title = '反馈奖励已到账';
        $content = sprintf(
            '你提交的反馈《%s》已被采纳，系统已赠送%s %d 次%s。<br><a href="%s">查看该反馈</a> 或 <a href="%s">查看我的次卡</a>。',
            e($feedback->title),
            e($quotaLabel),
            $reward->credits,
            e($validityText),
            e($detailUrl),
            e($creditsUrl),
        );

        return $this->createSiteNotification(
            (int) $feedback->user_id,
            $title,
            $content,
            'feature',
            'site',
        );
    }
}
