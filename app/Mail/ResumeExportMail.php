<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ResumeExportTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 简历导出完成邮件
 */
class ResumeExportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const TYPE_STARTED = 'started';

    public const TYPE_COMPLETED = 'completed';

    public const TYPE_FAILED = 'failed';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public ResumeExportTask $task,
        public string $type = self::TYPE_COMPLETED,
        public ?string $errorMessage = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            self::TYPE_STARTED => '🚀 简历导出任务已启动 - '.config('app.name'),
            self::TYPE_COMPLETED => '✅ 简历导出完成 - '.config('app.name'),
            self::TYPE_FAILED => '❌ 简历导出失败 - '.config('app.name'),
            default => '简历导出通知 - '.config('app.name'),
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
            self::TYPE_STARTED => 'emails.resume-export.started',
            self::TYPE_COMPLETED => 'emails.resume-export.completed',
            self::TYPE_FAILED => 'emails.resume-export.failed',
            default => 'emails.resume-export.default',
        };

        return new Content(
            view: $view,
            with: [
                'userName' => $this->user->name,
                'taskId' => $this->task->id,
                'format' => $this->task->format,
                'formatLabel' => $this->getFormatLabel(),
                'status' => $this->task->status,
                'progress' => $this->task->progress ?? 0,
                'fileUrl' => $this->task->file_url ?? $this->task->file_path,
                'errorMessage' => $this->errorMessage,
                'startedAt' => $this->task->started_at,
                'completedAt' => $this->task->completed_at,
                'tasksUrl' => route('user.resumes.index'),
            ],
        );
    }

    /**
     * 获取格式标签
     */
    private function getFormatLabel(): string
    {
        return match ($this->task->format) {
            'pdf' => 'PDF 格式',
            'docx' => 'Word 格式',
            'txt' => '纯文本格式',
            'html' => 'HTML 格式',
            'json' => 'JSON 格式',
            default => $this->task->format,
        };
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // 如果任务完成且有文件，添加附件
        if ($this->type === self::TYPE_COMPLETED && $this->task->file_path) {
            $fullPath = storage_path('app/'.$this->task->file_path);
            if (file_exists($fullPath)) {
                $attachments[] = Attachment::fromPath($fullPath)
                    ->as($this->task->file_name ?? basename($this->task->file_path))
                    ->withMime($this->getMimeType());
            }
        }

        return $attachments;
    }

    /**
     * 获取MIME类型
     */
    private function getMimeType(): string
    {
        return match ($this->task->format) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }
}
