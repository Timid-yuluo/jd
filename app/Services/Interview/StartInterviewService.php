<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Application\Actions\Interview\CreateInterviewQuestionAction;
use App\Application\Actions\Interview\CreateInterviewSessionAction;
use App\Models\InterviewSession;
use App\Models\Resume;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class StartInterviewService
{
    public function __construct(
        private readonly InterviewQuestionGeneratorService $interviewQuestionGeneratorService,
        private readonly CreateInterviewSessionAction $createInterviewSessionAction,
        private readonly CreateInterviewQuestionAction $createInterviewQuestionAction,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function start(int $userId, array $payload): InterviewSession
    {
        $resume = null;
        $maxQuestions = max(1, (int) config('interview.max_questions', 5));
        $resumeId = isset($payload['resume_id']) ? (int) $payload['resume_id'] : 0;

        $resume = Resume::query()
            ->where('id', $resumeId)
            ->where('user_id', $userId)
            ->first();

        if ($resume === null) {
            throw (new ModelNotFoundException)->setModel(Resume::class, [$resumeId]);
        }

        $user = $resume->user;
        $currentPlan = $user?->currentPlan();
        $planQuotas = is_array($currentPlan?->quotas ?? null) ? $currentPlan->quotas : [];
        $planMaxQuestions = (int) ($planQuotas['interview_sessions']['max_questions'] ?? 0);
        if ($planMaxQuestions > 0) {
            $maxQuestions = max(1, $planMaxQuestions);
        }

        $session = $this->createInterviewSessionAction->execute([
            'user_id' => $userId,
            'resume_id' => $resume->id,
            'position' => (string) $payload['position'],
            'company' => $payload['company'] ?? null,
            'job_description' => isset($payload['job_description']) ? trim((string) $payload['job_description']) : null,
            'candidate_profile' => (string) ($payload['candidate_profile'] ?? 'fresh_graduate'),
            'language' => (string) ($payload['language'] ?? 'zh'),
            'difficulty' => (string) ($payload['difficulty'] ?? 'medium'),
            'tech_keywords' => isset($payload['tech_keywords']) ? trim((string) $payload['tech_keywords']) : null,
            'type' => (string) $payload['type'],
            'mode' => (string) ($payload['mode'] ?? 'text'),
            'is_practice' => (bool) ($payload['is_practice'] ?? false),
            'status' => InterviewSession::STATUS_PENDING,
            'question_count' => $maxQuestions,
            'answered_count' => 0,
        ]);

        $firstQuestionPayload = $this->interviewQuestionGeneratorService->generateForSession($session, 1);
        $this->createInterviewQuestionAction->execute(
            $session->id,
            1,
            (string) $firstQuestionPayload['question'],
            (string) $firstQuestionPayload['dimension'],
            $firstQuestionPayload['tags'] ?? null,
            $firstQuestionPayload['difficulty_level'] ?? null,
        );

        return $session->refresh();
    }
}
