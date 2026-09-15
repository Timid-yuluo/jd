<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Support\Facades\Schema;

final class MailRuntimeConfigService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function applyFromSystemSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $mailer = trim($this->settings->get('mail_mailer', (string) config('mail.default', 'log')));
        if ($mailer === '') {
            return;
        }

        $encryption = strtolower(trim($this->settings->get('mail_encryption', 'tls')));
        $normalizedEncryption = $this->normalizeEncryption($encryption);

        $port = (int) $this->settings->get('mail_port', (string) config('mail.mailers.smtp.port', 587));
        if ($port <= 0) {
            $port = 587;
        }

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => $this->settings->get('mail_host', (string) config('mail.mailers.smtp.host', '')),
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $this->settings->get('mail_username', (string) config('mail.mailers.smtp.username', '')),
            'mail.mailers.smtp.password' => $this->settings->get('mail_password', (string) config('mail.mailers.smtp.password', '')),
            'mail.mailers.smtp.scheme' => $this->resolveSmtpScheme($normalizedEncryption),
            'mail.mailers.smtp.encryption' => $normalizedEncryption,
            'mail.from.address' => $this->settings->get('mail_from_address', (string) config('mail.from.address', '')),
            'mail.from.name' => $this->settings->get('mail_from_name', (string) config('mail.from.name', config('app.name'))),
        ]);
    }

    public function resolveSmtpScheme(string $encryption): string
    {
        return match ($this->normalizeEncryption($encryption)) {
            'ssl' => 'smtps',
            default => 'smtp',
        };
    }

    private function normalizeEncryption(string $encryption): ?string
    {
        $value = strtolower(trim($encryption));

        return match ($value) {
            'ssl', 'smtps' => 'ssl',
            'tls', 'starttls', 'smtp' => 'tls',
            '', 'null', 'none' => null,
            default => 'tls',
        };
    }
}
