<?php

declare(strict_types=1);

namespace App\Application\Actions\JobMatching;

use App\Models\JobMatchAnalysis;
use App\Models\Resume;
use App\Models\User;
use App\Services\JobMatchingService;
use DomainException;

final class AnalyzeJobMatchAction
{
    public function __construct(
        private readonly JobMatchingService $jobMatchingService,
        private readonly StoreJobMatchAnalysisAction $storeJobMatchAnalysisAction,
    ) {}

    /**
     * @return array{
     *     resume: Resume,
     *     job_description: string,
     *     result: array<string,mixed>,
     *     analysis_history: JobMatchAnalysis,
     *     audit: array<string,mixed>
     * }
     */
    public function execute(User $user, string $jobDescription, ?int $resumeId = null): array
    {
        $resume = $this->resolveResume($user, $resumeId);
        $result = $this->jobMatchingService->analyze($resume, $jobDescription);
        $analysisHistory = $this->storeJobMatchAnalysisAction->execute($user->id, $resume, $jobDescription, $result);
        $audit = is_array($result['__audit'] ?? null) ? $result['__audit'] : [];

        return [
            'resume' => $resume,
            'job_description' => $jobDescription,
            'result' => $result,
            'analysis_history' => $analysisHistory,
            'audit' => $audit,
        ];
    }

    private function resolveResume(User $user, ?int $resumeId): Resume
    {
        $resume = $resumeId !== null
            ? $user->resumes()->find($resumeId)
            : $user->resumes()->latest()->first();

        if (! $resume instanceof Resume) {
            throw new DomainException('NO_RESUME_AVAILABLE');
        }

        return $resume;
    }
}
