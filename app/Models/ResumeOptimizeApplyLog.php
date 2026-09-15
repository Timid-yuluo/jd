<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeOptimizeApplyLog extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'resume_id',
        'selections',
        'applied_result',
        'undo_token',
    ];

    protected function casts(): array
    {
        return [
            'selections' => 'array',
            'applied_result' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ResumeOptimizeSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
