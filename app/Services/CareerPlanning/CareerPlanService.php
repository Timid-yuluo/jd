<?php

declare(strict_types=1);

namespace App\Services\CareerPlanning;

use App\Infrastructure\AI\AiManager;
use App\Models\CareerPlan;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class CareerPlanService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 生成职业发展路径
     */
    public function generateCareerPath(
        User $user,
        ?Resume $resume = null,
        string $currentRole = '',
        string $targetRole = '',
        string $timeline = '3y'
    ): CareerPlan {
        $resumeText = '';
        if ($resume) {
            $resumeText = $resume->content_raw ?: $this->modulesToText($resume);
        }

        $prompt = <<<PROMPT
你是一位资深职业规划师。请基于以下信息生成职业发展路径：

**当前角色**: {$currentRole ?: '未知（请从简历推断）'}
**目标角色**: {$targetRole ?: '未知（请根据简历推荐合适的方向）'}
**时间范围**: {$timeline}
**简历摘要**: {$resumeText}

请输出 JSON 格式：
{
  "path_summary": "职业路径概述（200字内）",
  "path_nodes": [
    {"stage": "阶段1", "duration": "0-6个月", "role": "角色", "key_skills": ["技能1"], "goals": ["目标1"]}
  ],
  "skill_gaps": [
    {"skill": "缺失技能", "priority": "high|medium|low", "learning_resources": ["资源1"]}
  ],
  "salary_forecast": [
    {"year": 1, "min": 0, "max": 0, "note": "说明"},
    {"year": 3, "min": 0, "max": 0, "note": "说明"},
    {"year": 5, "min": 0, "max": 0, "note": "说明"}
  ],
  "milestones": [
    {"milestone": "里程碑", "target_date": "时间", "success_criteria": "成功标准"}
  ],
  "advice": "综合建议（100字内）"
}
PROMPT;

        $aiResult = $this->callAi($prompt);

        return CareerPlan::create([
            'user_id' => $user->id,
            'resume_id' => $resume?->id,
            'current_role' => $currentRole,
            'target_role' => $targetRole,
            'timeline' => $timeline,
            'ai_path' => $aiResult['path_nodes'] ?? null,
            'skill_gaps' => $aiResult['skill_gaps'] ?? null,
            'salary_forecast' => $aiResult['salary_forecast'] ?? null,
            'milestones' => $aiResult['milestones'] ?? null,
        ]);
    }

    /**
     * 技能差距分析
     *
     * @return array{gaps: array, recommendations: array}
     */
    public function analyzeSkillGaps(Resume $resume, string $targetJobDescription): array
    {
        $resumeText = $resume->content_raw ?: $this->modulesToText($resume);

        $prompt = <<<PROMPT
请分析简历与目标岗位之间的技能差距：

**简历内容**: {$resumeText}

**目标岗位 JD**: {$targetJobDescription}

请输出 JSON：
{
  "matched_skills": ["已具备的技能"],
  "missing_skills": [
    {"skill": "缺失技能", "importance": "high|medium|low", "learn_time": "预估学习时间", "resource": "推荐资源"}
  ],
  "transferable_skills": ["可迁移技能"],
  "overall_match": 75,
  "recommendation": "优先学习建议（100字内）"
}
PROMPT;

        return $this->callAi($prompt);
    }

    /**
     * 转行评估
     *
     * @return array<string,mixed>
     */
    public function evaluateCareerChange(User $user, string $fromIndustry, string $toIndustry, ?Resume $resume = null): array
    {
        $resumeText = $resume ? ($resume->content_raw ?: $this->modulesToText($resume)) : '无简历';

        $prompt = <<<PROMPT
你是一位职业转型顾问。请评估转行可行性：

**当前行业**: {$fromIndustry}
**目标行业**: {$toIndustry}
**简历摘要**: {$resumeText}

请输出 JSON：
{
  "feasibility": "high|medium|low",
  "difficulty_level": 7,
  "transferable_skills": ["可迁移技能"],
  "missing_skills": ["缺失技能"],
  "transition_roles": ["推荐的过渡岗位"],
  "timeline": "预估转型时间",
  "risks": ["风险点"],
  "action_plan": ["第一步", "第二步"],
  "salary_impact": "薪资影响说明",
  "advice": "总结建议"
}
PROMPT;

        return $this->callAi($prompt);
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

    /**
     * @return array<string,mixed>
     */
    private function callAi(string $prompt): array
    {
        try {
            $result = $this->aiManager->providerWithFallback()->chat([
                ['role' => 'system', 'content' => '你是一位专业的职业规划顾问，请以 JSON 格式回答。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.7, 'max_tokens' => 3000]);

            $content = (string) ($result['content'] ?? '');
            if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
                return json_decode($m[0], true) ?? ['raw' => $content];
            }

            return ['raw' => $content];
        } catch (\Throwable $e) {
            Log::warning('Career plan AI failed', ['error' => $e->getMessage()]);

            return ['error' => 'AI 分析暂时不可用，请稍后重试。'];
        }
    }
}
