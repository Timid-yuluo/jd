<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessBan extends Model
{
    public const BAN_TYPES = ['ip', 'ip_range', 'device', 'browser'];

    protected $fillable = [
        'ban_type',
        'ban_value',
        'reason',
        'severity',
        'metadata',
        'banned_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public function getBanTypeLabelAttribute(): string
    {
        return match ($this->ban_type) {
            'ip' => 'IP',
            'ip_range' => 'IP Range',
            'device' => 'Device',
            'browser' => 'Browser',
            default => $this->ban_type,
        };
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('ban_type', $type);
    }

    public function scopeBlocking($query)
    {
        return $query->active()->where('severity', 'block');
    }
}
