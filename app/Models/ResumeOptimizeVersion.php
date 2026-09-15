<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResumeOptimizeVersion extends Model
{
    protected $fillable = [
        'session_id',
        'before_raw',
        'after_raw',
        'before_modules',
        'after_modules',
        'diff_map',
        'score_delta',
        'risk_tips',
        'highlights',
    ];

    protected function casts(): array
    {
        return [
            'before_modules' => 'array',
            'after_modules' => 'array',
            'diff_map' => 'array',
            'score_delta' => 'array',
            'risk_tips' => 'array',
            'highlights' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ResumeOptimizeSession::class, 'session_id');
    }
}
