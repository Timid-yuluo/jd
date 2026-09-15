<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaUsage extends Model
{
    protected $fillable = [
        'user_id',
        'quota_key',
        'period',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取当前月期
     */
    public static function currentPeriod(): string
    {
        return now()->format('Ym');
    }
}
