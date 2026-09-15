<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Models\JobMatchAnalysis;

final class JobMatchAnalysisRepository
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(array $payload): JobMatchAnalysis
    {
        return JobMatchAnalysis::query()->create($payload);
    }
}
