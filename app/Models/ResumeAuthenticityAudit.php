<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeAuthenticityAudit extends Model
{
    protected $fillable = [
        'user_id', 'resume_id', 'authenticity_score',
        'risk_flags', 'timeline_conflicts', 'exaggeration_warnings',
        'pressure_test_questions', 'ai_advice',
    ];

    protected function casts(): array
    {
        return [
            'authenticity_score' => 'integer',
            'risk_flags' => 'array',
            'timeline_conflicts' => 'array',
            'exaggeration_warnings' => 'array',
            'pressure_test_questions' => 'array',
            'ai_advice' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
