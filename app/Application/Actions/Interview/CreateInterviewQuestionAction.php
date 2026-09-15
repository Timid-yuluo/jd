<?php

declare(strict_types=1);

namespace App\Application\Actions\Interview;

use App\Infrastructure\Repositories\InterviewSessionRepository;
use App\Models\InterviewQuestion;

final class CreateInterviewQuestionAction
{
    public function __construct(
        private readonly InterviewSessionRepository $interviewSessionRepository,
    ) {}

    public function execute(int $sessionId, int $roundNo, string $question, ?string $dimension = null, ?array $tags = null, ?string $difficultyLevel = null): InterviewQuestion
    {
        return $this->interviewSessionRepository->createQuestion($sessionId, $roundNo, $question, $dimension, $tags, $difficultyLevel);
    }
}
