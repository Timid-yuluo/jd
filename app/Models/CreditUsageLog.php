<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CreditUsageLog extends Model
{
    protected $fillable = ['user_id', 'credit_id', 'scenario', 'reference_type', 'reference_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(UserCredit::class, 'credit_id');
    }
}
