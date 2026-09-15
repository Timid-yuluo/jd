<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewDeviceLoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $ip,
        private readonly string $device,
        private readonly string $browser,
        private readonly string $location = 'Unknown',
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

        return (new MailMessage)
            ->subject("[{$appName}] New Device Admin Login")
            ->greeting('Hello '.$notifiable->name)
            ->line('Your admin account was logged in from a new device:')
            ->line("**Time**: {$time}")
            ->line("**IP**: {$this->ip}")
            ->line("**Device**: {$this->device}")
            ->line("**Browser**: {$this->browser}")
            ->line("**Location**: {$this->location}")
            ->line('')
            ->line('If this was not you, please change your password immediately and review the access logs.')
            ->action('Review Access Logs', route('admin.access-bans.logs', [], false))
            ->line('This is an automated security notification.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'ip' => $this->ip,
            'device' => $this->device,
            'browser' => $this->browser,
            'location' => $this->location,
        ];
    }
}
