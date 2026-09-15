<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeExportTask extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'type',
        'status',
        'attempt_count',
        'max_attempts',
        'resume_ids',
        'resume_count',
        'position_keywords',
        'include_optimized',
        'file_path',
        'file_name',
        'error_message',
        'last_error_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'resume_ids' => 'array',
            'position_keywords' => 'array',
            'resume_count' => 'integer',
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'include_optimized' => 'boolean',
            'last_error_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
