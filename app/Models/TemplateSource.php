<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TemplateSource extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'driver',
        'base_url',
        'credentials',
        'sync_config',
        'sync_interval_minutes',
        'last_synced_at',
        'last_sync_status',
        'last_sync_count',
        'last_sync_error',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'sync_config' => 'array',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ResumeTemplate::class, 'source_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function needsSync(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->addMinutes($this->sync_interval_minutes)->isPast();
    }
}
