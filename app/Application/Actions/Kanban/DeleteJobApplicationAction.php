<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;
use App\Models\JobApplication;

final class DeleteJobApplicationAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    public function execute(JobApplication $jobApplication): void
    {
        $this->jobApplicationRepository->delete($jobApplication);
    }
}
