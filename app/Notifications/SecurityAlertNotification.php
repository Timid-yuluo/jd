<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $alertType,
        private readonly string $severity,
        private readonly string $message,
        private readonly array $details = [],
    ) {
    }

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $time = now()->toIso8601String();
        $severityLabel = strtoupper($this->severity);

        $mail = (new MailMessage)
            ->subject("[{$appName}] Security Alert [{$severityLabel}] - {$this->alertType}")
            ->greeting('Security Alert')
            ->line("**Type**: {$this->alertType}")
            ->line("**Severity**: {$severityLabel}")
            ->line("**Time**: {$time}")
            ->line("**Message**: {$this->message}");

        if (! empty($this->details)) {
            $mail->line('**Details**:');
            foreach ($this->details as $key => $value) {
                $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                $mail->line("- **{$key}**: {$displayValue}");
            }
        }

        $banRoute = route('admin.access-bans.index', [], false);

        return $mail
            ->action('View Ban Rules', url($banRoute))
            ->line('Please review and take action if necessary.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_type' => $this->alertType,
            'severity' => $this->severity,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
