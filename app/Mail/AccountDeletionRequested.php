<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletionRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $recoveryToken,
        public string $deletionScheduledAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【重要】您的账号注销申请已收到',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-deletion-requested',
            with: [
                'user' => $this->user,
                'recoveryUrl' => route('account.recover', ['token' => $this->recoveryToken]),
                'deletionScheduledAt' => $this->deletionScheduledAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
