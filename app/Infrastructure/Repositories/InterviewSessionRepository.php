<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class InterviewSessionRepository
{
    public function paginateByUser(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return InterviewSession::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(perPage: $size, page: $page);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(array $payload): InterviewSession
    {
        return InterviewSession::query()->create($payload);
    }

    public function createQuestion(int $sessionId, int $roundNo, string $question, ?string $dimension = null, ?array $tags = null, ?string $difficultyLevel = null): InterviewQuestion
    {
        return InterviewQuestion::query()->create([
            'interview_session_id' => $sessionId,
            'round_no' => $roundNo,
            'dimension' => $dimension,
            'question' => $question,
            'tags' => $tags,
            'difficulty_level' => $difficultyLevel,
        ]);
    }

    public function findSessionQuestion(int $sessionId, int $questionId): ?InterviewQuestion
    {
        return InterviewQuestion::query()
            ->where('id', $questionId)
            ->where('interview_session_id', $sessionId)
            ->first();
    }
}
