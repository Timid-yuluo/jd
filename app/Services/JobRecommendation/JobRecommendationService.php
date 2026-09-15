<?php

declare(strict_types=1);

namespace App\Services\JobRecommendation;

use App\Infrastructure\AI\AiManager;
use App\Models\ExternalRecruitment;
use App\Models\JobRecommendation;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class JobRecommendationService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 为用户生成每日推荐岗位
     *
     * @return array{generated:int,recommendations:array}
     */
    public function generateDailyRecommendations(User $user, ?Resume $resume = null, int $limit = 10): array
    {
        if (! $resume) {
            $resume = $user->resumes()->latest()->first();
        }
        if (! $resume) {
            return ['generated' => 0, 'recommendations' => []];
        }

        $resumeText = $resume->content_raw ?: $this->modulesToText($resume);
        $targetJob = $resume->target_job ?? '';

        // 从外部招聘池获取候选
        $candidates = ExternalRecruitment::where('review_status', 'approved')
            ->where('updated_at', '>', now()->subDays(7))
            ->when($targetJob, fn ($q) => $q->where('title', 'like', '%' . escapeLike($targetJob) . '%')
                ->orWhere('positions', 'like', '%' . escapeLike($targetJob) . '%'))
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        if ($candidates->isEmpty()) {
            return ['generated' => 0, 'recommendations' => []];
        }

        // AI 批量评分和排序
        $scored = $this->aiScoreJobs($resumeText, $candidates);

        $generated = 0;
        $recommendations = [];
        foreach ($scored as $item) {
            if ($generated >= $limit) {
                break;
            }

            $existing = JobRecommendation::where('user_id', $user->id)
                ->where('external_recruitment_id', $item['recruitment']->id)
                ->where('created_at', '>', now()->subDays(3))
                ->exists();
            if ($existing) {
                continue;
            }

            $rec = JobRecommendation::create([
                'user_id' => $user->id,
                'resume_id' => $resume->id,
                'external_recruitment_id' => $item['recruitment']->id,
                'job_title' => $item['recruitment']->title,
                'company' => $item['recruitment']->company,
                'city' => is_array($item['recruitment']->work_locations) ? ($item['recruitment']->work_locations[0] ?? null) : $item['recruitment']->work_location,
                'match_score' => $item['score'],
                'match_reasons' => $item['reasons'],
                'skill_gaps' => $item['gaps'],
            ]);

            $recommendations[] = $rec;
            $generated++;
        }

        return ['generated' => $generated, 'recommendations' => $recommendations];
    }

    /**
     * @param  string  $resumeText
     * @param  \Illuminate\Database\Eloquent\Collection  $candidates
     * @return array<int,array{recruitment:mixed,score:int,reasons:array,gaps:array}>
     */
    private function aiScoreJobs(string $resumeText, $candidates): array
    {
        $jobsList = $candidates->take(20)->map(function ($r, $i) {
            $payload = is_array($r->raw_payload) ? $r->raw_payload : [];
            return "[{$i}] {$r->title} @ {$r->company} | " . ($payload['description'] ?? $r->positions ?? '');
        })->implode("\n");

        $prompt = <<<PROMPT
请评估以下岗位与简历的匹配度，为每个岗位打分并说明理由：

**简历摘要**: {$resumeText}

**岗位列表**:
{$jobsList}

请输出 JSON 数组：
[
  {
    "index": 0,
    "score": 75,
    "reasons": ["匹配理由1", "匹配理由2"],
    "gaps": ["缺失技能1"]
  }
]
PROMPT;

        try {
            $result = $this->aiManager->providerWithFallback()->chat([
                ['role' => 'system', 'content' => '你是一位专业的招聘匹配专家，请以 JSON 格式回答。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.3, 'max_tokens' => 2000]);

            $content = (string) ($result['content'] ?? '');
            if (preg_match('/\[[\s\S]*\]/', $content, $m)) {
                $scores = json_decode($m[0], true) ?: [];
            } else {
                $scores = [];
            }
        } catch (\Throwable $e) {
            Log::warning('Job recommendation AI failed', ['error' => $e->getMessage()]);
            $scores = [];
        }

        $scored = [];
        foreach ($candidates->take(20) as $i => $r) {
            $scoreData = collect($scores)->firstWhere('index', $i);
            $score = $scoreData['score'] ?? 50;
            $scored[] = [
                'recruitment' => $r,
                'score' => (int) $score,
                'reasons' => $scoreData['reasons'] ?? [],
                'gaps' => $scoreData['gaps'] ?? [],
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored;
    }

    /**
     * 分析投递时机
     *
     * @return array{best_time:string,competition:string,advice:string}
     */
    public function analyzeApplyTiming(ExternalRecruitment $recruitment): array
    {
        $daysOld = $recruitment->source_created_at
            ? $recruitment->source_created_at->diffInDays(now())
            : 0;

        $bestTime = match (true) {
            $daysOld <= 2 => '立即投递（岗位刚发布，竞争较小）',
            $daysOld <= 7 => '建议尽快投递（发布一周内最佳）',
            $daysOld <= 14 => '可以投递（已发布两周，需突出匹配度）',
            $daysOld <= 30 => '谨慎投递（可能已进入面试阶段）',
            default => '可能已截止（建议先确认是否还在招）',
        };

        $competition = $daysOld <= 3 ? '低' : ($daysOld <= 7 ? '中' : '高');

        return [
            'best_time' => $bestTime,
            'competition' => $competition,
            'advice' => "岗位已发布 {$daysOld} 天。{$bestTime}。",
        ];
    }

    private function modulesToText(Resume $resume): string
    {
        $parts = [];
        foreach ($resume->modules as $m) {
            $data = is_array($m->data) ? $m->data : [];
            $parts[] = ($m->type ?? '') . ': ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $parts);
    }
}
