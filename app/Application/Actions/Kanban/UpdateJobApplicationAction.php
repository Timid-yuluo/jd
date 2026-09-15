<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;
use App\Models\JobApplication;

final class UpdateJobApplicationAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function execute(JobApplication $jobApplication, array $payload): JobApplication
    {
        return $this->jobApplicationRepository->update($jobApplication, $payload);
    }
}
