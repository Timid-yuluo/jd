<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CareerAssessment extends Model
{
    protected $fillable = [
        'user_id', 'test_type', 'answers', 'result_code', 'result_label',
        'dimensions', 'ai_analysis', 'recommended_careers', 'team_roles',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'dimensions' => 'array',
            'ai_analysis' => 'array',
            'recommended_careers' => 'array',
            'team_roles' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
