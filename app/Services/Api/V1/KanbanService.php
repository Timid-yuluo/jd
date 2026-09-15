<?php

declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Application\Actions\Kanban\CreateJobApplicationAction;
use App\Application\Actions\Kanban\DeleteJobApplicationAction;
use App\Application\Actions\Kanban\JobApplicationStatisticsAction;
use App\Application\Actions\Kanban\PaginateJobApplicationsAction;
use App\Application\Actions\Kanban\UpdateJobApplicationAction;
use App\Application\Actions\Kanban\UpdateJobApplicationStatusAction;
use App\Models\JobApplication;
use App\Services\Api\Concerns\AssertsOwnership;
use Illuminate\Http\Request;

final class KanbanService
{
    use AssertsOwnership;

    public function __construct(
        private readonly PaginateJobApplicationsAction $paginateJobApplicationsAction,
        private readonly CreateJobApplicationAction $createJobApplicationAction,
        private readonly UpdateJobApplicationAction $updateJobApplicationAction,
        private readonly UpdateJobApplicationStatusAction $updateJobApplicationStatusAction,
        private readonly DeleteJobApplicationAction $deleteJobApplicationAction,
        private readonly JobApplicationStatisticsAction $jobApplicationStatisticsAction,
    ) {}

    /**
     * @return array{data:array<string,mixed>,meta:array<string,mixed>}
     */
    public function paginate(int $userId, Request $request): array
    {
        $page = max(1, (int) $request->input('page.number', 1));
        $size = min(50, max(1, (int) $request->input('page.size', 20)));

        $paginator = $this->paginateJobApplicationsAction->execute($userId, $page, $size);

        return [
            'data' => ['items' => $paginator->items()],
            'meta' => [
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'size' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'pages' => $paginator->lastPage(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(int $userId, array $payload): JobApplication
    {
        return $this->createJobApplicationAction->execute($userId, $payload);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function update(JobApplication $item, array $payload): JobApplication
    {
        return $this->updateJobApplicationAction->execute($item, $payload);
    }

    public function updateStatus(JobApplication $item, string $status): JobApplication
    {
        return $this->updateJobApplicationStatusAction->execute($item, $status);
    }

    public function delete(JobApplication $item): void
    {
        $this->deleteJobApplicationAction->execute($item);
    }

    /**
     * @return array<string,int>
     */
    public function statistics(int $userId): array
    {
        return $this->jobApplicationStatisticsAction->execute($userId);
    }
}
