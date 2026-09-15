<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 简历 ATS 评分历史记录
 */
final class ResumeAtsScoreLog extends Model
{
    protected $fillable = [
        'resume_id',
        'user_id',
        'score',
        'level',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'summary' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
