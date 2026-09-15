<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InterviewSession extends Model
{
    use SoftDeletes;
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'resume_id',
        'position',
        'company',
        'job_description',
        'candidate_profile',
        'language',
        'difficulty',
        'tech_keywords',
        'type',
        'mode',
        'is_practice',
        'qr_token',
        'qr_expires_at',
        'status',
        'reminder_sent',
        'started_notif_sent',
        'completed_notif_sent',
        'question_count',
        'answered_count',
        'overall_score',
        'report',
    ];

    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'answered_count' => 'integer',
            'overall_score' => 'integer',
            'report' => 'array',
            'tech_keywords' => 'array',
            'language' => 'string',
            'difficulty' => 'string',
            'qr_expires_at' => 'datetime',
            'reminder_sent' => 'boolean',
            'started_notif_sent' => 'boolean',
            'completed_notif_sent' => 'boolean',
            'is_practice' => 'boolean',
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

    public function questions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    // ---- Query Scopes ----

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeScored(Builder $query): Builder
    {
        return $query->completed()->whereNotNull('overall_score');
    }
}
