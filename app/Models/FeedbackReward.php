<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FeedbackReward extends Model
{
    protected $table = 'feedback_rewards';

    protected $fillable = [
        'feedback_id',
        'user_id',
        'user_credit_id',
        'granted_by',
        'quota_key',
        'credits',
        'validity_days',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'validity_days' => 'integer',
            'granted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (self $reward): void {
            $reward->granted_at ??= now();
        });
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function userCredit(): BelongsTo
    {
        return $this->belongsTo(UserCredit::class);
    }
}
