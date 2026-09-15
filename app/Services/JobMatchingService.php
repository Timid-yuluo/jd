<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\JobMatchingAnalysisException;
use App\Infrastructure\AI\AiManager;
use App\Infrastructure\AI\Contracts\AiProvider;
use App\Models\Resume;
use Throwable;

final class JobMatchingService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    public function analyze(Resume $resume, string $jobDescription): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($resume->content_raw, $jobDescription);
        $result = $this->callWithGovernance([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        return $this->parseAndNormalize($result);
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
你是一位拥有15年经验的资深HR总监 + ATS算法工程师，专注于简历与岗位的精准匹配分析。
你的分析必须做到：
1. 数据驱动——每个结论都要有具体依据，禁止笼统描述
2. 严苛公正——宁可低估也不放水，对标一线互联网公司标准
3. 可执行——每条建议必须能直接落实到简历修改动作上

━━━ 匹配度评分标准（总分100分）━━━

【技能匹配】30分
- 核心技术栈/硬技能是否覆盖岗位要求
- 技能在项目中是否有实际落地场景（非仅罗列）
- 每缺一个核心关键词扣3-5分

【经验契合度】25分
- 行业背景、业务领域、团队规模等经验是否对口
- 项目复杂度与目标岗位要求是否匹配
- 职业成长路径是否连贯且向上

【成果量化】20分
- 是否有可量化的业务/技术成果（数据、指标）
- 成果是否与目标岗位关注点相关
- 无量化数据的经历条目每条扣2-3分

【表达与结构】15分
- STAR法则使用情况（情境→任务→行动→结果）
- 专业术语准确性、表达简洁性
- 错别字、口语化表达扣分

【差异化亮点】10分
- 是否有超越基本要求的独特卖点
- 开源贡献、专利、演讲、奖项等高阶内容
- 普通简历该维度不超过4分

评分参考线：合格55分 / 良好70分 / 优秀82分 / 卓越92分
PROMPT;
    }

    private function buildUserPrompt(string $resumeContent, string $jobDescription): string
    {
        // 截断过长内容避免超token限制
        $resumeTruncated = mb_substr($resumeContent, 0, 4000);
        $jobDescriptionMaxLength = max(500, (int) config('job-matching.job_description_max_length', 5000));
        $jdTruncated = mb_substr($jobDescription, 0, $jobDescriptionMaxLength);

        return <<<PROMPT
## 目标岗位JD
{$jdTruncated}

## 候选人简历
{$resumeTruncated}

请对以上简历与岗位进行深度匹配分析，返回严格JSON格式：

{
    "match_score": <0-100整数>,
    "level": "<待提升/合格/良好/优秀/卓越>",
    "summary": "<一句话总评，指出最关键的匹配结论>",
    "breakdown": {
        "skill_match": { "score": <0-30>, "max": 30, "detail": "<具体分析>" },
        "experience_fit": { "score": <0-25>, "max": 25, "detail": "<具体分析>" },
        "quantified_results": { "score": <0-20>, "max": 20, "detail": "<具体分析>" },
        "expression_structure": { "score": <0-15>, "max": 15, "detail": "<具体分析>" },
        "differentiation": { "score": <0-10>, "max": 10, "detail": "<具体分析>" }
    },
    "strengths": ["<与岗位高度匹配的优势，每条具体到点>", "...最多5条"],
    "weaknesses": ["<明显不足或短板，每条说明影响>", "...最多5条"],
    "suggestions": [
        {"area": "<改进方向>", "priority": "<high/medium/low>", "action": "<具体改什么、怎么改>"},
        "...最多6条"
    ],
    "skill_gaps": {
        "missing_hard_skills": ["<缺少的核心硬技能>"],
        "missing_soft_skills": ["<缺失的软技能或素质>"],
        "nice_to_have": ["<加分项但非必须>"]
    },
    "interview_focus_areas": ["<建议面试重点考察的能力点>", "...3-5条"]
}
PROMPT;
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array<string,mixed>
     */
    private function callWithGovernance(array $messages): array
    {
        $startedAt = microtime(true);
        $attemptedDrivers = [];
        $lastException = null;

        foreach ($this->aiManager->orderedDrivers() as $driver) {
            if ($this->aiManager->isDriverCircuitOpen($driver, 'job_match')) {
                $attemptedDrivers[] = [
                    'driver' => $driver,
                    'status' => 'skipped',
                    'failure_type' => 'circuit_open',
                ];

                continue;
            }

            $attemptNumber = 1;

            while (true) {
                try {
                    $provider = $this->aiManager->provider($driver);
                    $payload = $this->callProviderChat($provider, $messages);

                    $this->aiManager->recordDriverSuccess($driver, 'job_match');

                    $attemptedDrivers[] = [
                        'driver' => $driver,
                        'status' => 'success',
                        'attempt' => $attemptNumber,
                    ];

                    $payload['__driver'] = $driver;
                    $payload['__attempted_drivers'] = $attemptedDrivers;

                    return $payload;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $classification = $this->aiManager->classifyException($exception);
                    $attemptedDrivers[] = [
                        'driver' => $driver,
                        'status' => 'failed',
                        'attempt' => $attemptNumber,
                        'failure_type' => $classification['type'],
                    ];

                    $this->aiManager->recordDriverFailure($driver, 'job_match', $exception);

                    if ($this->aiManager->shouldRetrySameDriverAfterBackoff($driver, $exception, $attemptNumber)) {
                        usleep($this->aiManager->resolveBackoffMilliseconds($exception, $attemptNumber) * 1000);
                        $attemptNumber++;

                        continue;
                    }

                    if ($this->aiManager->shouldRetryWithFallback($exception)) {
                        break;
                    }

                    throw $this->buildAnalysisException($exception, $attemptedDrivers, $driver, $startedAt);
                }
            }
        }

        throw $this->buildAnalysisException($lastException, $attemptedDrivers, null, $startedAt);
    }

    /**
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array<string,mixed>
     */
    private function callProviderChat(AiProvider $provider, array $messages): array
    {
        return $provider->chat($messages, [
            '_scene' => 'heavy',
        ]);
    }

    /**
     * @param  array<int,array<string,mixed>>  $attemptedDrivers
     */
    private function buildAnalysisException(
        ?Throwable $exception,
        array $attemptedDrivers,
        ?string $driver,
        float $startedAt,
    ): JobMatchingAnalysisException {
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        $classification = $exception instanceof Throwable
            ? $this->aiManager->classifyException($exception)
            : ['type' => 'all_providers_unavailable', 'retryable' => true];

        $message = $exception instanceof Throwable
            ? 'AI匹配分析失败: '.$exception->getMessage()
            : 'AI匹配分析失败：当前无可用 AI Provider。';

        return new JobMatchingAnalysisException(
            $message,
            $classification['type'],
            $classification['retryable'],
            $attemptedDrivers,
            $driver,
            null,
            $latencyMs,
            $exception,
        );
    }

    private function parseAndNormalize(array $raw): array
    {
        $data = is_array($raw) ? $raw : [];

        $matchScore = (int) ($data['match_score'] ?? $data['matchScore'] ?? 0);
        $matchScore = max(0, min(100, $matchScore));

        $level = (string) ($data['level'] ?? $this->inferLevel($matchScore));
        $summary = (string) ($data['summary'] ?? '');

        $breakdown = $this->normalizeBreakdown((array) ($data['breakdown'] ?? []));

        return [
            'match_score' => $matchScore,
            'level' => $level,
            'summary' => $summary,
            'breakdown' => $breakdown,
            'strengths' => $this->sanitizeList((array) ($data['strengths'] ?? []), 5),
            'weaknesses' => $this->sanitizeList((array) ($data['weaknesses'] ?? []), 5),
            'suggestions' => $this->normalizeSuggestions((array) ($data['suggestions'] ?? [])),
            'skill_gaps' => $this->normalizeSkillGaps((array) ($data['skill_gaps'] ?? [])),
            'interview_focus_areas' => $this->sanitizeList((array) ($data['interview_focus_areas'] ?? []), 5),
            '__audit' => [
                'driver' => (string) ($data['__driver'] ?? ''),
                'model' => (string) ($data['__provider_model'] ?? ''),
                'latency_ms' => (int) ($data['__latency_ms'] ?? 0),
                'attempted_drivers' => is_array($data['__attempted_drivers'] ?? null) ? $data['__attempted_drivers'] : [],
            ],
        ];
    }

    private function normalizeBreakdown(array $raw): array
    {
        $dimensions = ['skill_match', 'experience_fit', 'quantified_results', 'expression_structure', 'differentiation'];
        $defaults = [
            'skill_match' => ['max' => 30],
            'experience_fit' => ['max' => 25],
            'quantified_results' => ['max' => 20],
            'expression_structure' => ['max' => 15],
            'differentiation' => ['max' => 10],
        ];

        $result = [];
        foreach ($dimensions as $dim) {
            $item = (array) ($raw[$dim] ?? []);
            $def = $defaults[$dim];
            $result[$dim] = [
                'score' => max(0, min((int) ($item['score'] ?? 0), $def['max'])),
                'max_score' => $def['max'],
                'detail' => (string) ($item['detail'] ?? $item['deduction_reason'] ?? ''),
            ];
        }

        return $result;
    }

    private function normalizeSuggestions(array $raw): array
    {
        $result = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                $item = ['area' => '', 'priority' => 'medium', 'action' => (string) $item];
            }
            $result[] = [
                'area' => (string) ($item['area'] ?? $item['dimension'] ?? ''),
                'priority' => in_array((string) ($item['priority'] ?? ''), ['high', 'medium', 'low'])
                    ? (string) $item['priority']
                    : 'medium',
                'action' => (string) ($item['action'] ?? $item['text'] ?? $item['suggestion'] ?? ''),
            ];
        }

        return array_slice($result, 0, 6);
    }

    private function normalizeSkillGaps(array $raw): array
    {
        return [
            'missing_hard_skills' => $this->sanitizeList((array) ($raw['missing_hard_skills'] ?? []), 8),
            'missing_soft_skills' => $this->sanitizeList((array) ($raw['missing_soft_skills'] ?? []), 5),
            'nice_to_have' => $this->sanitizeList((array) ($raw['nice_to_have'] ?? []), 5),
        ];
    }

    private function sanitizeList(array $list, int $limit): array
    {
        return array_slice(
            array_filter(array_map(static fn ($item): string => trim((string) $item), $list), static fn (string $s): bool => $s !== ''),
            0,
            $limit
        );
    }

    private function inferLevel(int $score): string
    {
        return match (true) {
            $score >= 92 => '卓越',
            $score >= 82 => '优秀',
            $score >= 70 => '良好',
            $score >= 55 => '合格',
            default => '待提升',
        };
    }
}
