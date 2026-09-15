<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InterviewReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly JobApplication $application,
    ) {}

    public function via(): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'interview_reminder',
            'title' => '面试提醒',
            'body' => sprintf(
                '你明天有 %s 的面试，请做好准备！',
                $this->application->company ?? '该公司',
            ),
            'application_id' => $this->application->id,
            'company' => $this->application->company,
            'position' => $this->application->position,
            'interview_at' => $this->application->interview_at?->toIso8601String(),
        ];
    }
}
