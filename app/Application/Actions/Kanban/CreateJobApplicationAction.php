<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;
use App\Models\JobApplication;

final class CreateJobApplicationAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function execute(int $userId, array $payload): JobApplication
    {
        return $this->jobApplicationRepository->create($userId, $payload);
    }
}
