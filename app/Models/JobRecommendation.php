<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JobRecommendation extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'user_id', 'resume_id', 'external_recruitment_id',
        'job_title', 'company', 'city', 'match_score',
        'match_reasons', 'skill_gaps', 'status', 'dismissed_at',
        'fingerprint', 'viewed_at', 'favorited_at',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'integer',
            'match_reasons' => 'array',
            'skill_gaps' => 'array',
            'dismissed_at' => 'datetime',
            'viewed_at' => 'datetime',
            'favorited_at' => 'datetime',
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

    public function externalRecruitment(): BelongsTo
    {
        return $this->belongsTo(ExternalRecruitment::class);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_DISMISSED]);
    }

    /**
     * #19 已收藏的推荐
     */
    public function scopeFavorited(Builder $query): Builder
    {
        return $query->whereNotNull('favorited_at');
    }
}
