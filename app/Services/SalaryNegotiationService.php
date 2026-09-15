<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\AI\AiManager;
use App\Models\SalaryNegotiationSession;
use App\Models\SalarySurvey;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * 薪资谈判助手服务
 *
 * 职责：
 * 1. 查询薪资统计数据（P25/P50/P75）
 * 2. 构造 AI Prompt 调用谈判分析
 * 3. 解析返回结果并写入 session
 *
 * 关联文档：docs/features-development-plan.md §2.2.2
 */
final class SalaryNegotiationService
{
    /** 薪资统计缓存有效期（秒） */
    private const STATS_CACHE_TTL = 3600;

    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 查询薪资统计数据
     *
     * @return array{
     *   count: int,
     *   p25: float,
     *   p50: float,
     *   p75: float,
     *   min: int,
     *   max: int,
     *   avg: float,
     *   samples: array<int, array<string, mixed>>
     * }
     */
    public function getSalaryStats(string $jobTitle, ?string $city = null, ?string $experienceLevel = null): array
    {
        $cacheKey = $this->buildStatsCacheKey($jobTitle, $city, $experienceLevel);

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($jobTitle, $city, $experienceLevel) {
            $query = SalarySurvey::query()
                ->where('job_title', 'like', '%'.escapeLike($jobTitle).'%');

            if ($city !== null && $city !== '') {
                $query->where('city', $city);
            }
            if ($experienceLevel !== null && $experienceLevel !== '') {
                $query->where('experience_level', $experienceLevel);
            }

            $records = $query->orderByDesc('reported_at')->limit(200)->get();

            if ($records->isEmpty()) {
                return [
                    'count' => 0,
                    'p25' => 0.0,
                    'p50' => 0.0,
                    'p75' => 0.0,
                    'min' => 0,
                    'max' => 0,
                    'avg' => 0.0,
                    'samples' => [],
                ];
            }

            // 计算每条记录的平均薪资用于百分位计算
            $salaries = $records->map(fn ($r) => ($r->salary_min + $r->salary_max) / 2)->sort()->values();

            return [
                'count' => $records->count(),
                'p25' => $this->percentile($salaries, 25),
                'p50' => $this->percentile($salaries, 50),
                'p75' => $this->percentile($salaries, 75),
                'min' => (int) $salaries->min(),
                'max' => (int) $salaries->max(),
                'avg' => round($salaries->avg(), 0),
                'samples' => $records->take(10)->map(fn ($r) => [
                    'company' => $r->company,
                    'city' => $r->city,
                    'salary_min' => $r->salary_min,
                    'salary_max' => $r->salary_max,
                    'experience_level' => $r->experience_level,
                    'reported_at' => $r->reported_at?->format('Y-m-d'),
                ])->toArray(),
            ];
        });
    }

    /**
     * 启动薪资谈判分析
     *
     * @param  array<string, mixed>  $params  谈判参数
     * @return SalaryNegotiationSession 创建的会话
     */
    public function startNegotiation(User $user, array $params): SalaryNegotiationSession
    {
        // 查询市场薪资数据作为参考
        $marketStats = $this->getSalaryStats(
            $params['job_title'],
            $params['city'] ?? null,
            $params['experience_years'] ?? null,
        );

        // 调用 AI 分析
        $aiResult = $this->callAiAnalysis($params, $marketStats);

        // 创建会话记录
        $session = SalaryNegotiationSession::create([
            'user_id' => $user->id,
            'job_title' => $params['job_title'],
            'company' => $params['company'] ?? null,
            'current_salary' => $params['current_salary'],
            'target_salary' => $params['target_salary'],
            'city' => $params['city'] ?? null,
            'experience_years' => $params['experience_years'] ?? null,
            'context' => [
                'user_context' => $params['context'] ?? null,
                'market_stats' => $marketStats,
            ],
            'ai_strategy' => $aiResult['strategy'] ?? null,
            'ai_dialogue' => $aiResult['dialogue'] ?? null,
        ]);

        return $session;
    }

    /**
     * 调用 AI 进行谈判分析
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $marketStats
     * @return array{strategy: array<string, mixed>|null, dialogue: array<string, mixed>|null}
     */
    private function callAiAnalysis(array $params, array $marketStats): array
    {
        $messages = $this->buildAiMessages($params, $marketStats);

        try {
            $result = $this->aiManager->provider()->chat($messages, [
                'temperature' => 0.7,
                'response_format' => 'json',
            ]);

            return $this->parseAiResult($result);
        } catch (Throwable $e) {
            Log::error('薪资谈判 AI 分析失败', [
                'job_title' => $params['job_title'],
                'error' => $e->getMessage(),
            ]);

            return ['strategy' => null, 'dialogue' => null];
        }
    }

    /**
     * 构建 AI 分析消息
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $marketStats
     * @return array<int, array{role: string, content: string}>
     */
    private function buildAiMessages(array $params, array $marketStats): array
    {
        $marketText = $marketStats['count'] > 0
            ? sprintf(
                "市场参考：P25=%s, P50=%s, P75=%s, 平均=%s（样本 %d 条）",
                number_format($marketStats['p25']),
                number_format($marketStats['p50']),
                number_format($marketStats['p75']),
                number_format($marketStats['avg']),
                $marketStats['count'],
            )
            : '暂无市场薪资数据';

        // heredoc 不支持 ?? 操作符，预先提取变量
        $jobTitle = $params['job_title'];
        $company = $params['company'] ?? '未指定';
        $city = $params['city'] ?? '未指定';
        $experienceYears = $params['experience_years'] ?? '未指定';
        $currentSalary = $params['current_salary'];
        $targetSalary = $params['target_salary'];
        $context = $params['context'] ?? '无';

        $systemPrompt = <<<'PROMPT'
你是资深HR薪酬顾问，拥有10年+大厂谈薪经验。请基于用户信息给出薪资谈判建议。
分析必须数据驱动、可执行，返回严格 JSON 格式。
PROMPT;

        $userPrompt = <<<PROMPT
## 用户信息
岗位：{$jobTitle}
公司：{$company}
城市：{$city}
经验：{$experienceYears}
当前薪资：{$currentSalary}
期望薪资：{$targetSalary}
补充信息：{$context}

## 市场数据
{$marketText}

请返回 JSON：
{
    "strategy": {
        "assessment": "<薪资合理性评估：合理/偏高/偏低>",
        "assessment_reason": "<评估原因>",
        "negotiation_space": "<谈判空间分析>",
        "strategies": [
            {
                "name": "<策略名称>",
                "steps": ["<执行步骤1>", "<执行步骤2>"],
                "script": "<核心话术>"
            }
        ],
        "risks": ["<风险点1>", "<风险点2>"],
        "tips": ["<额外建议1>", "<额外建议2>"]
    },
    "dialogue": {
        "scenes": [
            {
                "hr_says": "<HR提问>",
                "you_reply": "<你的应对话术>",
                "purpose": "<此应对的目的>"
            }
        ]
    }
}
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    /**
     * 解析 AI 返回结果
     *
     * @param  array<string, mixed>  $result
     * @return array{strategy: array<string, mixed>|null, dialogue: array<string, mixed>|null}
     */
    private function parseAiResult(array $result): array
    {
        $content = $result['content'] ?? '';
        $decoded = json_decode($content, true) ?? [];

        return [
            'strategy' => $decoded['strategy'] ?? null,
            'dialogue' => $decoded['dialogue'] ?? null,
        ];
    }

    /**
     * 计算百分位数
     */
    private function percentile(\Illuminate\Support\Collection $sorted, float $percentile): float
    {
        if ($sorted->isEmpty()) {
            return 0.0;
        }

        $count = $sorted->count();
        $index = ($percentile / 100) * ($count - 1);

        return round($sorted[(int) round($index)], 0);
    }

    /**
     * 构建统计缓存键
     */
    private function buildStatsCacheKey(string $jobTitle, ?string $city, ?string $experienceLevel): string
    {
        return 'salary_stats:'.md5($jobTitle.'|'.($city ?? '').'|'.($experienceLevel ?? ''));
    }
}
