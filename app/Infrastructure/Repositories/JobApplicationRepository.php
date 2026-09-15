<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Models\JobApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class JobApplicationRepository
{
    public function paginateByUser(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return JobApplication::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(perPage: $size, page: $page);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(int $userId, array $payload): JobApplication
    {
        return JobApplication::query()->create($payload + ['user_id' => $userId]);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function update(JobApplication $jobApplication, array $payload): JobApplication
    {
        $jobApplication->fill($payload)->save();

        return $jobApplication->refresh();
    }

    public function updateStatus(JobApplication $jobApplication, string $status): JobApplication
    {
        $oldStatus = $jobApplication->status;
        $jobApplication->recordStatusChange($status);
        $jobApplication->forceFill(['status' => $status])->save();

        return $jobApplication->refresh();
    }

    public function delete(JobApplication $jobApplication): void
    {
        $jobApplication->delete();
    }

    /**
     * @return array<string,int>
     */
    public function statisticsByUser(int $userId): array
    {
        $rows = JobApplication::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('user_id', $userId)
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = ['wishlist', 'applied', 'written', 'interview', 'offer', 'rejected'];
        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = (int) ($rows[$status] ?? 0);
        }

        return $result;
    }
}
