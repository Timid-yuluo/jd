<?php

declare(strict_types=1);

namespace App\Application\Actions\Kanban;

use App\Infrastructure\Repositories\JobApplicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateJobApplicationsAction
{
    public function __construct(
        private readonly JobApplicationRepository $jobApplicationRepository,
    ) {}

    public function execute(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return $this->jobApplicationRepository->paginateByUser($userId, $page, $size);
    }
}
