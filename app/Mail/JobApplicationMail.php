<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 职位申请相关邮件
 */
class JobApplicationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const TYPE_APPLIED = 'applied';

    public const TYPE_STATUS_UPDATE = 'status_update';

    public const TYPE_DEADLINE_REMINDER = 'deadline_reminder';

    public const TYPE_SUGGESTION = 'suggestion';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public JobApplication $application,
        public string $type = self::TYPE_APPLIED,
        public ?string $oldStatus = null,
        public array $suggestions = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            self::TYPE_APPLIED => '✅ 职位申请已记录 - '.config('app.name'),
            self::TYPE_STATUS_UPDATE => '📝 申请状态已更新 - '.config('app.name'),
            self::TYPE_DEADLINE_REMINDER => '⏰ 申请截止日期提醒 - '.config('app.name'),
            self::TYPE_SUGGESTION => '💼 职位申请建议 - '.config('app.name'),
            default => '职位申请通知 - '.config('app.name'),
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = match ($this->type) {
            self::TYPE_APPLIED => 'emails.job-application.applied',
            self::TYPE_STATUS_UPDATE => 'emails.job-application.status-update',
            self::TYPE_DEADLINE_REMINDER => 'emails.job-application.deadline',
            self::TYPE_SUGGESTION => 'emails.job-application.suggestion',
            default => 'emails.job-application.default',
        };

        return new Content(
            view: $view,
            with: [
                'userName' => $this->user->name,
                'company' => $this->application->company,
                'position' => $this->application->position,
                'status' => $this->application->status,
                'oldStatus' => $this->oldStatus,
                'deadline' => $this->application->deadline,
                'channel' => $this->application->channel,
                'suggestions' => $this->suggestions,
                'daysUntilDeadline' => $this->getDaysUntilDeadline(),
                'statusLabel' => $this->getStatusLabel(),
                'applicationUrl' => route('user.kanban.show', $this->application),
            ],
        );
    }

    /**
     * 获取状态标签
     */
    private function getStatusLabel(): string
    {
        return match ($this->application->status) {
            'applied' => '已申请',
            'screening' => '筛选中',
            'interview' => '面试中',
            'offer' => '已录用',
            'rejected' => '已拒绝',
            'withdrawn' => '已撤回',
            default => $this->application->status,
        };
    }

    /**
     * 获取截止日期剩余天数
     */
    private function getDaysUntilDeadline(): ?int
    {
        if (! $this->application->deadline) {
            return null;
        }

        return now()->diffInDays($this->application->deadline, false);
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
