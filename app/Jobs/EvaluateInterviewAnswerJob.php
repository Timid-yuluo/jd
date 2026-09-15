<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\InterviewQuestion;
use App\Services\Interview\InterviewAnswerEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class EvaluateInterviewAnswerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public int $backoff = 5;

    public function __construct(
        public readonly int $questionId
    ) {
        // AI 评分任务独立队列，便于按 AI 资源调度
        $this->onQueue('ai');
    }

    public function handle(InterviewAnswerEvaluationService $evaluationService): void
    {
        $lock = Cache::lock("interview:evaluate:{$this->questionId}", 120);
        if (! $lock->get()) {
            return;
        }

        try {
            /** @var InterviewQuestion|null $question */
            $question = InterviewQuestion::query()->with('interviewSession')->find($this->questionId);
            if (! $question || $question->answer === null || $question->score !== null) {
                return;
            }

            $aiEval = $evaluationService->evaluate(
                (string) $question->question,
                (string) $question->answer,
                (int) $question->interview_session_id,
                (int) $question->id,
                false,
                [
                    'interview_type' => (string) ($question->interviewSession?->type ?? ''),
                    'dimension' => (string) $question->dimension,
                ]
            );

            $feedback = is_array($aiEval['feedback'] ?? null) ? $aiEval['feedback'] : [];
            $fluency = is_array($aiEval['fluency'] ?? null) ? $aiEval['fluency'] : [];
            $fluencyIssues = is_array($fluency['issues'] ?? null) ? $fluency['issues'] : [];
            if ($fluencyIssues !== []) {
                $feedback['fluency_issues'] = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $fluencyIssues)));
                $feedback['fluency_severity'] = (string) ($fluency['severity'] ?? 'medium');
            }
            $feedback['fluency_confidence'] = max(0.0, min(1.0, (float) ($fluency['confidence'] ?? 0.0)));
            $feedback['fluency_detected_by'] = (string) ($fluency['detected_by'] ?? 'rule');
            $dialogue = is_array($aiEval['dialogue'] ?? null) ? $aiEval['dialogue'] : [];
            $feedback['dialogue_action'] = (string) ($dialogue['action'] ?? 'continue');
            $feedback['dialogue_confidence'] = max(0.0, min(1.0, (float) ($dialogue['confidence'] ?? 0.0)));

            $question->forceFill([
                'score' => $aiEval['score'],
                'feedback' => $feedback,
            ])->save();
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        /** @var InterviewQuestion|null $question */
        $question = InterviewQuestion::query()->find($this->questionId);
        if (! $question || $question->answer === null || $question->score !== null) {
            return;
        }

        Log::warning('interview_evaluation_job_failed_fallback', [
            'question_id' => $this->questionId,
            'error' => $e->getMessage(),
        ]);

        // 标记为评估失败，不赋固定分数，允许后续重试
        $question->forceFill([
            'feedback' => [
                'comment' => 'AI 评估暂时不可用，评分将在稍后自动重试。请稍后刷新查看。',
                'suggestion' => '建议补充关键动作、量化结果和复盘改进点。',
                'evaluation_failed' => true,
                'evaluation_failed_at' => now()->toIso8601String(),
            ],
        ])->save();
    }
}
