<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CareerPlan extends Model
{
    protected $fillable = [
        'user_id', 'resume_id', 'current_role', 'target_role',
        'timeline', 'ai_path', 'skill_gaps', 'salary_forecast', 'milestones',
    ];

    protected function casts(): array
    {
        return [
            'ai_path' => 'array',
            'skill_gaps' => 'array',
            'salary_forecast' => 'array',
            'milestones' => 'array',
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
