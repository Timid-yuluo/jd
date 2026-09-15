<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;
use App\Models\JobApplication;

final class UpdateJobApplicationStatusAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    public function execute(JobApplication $jobApplication, string $status): JobApplication
    {
        return $this->jobApplicationRepository->updateStatus($jobApplication, $status);
    }
}
