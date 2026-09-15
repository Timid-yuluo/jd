<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'location',
        'is_success',
    ];

    protected function casts(): array
    {
        return [
            'is_success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDeviceIconAttribute(): string
    {
        return match ($this->device_type) {
            'mobile' => 'ti-device-mobile',
            'tablet' => 'ti-device-tablet',
            default => 'ti-device-desktop',
        };
    }

    public function getBrowserNameAttribute(): string
    {
        $agent = strtolower($this->user_agent ?? '');

        if (str_contains($agent, 'chrome') && ! str_contains($agent, 'edg')) {
            return 'Chrome';
        }
        if (str_contains($agent, 'firefox')) {
            return 'Firefox';
        }
        if (str_contains($agent, 'safari') && ! str_contains($agent, 'chrome')) {
            return 'Safari';
        }
        if (str_contains($agent, 'edg')) {
            return 'Edge';
        }

        return '未知浏览器';
    }
}
