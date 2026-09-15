<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

final class ResumeOptimizeSession extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_APPLYING = 'applying';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'uuid',
        'user_id',
        'resume_id',
        'status',
        'idempotency_key',
        'progress',
        'config',
        'error_code',
        'error_message',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'progress' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(static function (self $session): void {
            if (! filled($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function version(): HasOne
    {
        return $this->hasOne(ResumeOptimizeVersion::class, 'session_id');
    }

    public function applyLogs(): HasMany
    {
        return $this->hasMany(ResumeOptimizeApplyLog::class, 'session_id');
    }
}
