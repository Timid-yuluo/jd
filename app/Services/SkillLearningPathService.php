<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\AI\AiManager;
use App\Models\SkillAssessment;
use App\Models\SkillLearningPath;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 技能评估与学习路径服务
 *
 * 职责：
 * 1. 技能雷达图数据生成
 * 2. AI 抓取目标岗位要求技能
 * 3. 差距分析
 * 4. AI 生成学习路径
 *
 * 关联文档：docs/features-development-plan.md §4.2
 */
final class SkillLearningPathService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 获取用户技能雷达图数据
     *
     * @return array{
     *   hard_skills: array<int, array{name: string, proficiency: int}>,
     *   soft_skills: array<int, array{name: string, proficiency: int}>,
     *   avg_hard: float,
     *   avg_soft: float,
     *   total: int
     * }
     */
    public function getRadarData(User $user): array
    {
        $skills = SkillAssessment::where('user_id', $user->id)->get();

        $hard = $skills->where('skill_category', SkillAssessment::CATEGORY_HARD)
            ->map(fn ($s) => ['name' => $s->skill_name, 'proficiency' => $s->proficiency])
            ->values()
            ->toArray();

        $soft = $skills->where('skill_category', SkillAssessment::CATEGORY_SOFT)
            ->map(fn ($s) => ['name' => $s->skill_name, 'proficiency' => $s->proficiency])
            ->values()
            ->toArray();

        $avgHard = $skills->where('skill_category', SkillAssessment::CATEGORY_HARD)->avg('proficiency') ?: 0;
        $avgSoft = $skills->where('skill_category', SkillAssessment::CATEGORY_SOFT)->avg('proficiency') ?: 0;

        return [
            'hard_skills' => $hard,
            'soft_skills' => $soft,
            'avg_hard' => round((float) $avgHard, 1),
            'avg_soft' => round((float) $avgSoft, 1),
            'total' => $skills->count(),
        ];
    }

    /**
     * 分析技能差距并生成学习路径
     *
     * @param  User  $user  用户
     * @param  array{target_job: string, city?: ?string, experience_years?: ?string}  $params
     * @return SkillLearningPath 学习路径记录
     */
    public function analyzeAndCreatePath(User $user, array $params): SkillLearningPath
    {
        // 1. 获取当前技能快照
        $currentSkills = SkillAssessment::where('user_id', $user->id)->get();
        $currentSnapshot = $currentSkills->map(fn ($s) => [
            'name' => $s->skill_name,
            'category' => $s->skill_category,
            'proficiency' => $s->proficiency,
            'years_used' => $s->years_used,
        ])->toArray();

        // 2. 调用 AI 分析
        $aiResult = $this->callAiAnalysis($params['target_job'], $currentSnapshot, $params);

        // 3. 计算总时长
        $totalWeeks = 0;
        if (isset($aiResult['path']) && is_array($aiResult['path'])) {
            foreach ($aiResult['path'] as $phase) {
                $totalWeeks += (int) ($phase['duration_weeks'] ?? 0);
            }
        }

        // 4. 创建学习路径记录
        $path = SkillLearningPath::create([
            'user_id' => $user->id,
            'target_job' => $params['target_job'],
            'current_snapshot' => $currentSnapshot,
            'required_skills' => $aiResult['required_skills'] ?? null,
            'gap_analysis' => $aiResult['gap_analysis'] ?? null,
            'ai_path' => $aiResult['path'] ?? null,
            'total_duration_weeks' => $totalWeeks,
            'status' => SkillLearningPath::STATUS_ACTIVE,
        ]);

        // 5. 归档该用户之前的活跃路径
        SkillLearningPath::where('user_id', $user->id)
            ->where('id', '!=', $path->id)
            ->where('status', SkillLearningPath::STATUS_ACTIVE)
            ->update(['status' => SkillLearningPath::STATUS_ARCHIVED]);

        return $path;
    }

    /**
     * 调用 AI 分析
     *
     * @param  string  $targetJob  目标岗位
     * @param  array<int, array<string, mixed>>  $currentSkills  当前技能
     * @param  array<string, mixed>  $params  其他参数
     * @return array<string, mixed>
     */
    private function callAiAnalysis(string $targetJob, array $currentSkills, array $params): array
    {
        $messages = $this->buildAiMessages($targetJob, $currentSkills, $params);

        try {
            $result = $this->aiManager->provider()->chat($messages, [
                'temperature' => 0.6,
                'response_format' => 'json',
            ]);

            return $this->parseAiResult($result);
        } catch (Throwable $e) {
            Log::error('学习路径 AI 分析失败', [
                'target_job' => $targetJob,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 构建 AI 消息
     *
     * @param  array<int, array<string, mixed>>  $currentSkills
     * @param  array<string, mixed>  $params
     * @return array<int, array{role: string, content: string}>
     */
    private function buildAiMessages(string $targetJob, array $currentSkills, array $params): array
    {
        $skillsJson = json_encode($currentSkills, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $city = $params['city'] ?? '未指定';
        $experience = $params['experience_years'] ?? '未指定';

        $systemPrompt = <<<'PROMPT'
你是资深技术招聘专家和职业规划师，熟悉各岗位的技能要求。
请基于用户当前技能和目标岗位，分析差距并生成可执行的学习路径。
返回严格 JSON 格式。
PROMPT;

        $userPrompt = <<<PROMPT
## 目标岗位
{$targetJob}
城市：{$city}
经验：{$experience}

## 用户当前技能
{$skillsJson}

请返回 JSON：
{
    "required_skills": [
        {"name": "<技能名>", "importance": "<必须/建议/加分>", "target_level": <1-5>}
    ],
    "gap_analysis": [
        {
            "skill": "<技能名>",
            "current_level": <0-5>,
            "target_level": <1-5>,
            "gap_level": "<无差距/小差距/中差距/大差距/缺失>",
            "importance": "<必须/建议/加分>",
            "suggestion": "<补足建议>"
        }
    ],
    "path": [
        {
            "phase": <阶段序号>,
            "title": "<阶段标题>",
            "goal": "<阶段目标>",
            "topics": ["<学习主题1>", "<学习主题2>"],
            "resources": [
                {"type": "<book/course/project/video>", "name": "<资源名>", "url": "<可选链接>"}
            ],
            "duration_weeks": <周数>,
            "milestone": "<里程碑成果>"
        }
    ]
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
     * @return array<string, mixed>
     */
    private function parseAiResult(array $result): array
    {
        $content = $result['content'] ?? '';
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
