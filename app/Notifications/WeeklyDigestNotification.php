<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 每周求职进度邮件摘要
 */
final class WeeklyDigestNotification extends Notification
{
    use Queueable;

    /**
     * @param array{resume_count:int,interview_count:int,application_count:int,upcoming_interviews:int,match_count:int} $stats
     */
    public function __construct(
        private readonly array $stats,
        private readonly string $weekRange,
    ) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->stats;

        return (new MailMessage)
            ->subject("本周求职摘要（{$this->weekRange}）")
            ->greeting("你好，{$notifiable->name}！")
            ->line("以下是你的本周求职进度摘要：")
            ->line("- 简历数量：{$s['resume_count']} 份")
            ->line("- 模拟面试：{$s['interview_count']} 场")
            ->line("- 投递记录：{$s['application_count']} 条")
            ->line("- 岗位匹配：{$s['match_count']} 次")
            ->when($s['upcoming_interviews'] > 0, fn (MailMessage $mail) => $mail
                ->line("- 即将面试：{$s['upcoming_interviews']} 场，请做好准备！")
            )
            ->action('查看求职看板', route('user.dashboard'))
            ->line('继续加油，Offer 就在前方！');
    }
}
