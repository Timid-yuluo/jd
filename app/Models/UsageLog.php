<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'scenario',
        'provider',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'cost_micros',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
