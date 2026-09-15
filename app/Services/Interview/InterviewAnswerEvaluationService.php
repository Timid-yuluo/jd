<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Infrastructure\AI\AiManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class InterviewAnswerEvaluationService
{
    private ?InterviewFastEvaluator $fastEvaluator = null;

    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    private function fastEvaluator(): InterviewFastEvaluator
    {
        return $this->fastEvaluator ??= app(InterviewFastEvaluator::class);
    }

    /**
     * @return array{
     *   score:int,
     *   feedback:array<string,string>,
     *   termination:array{should_end:bool,reason:string,confidence:float},
     *   fluency:array{is_fluent:bool,severity:string,confidence:float,issues:array<int,string>,detected_by:string},
     *   dialogue:array{action:string,follow_up:string,follow_ups:array<int,string>,coach_reply:string,confidence:float}
     * }
     */
    public function evaluate(
        string $question,
        string $answer,
        int $sessionId,
        int $questionId,
        bool $forceAi = false,
        array $context = []
    ): array {
        $answer = trim($answer);
        $useCache = $this->shouldUseEvaluationCache($answer, $forceAi);
        $cacheKey = $useCache ? $this->buildEvaluationCacheKey($question, $answer, $context, $forceAi) : null;
        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        if (! $forceAi) {
            $fastResult = $this->fastEvaluator()->quickEvaluateByRules($answer, $context);
            if ($fastResult !== null) {
                if ($cacheKey !== null) {
                    Cache::put($cacheKey, $fastResult, now()->addSeconds($this->evaluationCacheTtlSeconds()));
                }

                return $fastResult;
            }
        }

        try {
            $aiEval = $this->aiManager->providerWithFallback()->evaluateInterviewAnswer($question, $answer);

            $score = (int) ($aiEval['score'] ?? 0);
            if ($score > 10) {
                $score = (int) round($score / 10);
            }
            $score = max(1, min(10, $score));

            $feedbackRaw = is_array($aiEval['feedback'] ?? null) ? $aiEval['feedback'] : [];
            $feedback = [
                'comment' => (string) ($feedbackRaw['comment'] ?? $feedbackRaw['strength'] ?? '回答已收到，结构比较完整。'),
                'suggestion' => (string) ($feedbackRaw['suggestion'] ?? $feedbackRaw['改进建议'] ?? '建议补充量化结果与复盘细节。'),
            ];
            $terminationRaw = is_array($aiEval['termination'] ?? null) ? $aiEval['termination'] : [];
            $termination = [
                'should_end' => (bool) ($terminationRaw['should_end'] ?? false),
                'reason' => trim((string) ($terminationRaw['reason'] ?? '')),
                'confidence' => max(0.0, min(1.0, (float) ($terminationRaw['confidence'] ?? 0.0))),
            ];
            $dialogueRaw = is_array($aiEval['dialogue'] ?? null) ? $aiEval['dialogue'] : [];
            $dialogueFollowUps = is_array($dialogueRaw['follow_ups'] ?? null)
                ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $dialogueRaw['follow_ups'])))
                : [];
            $dialogue = [
                'action' => (string) ($dialogueRaw['action'] ?? 'continue'),
                'follow_up' => trim((string) ($dialogueRaw['follow_up'] ?? '')),
                'follow_ups' => $dialogueFollowUps,
                'coach_reply' => trim((string) ($dialogueRaw['coach_reply'] ?? '')),
                'confidence' => max(0.0, min(1.0, (float) ($dialogueRaw['confidence'] ?? 0.0))),
            ];
            $fluency = $this->fastEvaluator()->extractFluency($aiEval, $answer);
            $normalized = $this->fastEvaluator()->normalizeByAnswer($score, $feedback, $termination, $fluency, $dialogue, $answer);
            $result = $this->fastEvaluator()->applyTypePolicy($normalized, $answer, $context);
            if ($cacheKey !== null) {
                Cache::put($cacheKey, $result, now()->addSeconds($this->evaluationCacheTtlSeconds()));
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('interview_answer_evaluation_fallback', [
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);

            $fallback = [
                'score' => 6,
                'feedback' => [
                    'comment' => '本次AI评分超时，已采用快速评分继续流程。',
                    'suggestion' => '建议补充关键动作、结果指标和复盘改进点。',
                ],
                'termination' => [
                    'should_end' => false,
                    'reason' => '',
                    'confidence' => 0.0,
                ],
                'fluency' => [
                    'is_fluent' => true,
                    'severity' => 'none',
                    'confidence' => 0.0,
                    'issues' => [],
                    'detected_by' => 'fallback',
                ],
                'dialogue' => [
                    'action' => 'continue',
                    'follow_up' => '',
                    'follow_ups' => [],
                    'coach_reply' => '',
                    'confidence' => 0.0,
                ],
            ];
            if ($cacheKey !== null) {
                Cache::put($cacheKey, $fallback, now()->addSeconds($this->evaluationCacheTtlSeconds()));
            }

            return $fallback;
        }
    }

    private function shouldUseEvaluationCache(string $answer, bool $forceAi): bool
    {
        if (! (bool) config('interview.evaluation_result_cache_enabled', true)) {
            return false;
        }
        if ($forceAi && ! (bool) config('interview.evaluation_result_cache_force_ai_enabled', true)) {
            return false;
        }

        $maxChars = max(50, (int) config('interview.evaluation_result_cache_max_answer_chars', 3000));

        return mb_strlen($answer) <= $maxChars;
    }

    private function evaluationCacheTtlSeconds(): int
    {
        return max(30, (int) config('interview.evaluation_result_cache_ttl_seconds', 900));
    }

    private function buildEvaluationCacheKey(string $question, string $answer, array $context, bool $forceAi): string
    {
        $normalizedContext = $context;
        ksort($normalizedContext);
        $payload = json_encode([
            'q' => trim($question),
            'a' => trim($answer),
            'c' => $normalizedContext,
            'f' => $forceAi,
            'v' => 1,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $payload ?: "{$question}|{$answer}|{$forceAi}");

        return "interview:evaluate:cache:{$hash}";
    }
}
