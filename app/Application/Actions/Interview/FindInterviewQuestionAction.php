<?php

declare(strict_types=1);

namespace App\Application\Actions\Interview;

use App\Infrastructure\Repositories\InterviewSessionRepository;
use App\Models\InterviewQuestion;

final class FindInterviewQuestionAction
{
    public function __construct(
        private readonly InterviewSessionRepository $interviewSessionRepository,
    ) {}

    public function execute(int $sessionId, int $questionId): ?InterviewQuestion
    {
        return $this->interviewSessionRepository->findSessionQuestion($sessionId, $questionId);
    }
}
