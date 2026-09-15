<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'device_fingerprint',
        'browser_signature',
        'action',
        'path',
        'details',
        'request_headers',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('accessed_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeByDevice($query, string $fingerprint)
    {
        return $query->where('device_fingerprint', $fingerprint);
    }

    public function scopeDenied($query)
    {
        return $query->whereIn('action', ['denied', 'banned', 'suspicious']);
    }
}
