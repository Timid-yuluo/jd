<?php

declare(strict_types=1);

namespace App\Application\Actions\Interview;

use App\Infrastructure\Repositories\InterviewSessionRepository;
use App\Models\InterviewSession;

final class CreateInterviewSessionAction
{
    public function __construct(
        private readonly InterviewSessionRepository $interviewSessionRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function execute(array $payload): InterviewSession
    {
        return $this->interviewSessionRepository->create($payload);
    }
}
