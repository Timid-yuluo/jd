<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VoiceTranscript extends Model
{
    protected $fillable = ['interview_session_id', 'question_id', 'transcript', 'language', 'duration_ms', 'source'];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestion::class, 'question_id');
    }
}
