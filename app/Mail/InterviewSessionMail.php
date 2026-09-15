<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 面试会话相关邮件
 */
class InterviewSessionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const TYPE_STARTED = 'started';

    public const TYPE_COMPLETED = 'completed';

    public const TYPE_REMINDER = 'reminder';

    public const TYPE_FEEDBACK = 'feedback';

    /**
     * 队列重试配置：最多3次，每次间隔60秒递增，避免 SMTP 频率限制
     */
    public int $tries = 3;

    public int $backoff = 60;

    /**
     * 队列超时时间（秒）
     */
    public int $timeout = 120;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public InterviewSession $session,
        public string $type = self::TYPE_STARTED,
        public ?array $reportData = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            self::TYPE_STARTED => '🎯 面试模拟已开始 - '.config('app.name'),
            self::TYPE_COMPLETED => '📊 面试模拟报告已生成 - '.config('app.name'),
            self::TYPE_REMINDER => '⏰ 面试准备提醒 - '.config('app.name'),
            self::TYPE_FEEDBACK => '💡 面试反馈与建议 - '.config('app.name'),
            default => '面试通知 - '.config('app.name'),
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
            self::TYPE_STARTED => 'emails.interview.started',
            self::TYPE_COMPLETED => 'emails.interview.completed',
            self::TYPE_REMINDER => 'emails.interview.reminder',
            self::TYPE_FEEDBACK => 'emails.interview.feedback',
            default => 'emails.interview.default',
        };

        return new Content(
            view: $view,
            with: [
                'userName' => $this->user->name,
                'company' => $this->session->company,
                'position' => $this->session->position,
                'type' => $this->session->type,
                'sessionId' => $this->session->id,
                'overallScore' => $this->session->overall_score ?? 0,
                'questionCount' => $this->session->question_count ?? 0,
                'answeredCount' => $this->session->answered_count ?? 0,
                'reportData' => $this->reportData,
                'sessionUrl' => route('user.interviews.show', $this->session),
                'typeLabel' => $this->getTypeLabel(),
            ],
        );
    }

    /**
     * 获取面试类型标签
     */
    private function getTypeLabel(): string
    {
        return match ($this->session->type) {
            'technical' => '技术面试',
            'behavioral' => '行为面试',
            'case' => '案例分析',
            'system_design' => '系统设计',
            'mixed' => '综合面试',
            default => '模拟面试',
        };
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
