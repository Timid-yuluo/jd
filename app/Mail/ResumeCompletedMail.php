<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 简历优化完成邮件
 */
class ResumeCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Resume $resume,
        public array $stats = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 您的简历优化已完成 - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.resume.completed',
            with: [
                'userName' => $this->user->name,
                'resumeTitle' => $this->resume->title,
                'targetJob' => $this->resume->target_job,
                'targetCompany' => $this->resume->target_company,
                'atsScore' => $this->resume->ats_score ?? 0,
                'viewUrl' => route('user.resumes.show', $this->resume),
                'stats' => $this->stats,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
