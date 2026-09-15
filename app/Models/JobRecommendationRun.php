<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * #30 推荐生成运行历史
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $resume_id
 * @property string $status
 * @property int $candidate_count
 * @property int $created_count
 * @property int $high_match_count
 * @property int $duration_ms
 * @property string $trigger_source
 * @property string|null $error_message
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class JobRecommendationRun extends Model
{
    protected $table = 'job_recommendation_runs';

    protected $fillable = [
        'user_id',
        'resume_id',
        'status',
        'candidate_count',
        'created_count',
        'high_match_count',
        'duration_ms',
        'trigger_source',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'candidate_count' => 'integer',
            'created_count' => 'integer',
            'high_match_count' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
