<?php

declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Application\Actions\Interview\CreateInterviewQuestionAction;
use App\Application\Actions\Interview\FindInterviewQuestionAction;
use App\Application\Actions\Interview\PaginateInterviewSessionsAction;
use App\Infrastructure\AI\AiManager;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Services\Api\Concerns\AssertsOwnership;
use App\Services\Interview\InterviewQuestionGeneratorService;
use App\Services\Interview\StartInterviewService;
use App\Services\UsageLogger;
use App\Support\Interview\AnswerAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class InterviewService
{
    use AssertsOwnership;

    private const DEFAULT_MAX_QUESTIONS = 5;

    private const REPORT_VERSION = 2;

    public function __construct(
        private readonly AiManager $aiManager,
        private readonly PaginateInterviewSessionsAction $paginateInterviewSessionsAction,
        private readonly CreateInterviewQuestionAction $createInterviewQuestionAction,
        private readonly FindInterviewQuestionAction $findInterviewQuestionAction,
        private readonly StartInterviewService $startInterviewService,
        private readonly InterviewQuestionGeneratorService $interviewQuestionGeneratorService,
    ) {}

    /**
     * @return array{data:array<string,mixed>,meta:array<string,mixed>}
     */
    public function paginate(int $userId, Request $request): array
    {
        $page = max(1, (int) $request->input('page.number', 1));
        $size = min(50, max(1, (int) $request->input('page.size', 10)));

        $paginator = $this->paginateInterviewSessionsAction->execute($userId, $page, $size);

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
    public function create(int $userId, array $payload): InterviewSession
    {
        return $this->startInterviewService->start($userId, $payload);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function currentQuestion(InterviewSession $session): ?array
    {
        $question = $session->questions()->whereNull('answer')->orderBy('round_no')->first();
        if (! $question) {
            return null;
        }

        if ($session->status === 'pending') {
            $session->forceFill(['status' => 'in_progress'])->save();
        }

        return $question->toArray();
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function submitAnswer(InterviewSession $session, array $payload): array
    {
        $maxQuestions = max(1, (int) config('interview.max_questions', self::DEFAULT_MAX_QUESTIONS));
        $lock = Cache::lock("interview:submit:{$session->id}:{$payload['question_id']}", 10);
        if (! $lock->get()) {
            return ['accepted' => false, 'message' => '提交过于频繁，请稍后再试'];
        }

        $answer = trim((string) $payload['answer']);
        $answerHash = hash('sha256', $answer);

        try {
            /** @var InterviewQuestion|null $question */
            $question = $this->findInterviewQuestionAction->execute($session->id, (int) $payload['question_id']);

            if (! $question) {
                return ['accepted' => false, 'message' => '题目不存在'];
            }

            if ($question->answer !== null) {
                $isSameAnswer = ($question->answer_hash !== null && hash_equals((string) $question->answer_hash, $answerHash))
                    || trim((string) $question->answer) === $answer;

                if ($isSameAnswer) {
                    $nextQuestion = $this->currentQuestion($session->refresh());
                    $isFinished = $nextQuestion === null;
                    if ($isFinished && $session->status !== 'completed') {
                        $session->forceFill(['status' => 'completed'])->save();
                    }

                    return [
                        'accepted' => true,
                        'idempotent' => true,
                        'score' => (int) ($question->score ?? 0),
                        'feedback' => $question->feedback ?? [],
                        'finished' => $isFinished,
                        'next_question' => $nextQuestion,
                    ];
                }

                return ['accepted' => false, 'message' => '此题已回答'];
            }

            $aiEval = $this->safeEvaluateAnswer((string) $question->question, $answer, $session->id, (int) $question->id);
            $score = (int) $aiEval['score'];

            $question->forceFill([
                'answer' => $answer,
                'answer_hash' => $answerHash,
                'score' => $score,
                'feedback' => $aiEval['feedback'],
            ])->save();

            if ($session->status === 'pending') {
                $session->forceFill(['status' => 'in_progress'])->save();
            }

            $answeredCount = $session->questions()->whereNotNull('answer')->count();
            $session->forceFill(['answered_count' => $answeredCount])->save();

            if ($answeredCount < $maxQuestions) {
                $nextRound = $answeredCount + 1;
                $nextQuestionPayload = $this->interviewQuestionGeneratorService->generateForSession($session, $nextRound, $answer);
                $this->createInterviewQuestionAction->execute(
                    $session->id,
                    $nextRound,
                    (string) $nextQuestionPayload['question'],
                    (string) $nextQuestionPayload['dimension'],
                    $nextQuestionPayload['tags'] ?? null,
                    $nextQuestionPayload['difficulty_level'] ?? null,
                );
                $session->forceFill(['question_count' => $maxQuestions])->save();
            }

            UsageLogger::log(
                $session->user_id,
                'interview_answer',
                config('ai.default'),
                $aiEval,
                ['session_id' => $session->id, 'question_id' => $question->id],
            );

            $nextQuestion = $this->currentQuestion($session->refresh());
            $isFinished = $nextQuestion === null;
            if ($isFinished && $session->status !== 'completed') {
                $session->forceFill(['status' => 'completed'])->save();
            }

            return [
                'accepted' => true,
                'idempotent' => false,
                'score' => $score,
                'feedback' => $aiEval['feedback'],
                'finished' => $isFinished,
                'next_question' => $nextQuestion,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{score:int,feedback:array<string,string>}
     */
    private function safeEvaluateAnswer(string $question, string $answer, int $sessionId, int $questionId): array
    {
        try {
            $aiEval = $this->aiManager->provider()->evaluateInterviewAnswer($question, $answer);

            $score = (int) ($aiEval['score'] ?? 0);
            // 部分模型会返回 0-100 分，统一收敛到 0-10。
            if ($score > 10) {
                $score = (int) round($score / 10);
            }
            $score = max(1, min(10, $score));

            $feedbackRaw = is_array($aiEval['feedback'] ?? null) ? $aiEval['feedback'] : [];
            $feedback = [
                'comment' => (string) ($feedbackRaw['comment'] ?? $feedbackRaw['strength'] ?? '回答已收到，结构比较完整。'),
                'suggestion' => (string) ($feedbackRaw['suggestion'] ?? $feedbackRaw['改进建议'] ?? '建议补充量化结果与复盘细节。'),
            ];

            return $this->normalizeByAnswer($score, $feedback, $answer);
        } catch (\Throwable $e) {
            Log::warning('interview_answer_evaluation_fallback', [
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'score' => 6,
                'feedback' => [
                    'comment' => '本次AI评分超时，已采用快速评分继续流程。',
                    'suggestion' => '建议补充关键动作、结果指标和复盘改进点。',
                ],
            ];
        }
    }

    /**
     * @param  array<string,string>  $feedback
     * @return array{score:int,feedback:array<string,string>}
     */
    private function normalizeByAnswer(int $score, array $feedback, string $answer): array
    {
        $answer = trim($answer);
        $length = mb_strlen($answer);
        $isNonAnswer = AnswerAnalyzer::isNonAnswer($answer);

        if ($isNonAnswer) {
            return [
                'score' => min($score, 2),
                'feedback' => [
                    'comment' => '该回答信息量较低（如“我不知道”），暂时无法体现你的岗位能力。',
                    'suggestion' => '建议按“背景-行动-结果-复盘”补充一个真实案例，即使是小项目也可以。',
                ],
            ];
        }

        if ($length < 20) {
            return [
                'score' => min($score, 4),
                'feedback' => [
                    'comment' => '回答偏短，细节不足，暂时难以判断你的方法论和解决能力。',
                    'suggestion' => '建议至少补充：场景背景、你的动作、结果数据与复盘。',
                ],
            ];
        }

        return [
            'score' => $score,
            'feedback' => [
                'comment' => trim((string) ($feedback['comment'] ?? '')) ?: '回答已收到，请继续保持结构化表达。',
                'suggestion' => trim((string) ($feedback['suggestion'] ?? '')) ?: '建议补充关键动作、结果指标和复盘改进点。',
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function finish(InterviewSession $session): array
    {
        $answeredQuestions = $session->questions()->whereNotNull('answer')->get();
        $avgScore = (int) round((float) ($answeredQuestions->avg('score') ?? 0));
        $dimensionScores = $this->buildDimensionScores($session, $answeredQuestions->all());

        $report = [
            'report_version' => self::REPORT_VERSION,
            'overall_score' => $avgScore ?: 0,
            'strengths' => collect($dimensionScores)
                ->filter(static fn (array $row): bool => (int) $row['score'] >= 7)
                ->sortByDesc('score')
                ->take((int) config('ui.limit.interview_recent', 3))
                ->map(static fn (array $row): string => (string) $row['dimension'])
                ->values()
                ->all(),
            'improvements' => collect($dimensionScores)
                ->filter(static fn (array $row): bool => (int) $row['score'] < 7)
                ->sortBy('score')
                ->take((int) config('ui.limit.interview_recent', 3))
                ->map(static fn (array $row): string => sprintf('（建议补充可量化案例）', (string) $row['dimension']))
                ->values()
                ->all(),
            'dimension_scores' => $dimensionScores,
            'weak_dimensions' => collect($dimensionScores)
                ->filter(static fn (array $row): bool => (int) $row['score'] < 7)
                ->sortBy('score')
                ->take((int) config('ui.limit.interview_weakness', 2))
                ->map(static fn (array $row): string => (string) $row['dimension'])
                ->values()
                ->all(),
        ];

        $session->forceFill([
            'status' => 'completed',
            'overall_score' => $avgScore,
            'report' => $report,
        ])->save();

        return $report;
    }

    /**
     * @return array<string,mixed>
     */
    public function report(InterviewSession $session): array
    {
        $report = (array) ($session->report ?? []);
        $reportVersion = (int) ($report['report_version'] ?? 0);
        $needRebuild = empty($report)
            || ! array_key_exists('dimension_scores', $report)
            || $reportVersion < self::REPORT_VERSION;

        if ($session->status === 'completed' && $needRebuild) {
            return $this->finish($session);
        }

        return $report;
    }

    /**
     * @param  array<int, InterviewQuestion>  $answeredQuestions
     * @return array<int, array{dimension:string,score:int,count:int}>
     */
    private function buildDimensionScores(InterviewSession $session, array $answeredQuestions): array
    {
        $rows = [];
        foreach ($answeredQuestions as $question) {
            $score = (int) ($question->score ?? 0);
            if ($score <= 0) {
                continue;
            }

            $dimension = trim((string) ($question->dimension ?? ''));
            if ($dimension === '') {
                $dimension = $this->interviewQuestionGeneratorService->resolveFocusDimension((string) $session->type, (int) $question->round_no);
            }

            if (! isset($rows[$dimension])) {
                $rows[$dimension] = [
                    'dimension' => $dimension,
                    'total_score' => 0,
                    'count' => 0,
                ];
            }

            $rows[$dimension]['total_score'] += $score;
            $rows[$dimension]['count']++;
        }

        $result = [];
        foreach ($rows as $row) {
            $count = max(1, (int) $row['count']);
            $result[] = [
                'dimension' => (string) $row['dimension'],
                'score' => (int) round(((int) $row['total_score']) / $count),
                'count' => $count,
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $result;
    }
}
