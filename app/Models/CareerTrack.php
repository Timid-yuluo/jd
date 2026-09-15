<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CareerTrack extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'icon',
        'color',
        'description',
        'optimization_focus',
        'recommended_templates',
        'prompt_strategy_key',
        'keywords',
        'avoid_words',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'optimization_focus' => 'array',
            'recommended_templates' => 'array',
            'keywords' => 'array',
            'avoid_words' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * 分类标签映射
     */
    public static function categoryLabels(): array
    {
        return [
            'tech' => '技术研发',
            'product_design' => '产品设计',
            'business' => '商业运营',
            'functional' => '职能支持',
            'industry' => '行业领域',
            'government' => '政企公共',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }
}
