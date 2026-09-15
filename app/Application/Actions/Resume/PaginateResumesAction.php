<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Infrastructure\Repositories\ResumeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateResumesAction
{
    public function __construct(
        private readonly ResumeRepository $resumeRepository,
    ) {}

    public function execute(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return $this->resumeRepository->paginateByUser($userId, $page, $size);
    }
}
