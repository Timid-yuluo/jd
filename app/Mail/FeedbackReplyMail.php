<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Feedback;
use App\Models\FeedbackReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback,
        public FeedbackReply $reply,
    ) {}

    public function build(): self
    {
        return $this->subject('您的反馈已收到回复 - '.config('app.name'))
            ->markdown('emails.feedback-reply');
    }
}
