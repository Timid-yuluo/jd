<?php

declare(strict_types=1);

namespace App\Application\Actions\Interview;

use App\Infrastructure\Repositories\InterviewSessionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateInterviewSessionsAction
{
    public function __construct(
        private readonly InterviewSessionRepository $interviewSessionRepository,
    ) {}

    public function execute(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return $this->interviewSessionRepository->paginateByUser($userId, $page, $size);
    }
}
