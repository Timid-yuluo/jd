<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\AI\AiManager;
use App\Models\CareerAssessment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Throwable;

/**
 * 职业测评服务
 *
 * 职责：
 * 1. 提供题库配置
 * 2. 计算测评结果（MBTI / Holland / DISC）
 * 3. 调用 AI 生成个性化解读
 *
 * 关联文档：docs/features-development-plan.md §3.2
 */
final class CareerAssessmentService
{
    /** 支持的测评类型 */
    public const TYPE_MBTI = 'mbti';
    public const TYPE_HOLLAND = 'holland';
    public const TYPE_DISC = 'disc';

    /** @var array<int, string> */
    private const VALID_TYPES = [self::TYPE_MBTI, self::TYPE_HOLLAND, self::TYPE_DISC];

    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 获取支持的测评类型列表
     *
     * @return array<int, array{type: string, title: string, description: string, estimated_minutes: int}>
     */
    public function getAvailableTypes(): array
    {
        $types = [];
        foreach (self::VALID_TYPES as $type) {
            $config = $this->getConfig($type);
            $types[] = [
                'type' => $type,
                'title' => $config['title'],
                'description' => $config['description'],
                'estimated_minutes' => $config['estimated_minutes'],
            ];
        }

        return $types;
    }

    /**
     * 校验测评类型是否合法
     */
    public function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }

    /**
     * 获取题库配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(string $type): array
    {
        return config("assessments.{$type}", []);
    }

    /**
     * 获取题目列表（不含答案解析）
     *
     * @return array<int, array<string, mixed>>
     */
    public function getQuestions(string $type): array
    {
        $config = $this->getConfig($type);

        return $config['questions'] ?? [];
    }

    /**
     * 计算测评结果
     *
     * @param  string  $type  测评类型
     * @param  array<int, array{question_id: int, option: string|int}>  $answers  用户答案
     * @return array{result_code: string, result_label: string, dimensions: array<string, mixed>}
     */
    public function calculateResult(string $type, array $answers): array
    {
        return match ($type) {
            self::TYPE_MBTI => $this->calculateMbti($answers),
            self::TYPE_HOLLAND => $this->calculateHolland($answers),
            self::TYPE_DISC => $this->calculateDisc($answers),
            default => ['result_code' => '', 'result_label' => '', 'dimensions' => []],
        };
    }

    /**
     * 调用 AI 生成个性化解读
     *
     * @param  CareerAssessment  $assessment  测评记录
     * @return array<string, mixed> AI 解读结果
     */
    public function generateAiAnalysis(CareerAssessment $assessment): array
    {
        $messages = $this->buildAiMessages($assessment);

        try {
            $result = $this->aiManager->provider()->chat($messages, [
                'temperature' => 0.7,
                'response_format' => 'json',
            ]);

            return $this->parseAiResult($result);
        } catch (Throwable $e) {
            Log::error('职业测评 AI 解读失败', [
                'assessment_id' => $assessment->id,
                'test_type' => $assessment->test_type,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 完整流程：提交答案 → 计算结果 → AI 解读 → 存储
     *
     * @param  User  $user  用户
     * @param  string  $type  测评类型
     * @param  array<int, array{question_id: int, option: string|int}>  $answers  答案
     * @return CareerAssessment 测评记录
     */
    public function submitAssessment(User $user, string $type, array $answers): CareerAssessment
    {
        // 1. 计算结果
        $result = $this->calculateResult($type, $answers);

        // 2. 创建测评记录
        $assessment = CareerAssessment::create([
            'user_id' => $user->id,
            'test_type' => $type,
            'answers' => $answers,
            'result_code' => $result['result_code'],
            'result_label' => $result['result_label'],
            'dimensions' => $result['dimensions'],
        ]);

        // 3. 调用 AI 解读
        $aiAnalysis = $this->generateAiAnalysis($assessment);

        if ($aiAnalysis !== []) {
            $assessment->update([
                'ai_analysis' => $aiAnalysis['analysis'] ?? null,
                'recommended_careers' => $aiAnalysis['recommended_careers'] ?? null,
                'team_roles' => $aiAnalysis['team_roles'] ?? null,
            ]);
            $assessment->refresh();
        }

        return $assessment;
    }

    /**
     * 计算 MBTI 结果
     *
     * @param  array<int, array{question_id: int, option: string|int}>  $answers
     * @return array{result_code: string, result_label: string, dimensions: array<string, array<string, mixed>>}
     */
    private function calculateMbti(array $answers): array
    {
        $scores = ['E' => 0, 'I' => 0, 'S' => 0, 'N' => 0, 'T' => 0, 'F' => 0, 'J' => 0, 'P' => 0];

        foreach ($answers as $answer) {
            $value = (string) ($answer['option'] ?? '');
            if (isset($scores[$value])) {
                $scores[$value]++;
            }
        }

        $code = '';
        $code .= ($scores['E'] >= $scores['I']) ? 'E' : 'I';
        $code .= ($scores['S'] >= $scores['N']) ? 'S' : 'N';
        $code .= ($scores['T'] >= $scores['F']) ? 'T' : 'F';
        $code .= ($scores['J'] >= $scores['P']) ? 'J' : 'P';

        $config = $this->getConfig(self::TYPE_MBTI);
        $typeInfo = $config['type_codes'][$code] ?? ['label' => '未知类型', 'desc' => ''];

        return [
            'result_code' => $code,
            'result_label' => $typeInfo['label'],
            'dimensions' => [
                'EI' => [
                    'name' => '能量方向',
                    'scores' => ['E' => $scores['E'], 'I' => $scores['I']],
                    'dominant' => ($scores['E'] >= $scores['I']) ? 'E' : 'I',
                ],
                'SN' => [
                    'name' => '信息接收',
                    'scores' => ['S' => $scores['S'], 'N' => $scores['N']],
                    'dominant' => ($scores['S'] >= $scores['N']) ? 'S' : 'N',
                ],
                'TF' => [
                    'name' => '决策方式',
                    'scores' => ['T' => $scores['T'], 'F' => $scores['F']],
                    'dominant' => ($scores['T'] >= $scores['F']) ? 'T' : 'F',
                ],
                'JP' => [
                    'name' => '生活态度',
                    'scores' => ['J' => $scores['J'], 'P' => $scores['P']],
                    'dominant' => ($scores['J'] >= $scores['P']) ? 'J' : 'P',
                ],
                'description' => $typeInfo['desc'],
            ],
        ];
    }

    /**
     * 计算霍兰德结果
     *
     * @param  array<int, array{question_id: int, option: string|int}>  $answers
     * @return array{result_code: string, result_label: string, dimensions: array<string, mixed>}
     */
    private function calculateHolland(array $answers): array
    {
        $config = $this->getConfig(self::TYPE_HOLLAND);
        $options = $config['options'] ?? [];
        $scores = ['R' => 0, 'I' => 0, 'A' => 0, 'S' => 0, 'E' => 0, 'C' => 0];

        // 题目 → 维度映射
        $questionDim = [];
        foreach ($config['questions'] ?? [] as $q) {
            $questionDim[$q['id']] = $q['dimension'];
        }

        foreach ($answers as $answer) {
            $qid = (int) ($answer['question_id'] ?? 0);
            $dim = $questionDim[$qid] ?? null;
            if ($dim === null) {
                continue;
            }
            // option 是选项 ID（1-3），转换为分数
            $optId = (int) ($answer['option'] ?? 0);
            $score = $options[$optId]['score'] ?? 0;
            $scores[$dim] += $score;
        }

        // 排序取前 3
        arsort($scores);
        $topThree = array_slice(array_keys($scores), 0, 3);
        $code = implode('', $topThree);

        $careers = $config['careers'][$code] ?? ['暂无推荐'];

        return [
            'result_code' => $code,
            'result_label' => '霍兰德代码 '.$code,
            'dimensions' => [
                'scores' => $scores,
                'top_three' => $topThree,
                'recommended_careers' => $careers,
            ],
        ];
    }

    /**
     * 计算 DISC 结果
     *
     * @param  array<int, array{question_id: int, option: string|int}>  $answers
     * @return array{result_code: string, result_label: string, dimensions: array<string, mixed>}
     */
    private function calculateDisc(array $answers): array
    {
        $scores = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];

        foreach ($answers as $answer) {
            $value = (string) ($answer['option'] ?? '');
            if (isset($scores[$value])) {
                $scores[$value]++;
            }
        }

        arsort($scores);
        $primary = array_key_first($scores);
        $secondary = array_slice(array_keys($scores), 1, 1)[0] ?? '';
        $code = $secondary !== '' ? $primary.$secondary : $primary;

        $config = $this->getConfig(self::TYPE_DISC);
        $typeInfo = $config['type_codes'][$code] ?? ($config['type_codes'][$primary] ?? ['label' => '混合型', 'desc' => '']);

        return [
            'result_code' => $code,
            'result_label' => $typeInfo['label'],
            'dimensions' => [
                'scores' => $scores,
                'primary' => $primary,
                'secondary' => $secondary,
                'description' => $typeInfo['desc'],
            ],
        ];
    }

    /**
     * 构建 AI 解读消息
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function buildAiMessages(CareerAssessment $assessment): array
    {
        $typeLabels = [
            self::TYPE_MBTI => 'MBTI',
            self::TYPE_HOLLAND => '霍兰德 RIASEC',
            self::TYPE_DISC => 'DISC',
        ];

        $typeLabel = $typeLabels[$assessment->test_type] ?? $assessment->test_type;
        $resultJson = json_encode([
            'type' => $assessment->test_type,
            'result_code' => $assessment->result_code,
            'result_label' => $assessment->result_label,
            'dimensions' => $assessment->dimensions,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $systemPrompt = <<<'PROMPT'
你是资深职业规划师和心理咨询师，精通 MBTI、霍兰德、DISC 等测评工具。
请基于用户的测评结果，给出个性化、专业、可执行的解读。
返回严格 JSON 格式。
PROMPT;

        $userPrompt = <<<PROMPT
## 测评类型
{$typeLabel}

## 测评结果
{$resultJson}

请返回 JSON：
{
    "analysis": {
        "personality_description": "<人格/风格特征描述>",
        "strengths": ["<优势1>", "<优势2>", "<优势3>"],
        "weaknesses": ["<劣势1>", "<劣势2>"],
        "development_suggestions": ["<发展建议1>", "<发展建议2>"],
        "career_path": "<职业发展路径建议>"
    },
    "recommended_careers": [
        {
            "title": "<岗位名称>",
            "match_score": <0-100>,
            "reason": "<推荐理由>"
        }
    ],
    "team_roles": [
        {
            "role": "<团队角色>",
            "description": "<角色描述>"
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
