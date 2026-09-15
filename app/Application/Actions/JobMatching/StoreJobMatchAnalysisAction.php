<?php

declare(strict_types=1);

namespace App\Application\Actions\JobMatching;

use App\Infrastructure\Repositories\JobMatchAnalysisRepository;
use App\Models\JobMatchAnalysis;
use App\Models\Resume;

final class StoreJobMatchAnalysisAction
{
    public function __construct(
        private readonly JobMatchAnalysisRepository $jobMatchAnalysisRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $result
     */
    public function execute(int $userId, Resume $resume, string $jobDescription, array $result): JobMatchAnalysis
    {
        $storedResult = array_filter(
            $result,
            static fn (string $key): bool => ! str_starts_with($key, '__'),
            ARRAY_FILTER_USE_KEY
        );

        return $this->jobMatchAnalysisRepository->create([
            'user_id' => $userId,
            'resume_id' => $resume->id,
            'job_description' => $jobDescription,
            'result' => $storedResult,
            'match_score' => (int) ($result['match_score'] ?? 0),
            'level' => (string) ($result['level'] ?? ''),
            'summary' => (string) ($result['summary'] ?? ''),
        ]);
    }
}
