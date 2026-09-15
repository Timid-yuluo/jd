<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 订阅即将到期提醒通知
 */
class SubscriptionExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? '会员';
        $expiresAt = $this->subscription->expires_at?->format('Y-m-d H:i') ?? '未知';

        return (new MailMessage)
            ->subject("你的{$planName}即将到期 — 还剩{$this->daysLeft}天")
            ->greeting("你好，{$notifiable->name}！")
            ->line("你的 **{$planName}** 将在 **{$this->daysLeft}天** 后到期（到期时间：{$expiresAt}）。")
            ->line('到期后你的账号将降级为免费版，以下功能将受到限制：')
            ->line('- AI 简历优化次数减少')
            ->line('- ATS 评分每日限额降低')
            ->line('- 模拟面试额度减少')
            ->line('- JD 定制题目不可用')
            ->action('立即续费', route('user.membership.pricing'))
            ->line('及时续费可保持所有功能不受影响，感谢你的使用！');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $planName = $this->subscription->plan?->name ?? '会员';

        return [
            'type' => 'subscription_expiring',
            'title' => '订阅即将到期',
            'body' => "你的{$planName}将在{$this->daysLeft}天后到期，请及时续费。",
            'subscription_id' => $this->subscription->id,
            'days_left' => $this->daysLeft,
        ];
    }
}
