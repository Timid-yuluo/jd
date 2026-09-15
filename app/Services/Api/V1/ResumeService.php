<?php

declare(strict_types=1);

namespace App\Services\Api\V1;

use App\Application\Actions\Resume\CreateResumeAction;
use App\Application\Actions\Resume\DeleteResumeAction;
use App\Application\Actions\Resume\PaginateResumesAction;
use App\Application\Actions\Resume\UpdateResumeAction;
use App\Infrastructure\AI\AiManager;
use App\Models\Resume;
use App\Services\Api\Concerns\AssertsOwnership;
use App\Services\UsageLogger;
use Illuminate\Http\Request;

final class ResumeService
{
    use AssertsOwnership;

    public function __construct(
        private readonly AiManager $aiManager,
        private readonly PaginateResumesAction $paginateResumesAction,
        private readonly CreateResumeAction $createResumeAction,
        private readonly UpdateResumeAction $updateResumeAction,
        private readonly DeleteResumeAction $deleteResumeAction,
    ) {}

    /**
     * @return array{data:array<string,mixed>,meta:array<string,mixed>}
     */
    public function paginate(int $userId, Request $request): array
    {
        $page = max(1, (int) $request->input('page.number', 1));
        $size = min(50, max(1, (int) $request->input('page.size', 10)));

        $paginator = $this->paginateResumesAction->execute($userId, $page, $size);

        return [
            'data' => [
                'items' => $paginator->items(),
            ],
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
    public function create(int $userId, array $payload): Resume
    {
        return $this->createResumeAction->execute($userId, $payload);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function update(Resume $resume, array $payload): Resume
    {
        return $this->updateResumeAction->execute($resume, $payload);
    }

    public function delete(Resume $resume): void
    {
        $this->deleteResumeAction->execute($resume);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function import(int $userId, array $payload): Resume
    {
        return $this->createResumeAction->execute($userId, [
            'title' => (string) $payload['title'],
            'target_job' => $payload['target_job'] ?? null,
            'content_raw' => (string) $payload['source_text'],
            'content_structured' => [
                'summary' => mb_substr((string) $payload['source_text'], 0, 300),
            ],
        ]);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function optimize(Resume $resume, array $payload): array
    {
        $targetJob = (string) $payload['target_job'];
        $contentRaw = (string) ($resume->content_raw ?? '');
        $options = [
            'target_company' => is_string($payload['target_company'] ?? null) ? $payload['target_company'] : '',
            'target_job_title' => is_string($payload['target_job_title'] ?? null) ? $payload['target_job_title'] : '',
            'target_job_description' => is_string($payload['target_job_description'] ?? null) ? $payload['target_job_description'] : '',
            'optimize_goals' => is_array($payload['optimize_goals'] ?? null) ? $payload['optimize_goals'] : [],
        ];
        $result = $this->aiManager->provider()->optimizeResume($contentRaw, $targetJob, $options);

        $resume->forceFill([
            'target_job' => $targetJob,
            'target_company' => $options['target_company'] ?: null,
            'target_job_title' => $options['target_job_title'] ?: null,
            'target_job_description' => $options['target_job_description'] ?: null,
            'optimize_goals' => ! empty($options['optimize_goals']) ? $options['optimize_goals'] : null,
            'optimized_text' => $result['optimized_text'],
            'highlights' => $result['highlights'],
        ])->save();

        UsageLogger::log(
            $resume->user_id,
            'resume_optimize',
            config('ai.default'),
            $result,
            ['resume_id' => $resume->id],
        );

        return [
            'target_job' => $targetJob,
            'optimized_text' => $result['optimized_text'],
            'highlights' => $result['highlights'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function atsScore(Resume $resume, ?string $contentRaw = null): array
    {
        $targetJob = (string) ($resume->target_job ?? '');
        $enrichedJob = $targetJob;
        $jdKeywords = [];

        // 自动关联最近一次岗位匹配分析的 JD 关键词
        $latestAnalysis = \App\Models\JobMatchAnalysis::query()
            ->where('user_id', $resume->user_id)
            ->where('resume_id', $resume->id)
            ->whereNotNull('job_description')
            ->where('job_description', '!=', '')
            ->latest()
            ->first();
        if ($latestAnalysis) {
            $jdKeywords = \App\Services\Interview\InterviewReportService::extractJdKeywords((string) $latestAnalysis->job_description);
            if (!empty($jdKeywords)) {
                $enrichedJob .= ' | 岗位关键词: ' . implode('、', array_slice($jdKeywords, 0, 15));
            }
        }

        $startMs = (int) (microtime(true) * 1000);
        $result = $this->aiManager->provider()->scoreResume($contentRaw ?? (string) $resume->content_raw, $enrichedJob);
        $latencyMs = (int) (microtime(true) * 1000) - $startMs;

        // P0-1: 总分范围校验，clamp 到 [0, 100]
        $rawScore = is_numeric($result['score'] ?? null) ? (int) $result['score'] : 0;
        $score = max(0, min(100, $rawScore));

        $level = is_string($result['level'] ?? null) ? $result['level'] : $this->scoreToLevel($score);
        $summary = is_string($result['summary'] ?? null) ? $result['summary'] : '';
        $rawSuggestions = $result['suggestions'] ?? [];
        $suggestions = [];

        if (is_array($rawSuggestions)) {
            foreach ($rawSuggestions as $suggestion) {
                if (is_array($suggestion) && isset($suggestion['text'])) {
                    $suggestions[] = [
                        'dimension' => (string) ($suggestion['dimension'] ?? ''),
                        'priority' => (string) ($suggestion['priority'] ?? 'medium'),
                        'text' => trim((string) $suggestion['text']),
                    ];
                } elseif (is_string($suggestion) && trim($suggestion) !== '') {
                    $suggestions[] = [
                        'dimension' => '',
                        'priority' => 'medium',
                        'text' => trim($suggestion),
                    ];
                }
            }
        }

        $breakdown = $this->normalizeAtsBreakdown($result['breakdown'] ?? null);
        $moduleScores = $this->normalizeModuleScores($result['module_scores'] ?? null);

        // P0-2: 一致性校验 — breakdown 合计与总分偏差 >15 时按 breakdown 合计修正
        $breakdownSum = array_sum(array_map(static fn (array $item): int => (int) $item['score'], $breakdown));
        if (abs($score - $breakdownSum) > 15) {
            \Illuminate\Support\Facades\Log::warning('ATS score-breakdown mismatch', [
                'resume_id' => $resume->id,
                'raw_score' => $rawScore,
                'clamped_score' => $score,
                'breakdown_sum' => $breakdownSum,
                'latency_ms' => $latencyMs,
            ]);
            $score = max(0, min(100, $breakdownSum));
            $level = $this->scoreToLevel($score);
        }

        $contentStructured = is_array($resume->content_structured) ? $resume->content_structured : [];
        $previousHistory = is_array($contentStructured['ats']['history'] ?? null) ? $contentStructured['ats']['history'] : [];
        $previousHistory[] = [
            'score' => $score,
            'level' => $level,
            'scored_at' => now()->toIso8601String(),
        ];
        // P1-7: 评分历史扩展到 20 条
        $history = array_slice($previousHistory, -20);

        $contentStructured['ats'] = [
            'score' => $score,
            'level' => $level,
            'summary' => $summary,
            'target_job' => (string) ($resume->target_job ?? ''),
            'suggestions' => $suggestions,
            'breakdown' => $breakdown,
            'module_scores' => $moduleScores,
            'scored_at' => now()->toIso8601String(),
            'history' => $history,
            'used_jd_keywords' => $latestAnalysis ? array_slice($jdKeywords ?? [], 0, 15) : [],
            'latency_ms' => $latencyMs,
        ];

        $resume->forceFill([
            'ats_score' => $score,
            'content_structured' => $contentStructured,
        ])->save();

        // P0-3: 增量更新百分位缓存
        $this->incrementPercentileCache($score);

        UsageLogger::log(
            $resume->user_id,
            'resume_ats',
            config('ai.default'),
            $result,
            ['resume_id' => $resume->id],
        );

        return [
            'score' => $score,
            'level' => $level,
            'summary' => $summary,
            'suggestions' => $suggestions,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * P0-3: 增量更新百分位缓存，避免全表扫描
     */
    private function incrementPercentileCache(int $score): void
    {
        $cacheKey = 'ats_percentile_data';
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($cached === null) {
            // 首次初始化：全量计算
            $total = Resume::query()->whereNotNull('ats_score')->count();
            $distribution = Resume::query()
                ->whereNotNull('ats_score')
                ->selectRaw('ats_score, COUNT(*) as cnt')
                ->groupBy('ats_score')
                ->pluck('cnt', 'ats_score')
                ->all();
            $cached = ['total' => $total, 'distribution' => $distribution];
        } else {
            // 增量更新
            $cached['total'] = ($cached['total'] ?? 0) + 1;
            $cached['distribution'][$score] = ($cached['distribution'][$score] ?? 0) + 1;
        }

        // 缓存 6 小时，定时任务会定期全量刷新
        \Illuminate\Support\Facades\Cache::put($cacheKey, $cached, 21600);
    }

    private function scoreToLevel(int $score): string
    {
        return match (true) {
            $score >= 85 => '优秀',
            $score >= 72 => '良好',
            $score >= 60 => '合格',
            default => '待提升',
        };
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function normalizeAtsBreakdown(mixed $rawBreakdown): array
    {
        $dimensions = [
            'keyword_match' => ['label' => '关键词与硬技能匹配', 'max_score' => 20],
            'quantified_results' => ['label' => '量化成果与数据支撑', 'max_score' => 20],
            'star_structure' => ['label' => 'STAR结构与逻辑清晰度', 'max_score' => 15],
            'professional_format' => ['label' => '专业表达与格式规范', 'max_score' => 15],
            'relevance_focus' => ['label' => '内容相关性与聚焦度', 'max_score' => 15],
            'competitive_edge' => ['label' => '竞争力与差异化亮点', 'max_score' => 15],
        ];

        // 兼容旧版 4 维度数据
        $legacyMap = [
            'star_quantified' => 'quantified_results',
        ];

        $normalized = [];
        foreach ($dimensions as $key => $meta) {
            $rawItem = [];
            if (is_array($rawBreakdown)) {
                if (isset($rawBreakdown[$key]) && is_array($rawBreakdown[$key])) {
                    $rawItem = $rawBreakdown[$key];
                } else {
                    // 尝试从旧维度名映射
                    foreach ($legacyMap as $oldKey => $newKey) {
                        if ($newKey === $key && isset($rawBreakdown[$oldKey]) && is_array($rawBreakdown[$oldKey])) {
                            $rawItem = $rawBreakdown[$oldKey];
                            break;
                        }
                    }
                }
            }

            $score = isset($rawItem['score']) && is_numeric($rawItem['score']) ? (int) $rawItem['score'] : 0;
            $maxScore = isset($rawItem['max_score']) && is_numeric($rawItem['max_score'])
                ? (int) $rawItem['max_score']
                : $meta['max_score'];
            $maxScore = max(1, $maxScore);
            $score = min(max(0, $score), $maxScore);

            $deductionReason = isset($rawItem['deduction_reason']) && is_string($rawItem['deduction_reason'])
                ? trim($rawItem['deduction_reason'])
                : '';
            if ($deductionReason === '') {
                $deductionReason = '暂无明确扣分说明。';
            }

            $normalized[$key] = [
                'label' => $meta['label'],
                'score' => $score,
                'max_score' => $maxScore,
                'percent' => (int) round(($score / $maxScore) * 100),
                'deduction_reason' => $deductionReason,
            ];
        }

        return $normalized;
    }

    private function normalizeModuleScores(mixed $raw): array
    {
        $defaults = [
            'education' => ['label' => '教育经历', 'max_score' => 10],
            'experience' => ['label' => '实习经历', 'max_score' => 10],
            'project' => ['label' => '项目经验', 'max_score' => 10],
            'skill' => ['label' => '技能证书', 'max_score' => 10],
            'certificate' => ['label' => '获奖情况', 'max_score' => 10],
        ];

        $normalized = [];
        foreach ($defaults as $key => $meta) {
            $rawItem = is_array($raw) && is_array($raw[$key] ?? null) ? $raw[$key] : [];
            $score = isset($rawItem['score']) && is_numeric($rawItem['score']) ? (int) $rawItem['score'] : 0;
            $maxScore = max(1, isset($rawItem['max_score']) && is_numeric($rawItem['max_score']) ? (int) $rawItem['max_score'] : $meta['max_score']);
            $score = min(max(0, $score), $maxScore);
            $comment = isset($rawItem['comment']) && is_string($rawItem['comment']) ? trim($rawItem['comment']) : '';

            $normalized[$key] = [
                'label' => $meta['label'],
                'score' => $score,
                'max_score' => $maxScore,
                'percent' => (int) round(($score / $maxScore) * 100),
                'comment' => $comment,
            ];
        }

        return $normalized;
    }
}
