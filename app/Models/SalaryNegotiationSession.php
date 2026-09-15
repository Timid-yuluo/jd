<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SalaryNegotiationSession extends Model
{
    protected $fillable = [
        'user_id', 'job_title', 'company', 'current_salary',
        'target_salary', 'city', 'experience_years',
        'context', 'ai_strategy', 'ai_dialogue',
    ];

    protected function casts(): array
    {
        return [
            'current_salary' => 'integer',
            'target_salary' => 'integer',
            'context' => 'array',
            'ai_strategy' => 'array',
            'ai_dialogue' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
