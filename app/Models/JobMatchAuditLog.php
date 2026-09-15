<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JobMatchAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'resume_id',
        'job_match_analysis_id',
        'action',
        'status',
        'driver',
        'model',
        'failure_type',
        'latency_ms',
        'quota_source',
        'credit_id',
        'request_id',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'latency_ms' => 'integer',
            'credit_id' => 'integer',
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

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(JobMatchAnalysis::class, 'job_match_analysis_id');
    }
}
