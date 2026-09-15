<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PageVisit extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'path',
        'route_name',
        'method',
        'ip_address',
        'real_ip',
        'user_agent',
        'device_type',
        'device_brand',
        'browser',
        'browser_version',
        'os',
        'os_version',
        'is_bot',
        'bot_name',
        'referer',
        'country',
        'city',
        'isp',
        'duration_ms',
        'event_type',
        'event_label',
        'meta',
        'created_at',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'duration_ms' => 'integer',
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePageviews($query)
    {
        return $query->where('event_type', 'pageview');
    }

    public function scopeEvents($query)
    {
        return $query->where('event_type', '!=', 'pageview');
    }

    public function scopeToday($query)
    {
        return $query->where('created_at', '>=', now()->startOfDay());
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('created_at', '<=', $to.' 23:59:99');
        }

        return $query;
    }

    public function scopeExcludeBots($query)
    {
        return $query->where('is_bot', false);
    }

    public function getDisplayIpAttribute(): string
    {
        return $this->real_ip ?: ($this->ip_address ?? '-');
    }

    public function getDeviceIconAttribute(): string
    {
        return match ($this->device_type) {
            'mobile' => 'ti-device-mobile',
            'tablet' => 'ti-device-tablet',
            'bot' => 'ti-robot',
            default => 'ti-device-desktop',
        };
    }

    public function getBrowserDisplayAttribute(): string
    {
        $display = $this->browser ?? 'Unknown';
        if ($this->browser_version) {
            $major = explode('.', $this->browser_version)[0];
            $display .= ' '.$major;
        }

        return $display;
    }

    public function getOsDisplayAttribute(): string
    {
        $display = $this->os ?? 'Unknown';
        if ($this->os_version) {
            $display .= ' '.$this->os_version;
        }

        return $display;
    }

    public function getDeviceDisplayAttribute(): string
    {
        $parts = [];
        if ($this->device_brand) {
            $parts[] = $this->device_brand;
        }
        $parts[] = match ($this->device_type) {
            'mobile' => '手机',
            'tablet' => '平板',
            'bot' => '机器人',
            default => '桌面',
        };

        return implode(' · ', $parts);
    }
}
