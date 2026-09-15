<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfigHistory extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'changes',
        'verify_results',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'verify_results' => 'array',
    ];

    /**
     * 操作人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
