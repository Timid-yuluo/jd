<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeDiagnostic extends Model
{
    protected $fillable = [
        'user_id', 'resume_id', 'overall_score',
        'dimensions', 'issues', 'fix_priority', 'benchmark',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'dimensions' => 'array',
            'issues' => 'array',
            'fix_priority' => 'array',
            'benchmark' => 'array',
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
