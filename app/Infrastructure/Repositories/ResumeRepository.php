<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Models\Resume;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ResumeRepository
{
    public function paginateByUser(int $userId, int $page, int $size): LengthAwarePaginator
    {
        return Resume::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(perPage: $size, page: $page);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(int $userId, array $payload): Resume
    {
        return Resume::query()->create($payload + ['user_id' => $userId]);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function update(Resume $resume, array $payload): Resume
    {
        $resume->fill($payload)->save();

        return $resume->refresh();
    }

    public function delete(Resume $resume): void
    {
        $resume->delete();
    }
}
