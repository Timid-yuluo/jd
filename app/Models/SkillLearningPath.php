<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI 学习路径模型
 */
final class SkillLearningPath extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'target_job',
        'current_snapshot',
        'required_skills',
        'gap_analysis',
        'ai_path',
        'total_duration_weeks',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_snapshot' => 'array',
            'required_skills' => 'array',
            'gap_analysis' => 'array',
            'ai_path' => 'array',
            'total_duration_weeks' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 仅活跃路径
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 归档路径
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * 获取差距数量
     */
    public function getGapCountAttribute(): int
    {
        return is_array($this->gap_analysis) ? count($this->gap_analysis) : 0;
    }

    /**
     * 获取学习阶段数量
     */
    public function getPhaseCountAttribute(): int
    {
        return is_array($this->ai_path) ? count($this->ai_path) : 0;
    }
}
