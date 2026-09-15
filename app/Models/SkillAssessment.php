<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 用户技能自评模型
 */
final class SkillAssessment extends Model
{
    /** 硬技能 */
    public const CATEGORY_HARD = 'hard';

    /** 软技能 */
    public const CATEGORY_SOFT = 'soft';

    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'skill_name',
        'skill_category',
        'proficiency',
        'years_used',
        'last_used_at',
        'evidence',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'date',
            'proficiency' => 'integer',
            'years_used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 熟练度等级标签
     */
    public function getProficiencyLabelAttribute(): string
    {
        return match ($this->proficiency) {
            1 => '入门',
            2 => '基础',
            3 => '熟练',
            4 => '精通',
            5 => '专家',
            default => '未知',
        };
    }

    /**
     * 熟练度百分比（用于图表）
     */
    public function getProficiencyPercentAttribute(): int
    {
        return $this->proficiency * 20; // 1=20%, 5=100%
    }

    /**
     * 按类别筛选作用域
     */
    public function scopeHard($query)
    {
        return $query->where('skill_category', self::CATEGORY_HARD);
    }

    public function scopeSoft($query)
    {
        return $query->where('skill_category', self::CATEGORY_SOFT);
    }
}
