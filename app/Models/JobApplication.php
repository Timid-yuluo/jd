<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JobApplication extends Model
{
    public const STATUS_WISHLIST = 'wishlist';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_WRITTEN = 'written';

    public const STATUS_INTERVIEW = 'interview';

    public const STATUS_OFFER = 'offer';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'resume_id',
        'company',
        'position',
        'job_tags',
        'experience_required',
        'education_required',
        'status',
        'deadline',
        'applied_at',
        'interview_at',
        'interview_info',
        'channel',
        'note',
        'job_url',
        'job_description',
        'salary_min',
        'salary_max',
        'location',
        'company_size',
        'industry',
        'ai_suggestions',
        'parsed_at',
        'reminder_sent',
        'reminder_sent_at',
        'status_history',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'applied_at' => 'date',
            'interview_at' => 'date',
            'parsed_at' => 'datetime',
            'ai_suggestions' => 'array',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'reminder_sent' => 'boolean',
            'reminder_sent_at' => 'datetime',
            'status_history' => 'array',
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

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_WISHLIST => '愿望单',
            self::STATUS_APPLIED => '已投递',
            self::STATUS_WRITTEN => '笔试',
            self::STATUS_INTERVIEW => '面试',
            self::STATUS_OFFER => 'Offer',
            self::STATUS_REJECTED => '淘汰',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * 记录状态变更到历史
     */
    public function recordStatusChange(string $newStatus, ?string $note = null): void
    {
        $history = is_array($this->status_history) ? $this->status_history : [];
        $history[] = [
            'from' => $this->status,
            'to' => $newStatus,
            'at' => now()->toIso8601String(),
            'note' => $note,
        ];
        $this->status_history = $history;
    }
}
