<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GroupInterviewSession extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'topic', 'scenario_type', 'participant_count',
        'user_role', 'status', 'participants', 'transcript',
        'evaluation', 'overall_score',
    ];

    protected function casts(): array
    {
        return [
            'participant_count' => 'integer',
            'overall_score' => 'integer',
            'participants' => 'array',
            'transcript' => 'array',
            'evaluation' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
