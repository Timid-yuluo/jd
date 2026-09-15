<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InterviewQuestion extends Model
{
    protected $fillable = [
        'interview_session_id',
        'round_no',
        'dimension',
        'question',
        'answer',
        'answer_hash',
        'score',
        'feedback',
        'tags',
        'difficulty_level',
    ];

    protected function casts(): array
    {
        return [
            'round_no' => 'integer',
            'score' => 'integer',
            'feedback' => 'array',
            'tags' => 'array',
        ];
    }

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
