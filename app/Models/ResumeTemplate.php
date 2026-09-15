<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'position',
        'level',
        'industry',
        'style',
        'template',
        'theme',
        'ats_level',
        'density',
        'tags',
        'font_settings',
        'module_blueprint',
        'preview_image_url',
        'is_active',
        'is_featured',
        'usage_count',
        'sort_order',
        'source_id',
        'source_driver',
        'external_id',
        'external_url',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'font_settings' => 'array',
            'module_blueprint' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TemplateSource::class, 'source_id');
    }

    public function isExternal(): bool
    {
        return $this->source_id !== null && $this->source_driver !== null;
    }
}
