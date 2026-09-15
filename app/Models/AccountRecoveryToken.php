<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRecoveryToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return ! $this->used && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    public static function generateForUser(int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));

        self::create([
            'user_id' => $userId,
            'token' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(7),
            'used' => false,
        ]);

        return $rawToken;
    }

    public static function findByRawToken(string $rawToken): ?self
    {
        return self::where('token', hash('sha256', $rawToken))->with('user')->first();
    }
}
