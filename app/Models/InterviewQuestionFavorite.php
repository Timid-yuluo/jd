<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 面试问题收藏（错题本）
 */
final class InterviewQuestionFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'interview_question_id',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestion::class, 'interview_question_id');
    }
}
