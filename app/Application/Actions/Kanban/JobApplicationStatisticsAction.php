<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;

final class JobApplicationStatisticsAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    /**
     * @return array<string,int>
     */
    public function execute(int $userId): array
    {
        return $this->jobApplicationRepository->statisticsByUser($userId);
    }
}
