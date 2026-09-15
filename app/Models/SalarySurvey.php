<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SalarySurvey extends Model
{
    protected $fillable = [
        'user_id', 'job_title', 'company', 'city', 'industry',
        'salary_min', 'salary_max', 'currency', 'experience_level',
        'source', 'source_hash', 'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'reported_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByJob(Builder $query, string $jobTitle): Builder
    {
        return $query->where('job_title', 'like', '%' . escapeLike($jobTitle) . '%');
    }
}
