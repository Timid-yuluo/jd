<?php

declare(strict_types=1);

namespace App\Services\Salary;

use App\Infrastructure\AI\AiManager;
use App\Models\SalaryNegotiationSession;
use App\Models\SalarySurvey;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SalaryService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 获取岗位薪资统计
     *
     * @return array{count:int,p25:int,p50:int,p75:int,avg:int,min:int,max:int,distribution:array}
     */
    public function getSalaryStats(string $jobTitle, ?string $city = null, ?string $experience = null): array
    {
        $query = SalarySurvey::where('job_title', 'like', '%' . escapeLike($jobTitle) . '%');

        if ($city) {
            $query->where('city', $city);
        }
        if ($experience) {
            $query->where('experience_level', $experience);
        }

        $records = $query->where('salary_max', '>', 0)->get();

        if ($records->isEmpty()) {
            return [
                'count' => 0, 'p25' => 0, 'p50' => 0, 'p75' => 0,
                'avg' => 0, 'min' => 0, 'max' => 0, 'distribution' => [],
            ];
        }

        $salaries = $records->map(fn ($r) => (int) (($r->salary_min + $r->salary_max) / 2))->sort()->values();

        $count = $salaries->count();
        $p25 = (int) $salaries->get((int) floor($count * 0.25));
        $p50 = (int) $salaries->get((int) floor($count * 0.50));
        $p75 = (int) $salaries->get((int) floor($count * 0.75));

        // 分段统计
        $distribution = [];
        $ranges = [[0, 5000], [5000, 10000], [10000, 15000], [15000, 25000], [25000, 40000], [40000, 1000000]];
        foreach ($ranges as [$lo, $hi]) {
            $label = $hi >= 1000000 ? "{$lo}万+" : ($lo >= 10000 ? floor($lo / 10000) . '-' . floor($hi / 10000) . '万' : "{$lo}-{$hi}");
            $distribution[$label] = $salaries->filter(fn ($s) => $s >= $lo && $s < $hi)->count();
        }

        return [
            'count' => $count,
            'p25' => $p25,
            'p50' => $p50,
            'p75' => $p75,
            'avg' => (int) $salaries->avg(),
            'min' => (int) $salaries->min(),
            'max' => (int) $salaries->max(),
            'distribution' => $distribution,
        ];
    }

    /**
     * AI 生成薪资谈判策略
     */
    public function generateNegotiationStrategy(
        User $user,
        string $jobTitle,
        ?string $company,
        int $currentSalary,
        int $targetSalary,
        ?string $city = null,
        ?string $experienceYears = null,
        ?string $extraContext = null
    ): SalaryNegotiationSession {
        $stats = $this->getSalaryStats($jobTitle, $city);

        $marketInfo = $stats['count'] > 0
            ? "市场薪资中位数 {$stats['p50']} 元/月，25 分位 {$stats['p25']}，75 分位 {$stats['p75']}。"
            : "暂无该岗位的市场薪资数据。";

        $prompt = <<<PROMPT
你是一位资深 HR 和薪资谈判专家。请基于以下信息生成薪资谈判策略：

**岗位**: {$jobTitle}
**公司**: {$company ?? '未知'}
**城市**: {$city ?? '未知'}
**当前薪资**: {$currentSalary} 元/月
**期望薪资**: {$targetSalary} 元/月
**工作经验**: {$experienceYears ?? '未知'} 年
**市场数据**: {$marketInfo}
**补充说明**: {$extraContext ?? '无'}

请输出 JSON 格式：
{
  "feasibility": "high|medium|low",
  "analysis": "对期望薪资合理性的分析（100字内）",
  "strategy": ["策略要点1", "策略要点2", ...],
  "talking_points": ["谈判话术1", "谈判话术2", ...],
  "fallback_plan": "如果对方拒绝的备选方案",
  "warnings": ["注意事项1", ...]
}
PROMPT;

        $aiResult = $this->callAi($prompt);

        $session = SalaryNegotiationSession::create([
            'user_id' => $user->id,
            'job_title' => $jobTitle,
            'company' => $company,
            'current_salary' => $currentSalary,
            'target_salary' => $targetSalary,
            'city' => $city,
            'experience_years' => $experienceYears,
            'context' => ['extra' => $extraContext, 'market_stats' => $stats],
            'ai_strategy' => $aiResult,
        ]);

        return $session;
    }

    /**
     * 从外部招聘数据聚合薪资信息
     */
    public function aggregateFromExternalRecruitments(): int
    {
        $count = 0;
        $recruitments = \App\Models\ExternalRecruitment::where('review_status', 'approved')
            ->whereNotNull('raw_payload')
            ->where('updated_at', '>', now()->subDays(30))
            ->limit(500)
            ->get();

        foreach ($recruitments as $r) {
            $payload = is_array($r->raw_payload) ? $r->raw_payload : [];
            $salaryText = (string) ($payload['salary'] ?? $payload['salary_range'] ?? '');

            if ($salaryText === '') {
                continue;
            }

            $parsed = $this->parseSalaryText($salaryText);
            if ($parsed['min'] === 0 && $parsed['max'] === 0) {
                continue;
            }

            $hash = md5($r->company . $r->title . $salaryText);
            if (SalarySurvey::where('source_hash', $hash)->exists()) {
                continue;
            }

            SalarySurvey::create([
                'job_title' => $r->title ?? '未知',
                'company' => $r->company,
                'city' => is_array($r->work_locations) ? ($r->work_locations[0] ?? null) : $r->work_location,
                'industry' => $r->industry,
                'salary_min' => $parsed['min'],
                'salary_max' => $parsed['max'],
                'source' => 'external_recruitment',
                'source_hash' => $hash,
                'reported_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * 解析薪资文本
     *
     * @return array{min:int,max:int}
     */
    private function parseSalaryText(string $text): array
    {
        $text = str_replace(['薪', '资', '/', '月', '年', '起', '以上', '元', ' ', 'K', 'k', '万'], ['', '', '', '', '', '', '', '', '', '000', '000', '0000'], $text);

        if (preg_match('/(\d+)-(\d+)/', $text, $m)) {
            return ['min' => (int) $m[1], 'max' => (int) $m[2]];
        }
        if (preg_match('/(\d+)/', $text, $m)) {
            return ['min' => (int) $m[1], 'max' => (int) $m[1]];
        }

        return ['min' => 0, 'max' => 0];
    }

    /**
     * @return array<string,mixed>
     */
    private function callAi(string $prompt): array
    {
        try {
            $result = $this->aiManager->providerWithFallback()->chat([
                ['role' => 'system', 'content' => '你是一位专业的薪资谈判顾问，请以 JSON 格式回答。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.7, 'max_tokens' => 2000]);

            $content = (string) ($result['content'] ?? '');
            // 尝试解析 JSON
            if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
                return json_decode($m[0], true) ?? ['raw' => $content];
            }

            return ['raw' => $content];
        } catch (\Throwable $e) {
            Log::warning('Salary negotiation AI failed', ['error' => $e->getMessage()]);

            return ['error' => 'AI 分析暂时不可用，请稍后重试。'];
        }
    }
}
