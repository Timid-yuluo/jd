<?php

declare(strict_types=1);

namespace App\Services\ResumeDiagnostic;

use App\Infrastructure\AI\AiManager;
use App\Models\Resume;
use App\Models\ResumeDiagnostic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ResumeDiagnosticService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 全面诊断简历
     */
    public function diagnose(Resume $resume): ResumeDiagnostic
    {
        $resumeText = $resume->content_raw ?: $this->modulesToText($resume);
        $modules = $resume->modules;

        // 规则检查（不依赖 AI）
        $ruleIssues = $this->ruleBasedCheck($resume, $modules);

        // AI 深度分析
        $aiResult = $this->aiDiagnose($resumeText, $resume->target_job ?? '');

        // 合并结果
        $dimensions = $this->buildDimensions($ruleIssues, $aiResult);
        $allIssues = array_merge($ruleIssues, $aiResult['issues'] ?? []);
        $fixPriority = $this->calculateFixPriority($allIssues);
        $overallScore = $this->calculateOverallScore($dimensions);

        // 同类简历基准对比
        $benchmark = $this->getBenchmark($resume);

        return ResumeDiagnostic::create([
            'user_id' => $resume->user_id,
            'resume_id' => $resume->id,
            'overall_score' => $overallScore,
            'dimensions' => $dimensions,
            'issues' => $allIssues,
            'fix_priority' => $fixPriority,
            'benchmark' => $benchmark,
        ]);
    }

    /**
     * 规则检查 — 不依赖 AI 的基础检测
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $modules
     * @return array<int,array{type:string,severity:string,message:string,section:string}>
     */
    private function ruleBasedCheck(Resume $resume, $modules): array
    {
        $issues = [];

        // 1. 完整性检查
        $moduleTypes = $modules->pluck('type')->unique()->toArray();
        $required = ['personal', 'objective', 'education', 'experience'];
        foreach ($required as $type) {
            if (! in_array($type, $moduleTypes, true)) {
                $issues[] = [
                    'type' => 'missing_module',
                    'severity' => 'high',
                    'message' => "缺少「{$this->moduleLabel($type)}」模块",
                    'section' => $type,
                ];
            }
        }

        // 2. 字数检查
        $totalChars = 0;
        foreach ($modules as $m) {
            $data = is_array($m->data) ? $m->data : [];
            $totalChars += mb_strlen(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        if ($totalChars < 200) {
            $issues[] = [
                'type' => 'too_short',
                'severity' => 'medium',
                'message' => "简历内容过少（{$totalChars} 字），建议补充更多细节",
                'section' => 'overall',
            ];
        }
        if ($totalChars > 5000) {
            $issues[] = [
                'type' => 'too_long',
                'severity' => 'low',
                'message' => "简历内容较多（{$totalChars} 字），ATS 系统可能截断",
                'section' => 'overall',
            ];
        }

        // 3. 联系方式检查
        $personalModule = $modules->firstWhere('type', 'personal');
        if ($personalModule) {
            $data = is_array($personalModule->data) ? $personalModule->data : [];
            $phone = (string) ($data['phone'] ?? '');
            $email = (string) ($data['email'] ?? '');
            if ($phone && ! preg_match('/^1[3-9]\d{9}$/', $phone)) {
                $issues[] = ['type' => 'invalid_phone', 'severity' => 'low', 'message' => '手机号格式不规范', 'section' => 'personal'];
            }
            if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $issues[] = ['type' => 'invalid_email', 'severity' => 'high', 'message' => '邮箱格式不正确', 'section' => 'personal'];
            }
        }

        // 4. 量化程度检查
        $experienceModules = $modules->where('type', 'experience');
        foreach ($experienceModules as $exp) {
            $data = is_array($exp->data) ? $exp->data : [];
            $content = (string) ($data['content'] ?? '');
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $allText = $content . ' ' . implode(' ', $items);
            if (! preg_match('/\d+%|\d+次|\d+个|\d+万|\d+x|\d+人|\d+小时/i', $allText)) {
                $issues[] = [
                    'type' => 'lack_quantification',
                    'severity' => 'medium',
                    'message' => '工作经历缺少量化数据（如百分比、次数、金额等）',
                    'section' => 'experience',
                ];
                break;
            }
        }

        // 5. 目标岗位检查
        if (empty($resume->target_job)) {
            $issues[] = [
                'type' => 'no_target_job',
                'severity' => 'medium',
                'message' => '未设置目标岗位，AI 优化精准度会降低',
                'section' => 'objective',
            ];
        }

        return $issues;
    }

    /**
     * AI 深度诊断
     *
     * @return array<string,mixed>
     */
    private function aiDiagnose(string $resumeText, string $targetJob): array
    {
        $prompt = <<<PROMPT
你是一位资深简历诊断专家。请对以下简历进行深度诊断：

**目标岗位**: {$targetJob ?: '通用'}
**简历内容**: {$resumeText}

请输出 JSON：
{
  "issues": [
    {"type": "issue_type", "severity": "high|medium|low", "message": "问题描述", "section": "affected_section"}
  ],
  "strengths": ["亮点1", "亮点2"],
  "keyword_analysis": {
    "matched": ["命中关键词"],
    "missing": ["缺失关键词"]
  },
  "structure_score": 75,
  "content_score": 70,
  "language_score": 80,
  "ats_compatibility_score": 72,
  "overall_suggestion": "综合改进建议（100字内）"
}
PROMPT;

        try {
            $result = $this->aiManager->providerWithFallback()->chat([
                ['role' => 'system', 'content' => '你是一位专业的简历诊断专家，请以 JSON 格式回答。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.3, 'max_tokens' => 2000]);

            $content = (string) ($result['content'] ?? '');
            if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
                return json_decode($m[0], true) ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('Resume diagnostic AI failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * @param  array  $ruleIssues
     * @param  array  $aiResult
     * @return array<string,int>
     */
    private function buildDimensions(array $ruleIssues, array $aiResult): array
    {
        $dimensions = [
            'completeness' => 100,
            'quantification' => 100,
            'keyword_coverage' => (int) ($aiResult['keyword_analysis'] ?? []) ? 60 : 50,
            'structure' => (int) ($aiResult['structure_score'] ?? 70),
            'content_quality' => (int) ($aiResult['content_score'] ?? 70),
            'language' => (int) ($aiResult['language_score'] ?? 75),
            'ats_compatibility' => (int) ($aiResult['ats_compatibility_score'] ?? 70),
        ];

        // 根据规则问题扣分
        foreach ($ruleIssues as $issue) {
            $severity = $issue['severity'] ?? 'low';
            $deduction = match ($severity) { 'high' => 20, 'medium' => 10, default => 5 };
            $section = $issue['section'] ?? 'overall';

            if ($section === 'overall' || in_array($section, ['personal', 'objective', 'education', 'experience'], true)) {
                $dimensions['completeness'] = max(0, $dimensions['completeness'] - $deduction);
            }
            if ($issue['type'] === 'lack_quantification') {
                $dimensions['quantification'] = max(0, $dimensions['quantification'] - $deduction);
            }
        }

        return $dimensions;
    }

    /**
     * @param  array  $issues
     * @return array<int,array{issue:array,impact:int,ease:int,priority:float}>
     */
    private function calculateFixPriority(array $issues): array
    {
        $prioritized = [];
        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'low';
            $impact = match ($severity) { 'high' => 90, 'medium' => 60, default => 30 };
            $ease = 70; // 默认修改难度中等
            if ($issue['type'] === 'missing_module') {
                $ease = 40; // 新增模块较难
            } elseif ($issue['type'] === 'invalid_email') {
                $ease = 95; // 修改邮箱很容易
            }
            $priority = ($impact * 0.6 + $ease * 0.4);
            $prioritized[] = [
                'issue' => $issue,
                'impact' => $impact,
                'ease' => $ease,
                'priority' => $priority,
            ];
        }
        usort($prioritized, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $prioritized;
    }

    /**
     * @return array{avg_modules:int,avg_chars:int,avg_ats:int,sample_size:int}
     */
    private function getBenchmark(Resume $resume): array
    {
        $stats = DB::table('resumes')
            ->selectRaw('COUNT(*) as count, AVG(LENGTH(content_raw)) as avg_chars')
            ->whereNotNull('content_raw')
            ->first();

        $moduleAvg = DB::table('resume_modules')
            ->selectRaw('COUNT(DISTINCT resume_id) as resume_count, COUNT(*) as module_count')
            ->first();

        return [
            'avg_modules' => $moduleAvg && $moduleAvg->resume_count > 0
                ? (int) round($moduleAvg->module_count / $moduleAvg->resume_count)
                : 0,
            'avg_chars' => $stats ? (int) ($stats->avg_chars ?? 0) : 0,
            'avg_ats' => (int) DB::table('resumes')->whereNotNull('ats_score')->avg('ats_score'),
            'sample_size' => $stats ? (int) $stats->count : 0,
        ];
    }

    private function calculateOverallScore(array $dimensions): int
    {
        if ($dimensions === []) {
            return 0;
        }

        return (int) round(array_sum($dimensions) / count($dimensions));
    }

    private function moduleLabel(string $type): string
    {
        return match ($type) {
            'personal' => '个人信息',
            'objective' => '求职意向',
            'education' => '教育背景',
            'experience' => '工作经历',
            'project' => '项目经验',
            'skill' => '技能特长',
            'certificate' => '荣誉证书',
            'summary' => '自我评价',
            default => $type,
        };
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
