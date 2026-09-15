<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Enums\Interview as I;
use Illuminate\Support\Str;

final class InterviewReportService
{
    public const REPORT_VERSION = '2.0';

    public function persistInterviewReport(InterviewSession $interview): InterviewSession
    {
        $answeredQuestions = $interview->questions()->whereNotNull('answer')->get();
        $overallScore = (int) round((float) ($answeredQuestions->avg('score') ?? 0));
        $dimensionScores = $this->buildDimensionScores($interview, $answeredQuestions->all());
        $jdAlignment = $this->buildJdAlignmentMetrics($interview, $answeredQuestions->all());

        $strengths = collect($dimensionScores)
            ->filter(static fn (array $row): bool => (int) $row['score'] >= 7)
            ->sortByDesc('score')
            ->take((int) config('ui.limit.interview_recent', 3))
            ->map(static fn (array $row): string => (string) $row['dimension'])
            ->values()
            ->all();
        $improvements = collect($dimensionScores)
            ->filter(static fn (array $row): bool => (int) $row['score'] < 7)
            ->sortBy('score')
            ->take((int) config('ui.limit.interview_recent', 3))
            ->map(static fn (array $row): string => sprintf('%s（建议补充可量化案例）', (string) $row['dimension']))
            ->values()
            ->all();
        if ((bool) ($jdAlignment['enabled'] ?? false)) {
            $keywordHitRate = (int) ($jdAlignment['keyword_hit_rate'] ?? 0);
            if ($keywordHitRate >= 60) {
                $strengths[] = sprintf('JD关键词覆盖率 %d%%', $keywordHitRate);
            } else {
                $improvements[] = sprintf('JD关键词覆盖率偏低（当前 %d%%）', $keywordHitRate);
            }
            if ((int) ($jdAlignment['role_focus_score'] ?? 0) < 6) {
                $improvements[] = '回答与岗位核心关键词关联偏弱';
            }
            if ((int) ($jdAlignment['evidence_score'] ?? 0) < 6) {
                $improvements[] = '案例中的量化证据不足，可补充指标与结果';
            }
        }

        $strengths = array_values(array_unique(array_map(static fn (string $item): string => trim($item), $strengths)));
        $improvements = array_values(array_unique(array_map(static fn (string $item): string => trim($item), $improvements)));

        $report = [
            'report_version' => self::REPORT_VERSION,
            'overall_score' => $overallScore,
            'strengths' => array_slice($strengths, 0, 4),
            'improvements' => array_slice($improvements, 0, 4),
            'dimension_scores' => $dimensionScores,
            'jd_alignment' => $jdAlignment,
            'weak_dimensions' => collect($dimensionScores)
                ->filter(static fn (array $row): bool => (int) $row['score'] < 7)
                ->sortBy('score')
                ->take((int) config('ui.limit.interview_weakness', 2))
                ->map(static fn (array $row): string => (string) $row['dimension'])
                ->values()
                ->all(),
            'answer_guidance' => $this->buildAnswerGuidance($dimensionScores, $interview->type),
            'fresh_grad_growth_path' => $this->buildFreshGradGrowthPath($interview, $answeredQuestions->all()),
        ];

        $interview->update([
            'overall_score' => $overallScore,
            'report' => $report,
        ]);

        return $interview->fresh();
    }

    /**
     * @param  array<int, InterviewQuestion>  $answeredQuestions
     * @return array<int, array{dimension:string,score:int,count:int}>
     */
    public function buildDimensionScores(InterviewSession $interview, array $answeredQuestions): array
    {
        $rows = [];
        foreach ($answeredQuestions as $question) {
            $score = (int) ($question->score ?? 0);
            if ($score <= 0) {
                continue;
            }

            $dimension = trim((string) ($question->dimension ?? ''));
            if ($dimension === '') {
                $dimension = $this->resolveFocusDimension((string) $interview->type, (int) $question->round_no);
            }

            if (! isset($rows[$dimension])) {
                $rows[$dimension] = [
                    'dimension' => $dimension,
                    'total_score' => 0,
                    'count' => 0,
                ];
            }
            $rows[$dimension]['total_score'] += $score;
            $rows[$dimension]['count']++;
        }

        $result = [];
        foreach ($rows as $row) {
            $count = max(1, (int) $row['count']);
            $result[] = [
                'dimension' => (string) $row['dimension'],
                'score' => (int) round(((int) $row['total_score']) / $count),
                'count' => $count,
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $result;
    }

    /**
     * @param  array<int, InterviewQuestion>  $answeredQuestions
     * @return array{
     *     enabled:bool,
     *     jd_keyword_count:int,
     *     matched_count:int,
     *     keyword_hit_rate:int,
     *     match_score:int,
     *     requirements_coverage_score:int,
     *     role_focus_score:int,
     *     evidence_score:int,
     *     matched_keywords:array<int, string>,
     *     missing_keywords:array<int, string>
     * }
     */
    public function buildJdAlignmentMetrics(InterviewSession $interview, array $answeredQuestions): array
    {
        $jobDescription = trim((string) ($interview->job_description ?? ''));
        if ($jobDescription === '') {
            return [
                'enabled' => false,
                'jd_keyword_count' => 0,
                'matched_count' => 0,
                'keyword_hit_rate' => 0,
                'match_score' => 0,
                'requirements_coverage_score' => 0,
                'role_focus_score' => 0,
                'evidence_score' => 0,
                'matched_keywords' => [],
                'missing_keywords' => [],
            ];
        }

        $answersText = implode("\n", array_map(
            static fn (InterviewQuestion $question): string => trim((string) ($question->answer ?? '')),
            $answeredQuestions
        ));
        $answersLower = Str::lower($answersText);

        $jdKeywords = $this->extractJdKeywords($jobDescription);
        $matchedKeywords = [];
        $missingKeywords = [];
        foreach ($jdKeywords as $keyword) {
            if (Str::contains($answersLower, Str::lower($keyword))) {
                $matchedKeywords[] = $keyword;
            } else {
                $missingKeywords[] = $keyword;
            }
        }

        $jdKeywordCount = count($jdKeywords);
        $matchedCount = count($matchedKeywords);
        $keywordHitRate = $jdKeywordCount > 0 ? (int) round(($matchedCount / $jdKeywordCount) * 100) : 0;
        $requirementsCoverageScore = (int) max(0, min(10, round($keywordHitRate / 10)));

        $positionTokens = $this->extractJdKeywords((string) $interview->position);
        $companyTokens = $this->extractJdKeywords((string) ($interview->company ?? ''));
        $roleTokens = array_values(array_unique(array_merge($positionTokens, array_slice($companyTokens, 0, 3))));
        $roleTokenHitCount = 0;
        foreach ($roleTokens as $token) {
            if (Str::contains($answersLower, Str::lower($token))) {
                $roleTokenHitCount++;
            }
        }
        $roleFocusScore = count($roleTokens) > 0
            ? (int) max(0, min(10, round(($roleTokenHitCount / count($roleTokens)) * 10)))
            : 5;

        preg_match_all('/(\d+%|\d+\+|[1-9]\d{1,}|提升|优化|增长|降低|缩短|减少|节省)/u', $answersText, $evidenceMatches);
        $evidenceCount = count($evidenceMatches[0] ?? []);
        $evidenceScore = (int) max(0, min(10, 3 + $evidenceCount));

        $matchScore = (int) max(0, min(
            10,
            round(($requirementsCoverageScore * 0.6) + ($roleFocusScore * 0.25) + ($evidenceScore * 0.15))
        ));

        return [
            'enabled' => true,
            'jd_keyword_count' => $jdKeywordCount,
            'matched_count' => $matchedCount,
            'keyword_hit_rate' => $keywordHitRate,
            'match_score' => $matchScore,
            'requirements_coverage_score' => $requirementsCoverageScore,
            'role_focus_score' => $roleFocusScore,
            'evidence_score' => $evidenceScore,
            'matched_keywords' => array_slice($matchedKeywords, 0, 10),
            'missing_keywords' => array_slice($missingKeywords, 0, 10),
        ];
    }

    /**
     * Generate model answer guidance for the weakest dimensions
     */
    private function buildAnswerGuidance(array $dimensionScores, string $interviewType): array
    {
        $weak = collect($dimensionScores)
            ->filter(static fn (array $row): bool => (int) $row['score'] < 7)
            ->sortBy('score')
            ->take(3)
            ->values()
            ->all();

        if (empty($weak)) {
            return [
                ['title' => '整体表现优秀', 'framework' => '继续保持结构化表达', 'tips' => ['继续用量化数据支撑观点', '主动连接不同话题形成叙事线']],
            ];
        }

        $guidance = [];
        $frameworks = [
            '沟通表达' => ['框架' => '金字塔原理', '要点' => ['结论先行', '自上而下展开', '每个论点至少一个例子支撑']],
            '团队协作' => ['框架' => 'STAR + 协作维度', '要点' => ['描述具体协作场景', '明确你的角色 vs 团队角色', '量化协作成果']],
            '领导力' => ['框架' => 'STAR + 影响力', '要点' => ['描述你如何影响决策', '展示团队管理或培养案例', '体现冲突处理与权衡']],
            '复盘' => ['框架' => 'K.U.S.S（保持-改进-停止-开始）', '要点' => ['具体描述失败或待改进场景', '说明你学到了什么', '展示后续改进行动']],
            '技术' => ['框架' => 'STAR + 技术深度', '要点' => ['先讲业务背景再讲技术方案', '对比方案选择与权衡', '量化性能/效率提升']],
            '管理' => ['框架' => 'STAR + 管理视角', '要点' => ['从组织层面描述场景', '展示你如何分配资源和优先级', '量化管理带来的效率提升']],
            '压力' => ['框架' => 'CARL（情境-行动-结果-学习）', '要点' => ['诚实描述压力情境', '展示韧性而非抱怨', '说明从中学到的应对策略']],
            '问题' => ['框架' => 'IDEAL（识别-定义-探索-行动-回顾）', '要点' => ['结构化拆解问题步骤', '展示多种方案探索', '量化解决效果']],
            '设计' => ['框架' => '双钻模型', '要点' => ['从用户痛点出发', '展示方案探索与迭代', '用数据验证设计决策']],
            '学习' => ['框架' => '70-20-10法则', '要点' => ['展示持续学习习惯', '说明如何将新知识应用到工作', '量化学习带来的业务影响']],
        ];

        foreach ($weak as $row) {
            $dimension = (string) ($row['dimension'] ?? '');
            $bestMatch = null;
            foreach ($frameworks as $keyword => $fw) {
                if (mb_stripos($dimension, $keyword) !== false) {
                    $bestMatch = $fw;
                    break;
                }
            }
            if ($bestMatch === null) {
                $bestMatch = ['框架' => 'STAR 法则', '要点' => ['先描述情境和任务', '重点描述你的行动和贡献', '用数据量化结果']];
            }
            $guidance[] = [
                'title' => $dimension,
                'framework' => $bestMatch['框架'],
                'tips' => $bestMatch['要点'],
            ];
        }

        return $guidance;
    }

    /**
     * Build fresh graduate specific growth path and recommendations.
     */
    private function buildFreshGradGrowthPath(InterviewSession $interview, array $answeredQuestions): array
    {
        if (! in_array((string) $interview->candidate_profile, I::BEGINNER_PROFILES, true)) {
            return [];
        }

        $avgScore = count($answeredQuestions) > 0
            ? (int) round((float) (collect($answeredQuestions)->avg('score') ?? 0))
            : 0;

        $path = [
            'is_fresh_grad' => true,
            'avg_score' => $avgScore,
            'stages' => [],
            'career_paths' => $this->suggestCareerPaths($interview, $answeredQuestions),
            'resources' => $this->suggestLearningResources($interview),
            'elevator_pitch' => $this->buildElevatorPitchTemplate($interview),
            'coaching' => $this->buildCoachingMessages($answeredQuestions),
        ];

        // Map each question to the growth path stage
        $stages = (array) config('interview.fresh_graduate_growth_path', []);
        foreach ($answeredQuestions as $q) {
            $round = (int) $q->round_no;
            $stage = $stages[$round] ?? null;
            if ($stage) {
                $path['stages'][] = [
                    'round' => $round,
                    'stage' => $stage['stage'] ?? '',
                    'score' => (int) ($q->score ?? 0),
                    'advice' => $stage['advice'] ?? '',
                ];
            }
        }

        // Overall recommendations based on performance
        if ($avgScore >= 7) {
            $path['overall'] = '你的面试表现优秀，展现出了扎实的基础和良好的表达力。建议下一步：1) 准备一个3-5分钟的"电梯演讲"自我介绍；2) 深入研究目标公司的业务和技术栈；3) 准备2-3个有量化数据支撑的项目案例。';
        } elseif ($avgScore >= 5) {
            $path['overall'] = '你有良好的潜力，但部分回答缺少结构和量化支撑。建议下一步：1) 学习用STAR法则重构每一个经历描述；2) 给每个项目找至少一个量化结果（%或数字）；3) 提前准备"为什么选择这个行业/公司"的回答。';
        } else {
            $path['overall'] = '你有基础能力，但需要在表达结构和案例深度上多下功夫。建议下一步：1) 从一个小项目开始用STAR框架写文案，反复练习口头表达；2) 找一个在目标行业工作的学长学姐做mock interview；3) 把你简历上的每个经历都扩展为1分钟的STAR故事。';
        }

        return $path;
    }

    /**
     * Suggest career paths based on interview answers and tech keywords
     */
    private function suggestCareerPaths(InterviewSession $interview, array $answeredQuestions): array
    {
        $answersText = implode(' ', array_map(
            static fn (InterviewQuestion $q): string => trim((string) ($q->answer ?? '')),
            $answeredQuestions
        ));
        $answersLower = Str::lower($answersText);
        $keywords = trim((string) ($interview->tech_keywords ?? ''));
        $position = Str::lower(trim((string) ($interview->position ?? '')));

        // 一次性提取所有 token，避免循环中重复调用 Str::contains
        $allTokens = preg_split('/[\s,，、。；;]+/u', $answersLower . ' ' . $keywords . ' ' . $position) ?: [];
        $tokenSet = array_flip($allTokens);

        $careerPaths = (array) config('interview.fresh_grad_career_paths', []);
        $scored = [];
        foreach ($careerPaths as $key => $cp) {
            $score = 0;
            foreach ($cp['keywords'] ?? [] as $kw) {
                if (isset($tokenSet[Str::lower($kw)])) $score++;
            }
            if ($score > 0) {
                $scored[] = ['key' => $key, 'score' => $score, 'data' => $cp];
            }
        }
        usort($scored, static fn ($a, $b): int => $b['score'] <=> $a['score']);

        $result = [];
        foreach (array_slice($scored, 0, 3) as $s) {
            $cp = $s['data'];
            $resources = array_map(static fn (string $r): array => [
                'name' => explode('|', $r)[0] ?? $r,
                'type' => explode('|', $r)[1] ?? '学习',
            ], $cp['resources'] ?? []);
            $result[] = [
                'label' => $cp['label'] ?? $s['key'],
                'score' => $s['score'],
                'resources' => $resources,
            ];
        }

        // Always include general advice
        $general = $careerPaths['general'] ?? null;
        if ($general) {
            $result[] = [
                'label' => $general['label'] ?? '通用建议',
                'score' => 0,
                'resources' => array_map(static fn (string $r): array => [
                    'name' => explode('|', $r)[0] ?? $r,
                    'type' => explode('|', $r)[1] ?? '学习',
                ], $general['resources'] ?? []),
            ];
        }

        return $result;
    }

    private function suggestLearningResources(InterviewSession $interview): array
    {
        $resources = [];
        // Always include these for fresh grads
        $resources[] = ['name' => '用STAR框架重构简历上每一段经历', 'type' => '核心技能', 'icon' => 'star'];
        $resources[] = ['name' => '准备3分钟的自我介绍（电梯演讲）', 'type' => '面试技巧', 'icon' => 'mic'];
        $resources[] = ['name' => '在牛客网/LeetCode 完成50道题', 'type' => '刷题练习', 'icon' => 'code'];

        $type = (string) ($interview->type ?? 'mixed');
        if ($type === 'technical') {
            $resources[] = ['name' => '《程序员面试金典》重点章节', 'type' => '进阶阅读', 'icon' => 'book'];
        }
        if ($type === 'behavioral') {
            $resources[] = ['name' => '用CARL框架复盘一次冲突/困难经历', 'type' => '行为面试', 'icon' => 'message'];
        }

        return $resources;
    }

    private function buildElevatorPitchTemplate(InterviewSession $interview): array
    {
        $position = trim((string) ($interview->position ?? ''));
        $techKeywords = trim((string) ($interview->tech_keywords ?? ''));

        return [
            'hook' => '我叫[姓名]，是[学校][专业]应届毕业生，正在寻找'.($position !== '' ? $position.'相关' : '').'的机会。',
            'value_1' => '在校期间我通过[课程/项目]掌握了'.($techKeywords !== '' ? $techKeywords : '[核心技术]').'，并用它完成了[具体成果]。',
            'value_2' => '我在[实习/社团]中负责[角色和任务]，这让我学会了[关键能力]，并帮助团队达到了[量化结果]。',
            'close' => '我对'.($position !== '' ? $position.'领域' : '技术领域').'充满热情，希望加入一个有成长空间的团队，在实战中快速进步。',
            'tip' => '自我介绍控制在60-90秒，背熟但不死板，对着镜子练习3-5遍。',
        ];
    }

    private function buildCoachingMessages(array $answeredQuestions): array
    {
        $coaching = (array) config('interview.fresh_grad_coaching', []);
        $messages = [];
        if (! empty($coaching['intro'])) {
            $messages[] = $coaching['intro'];
        }

        $rounds = array_map(static fn (InterviewQuestion $q): int => (int) $q->round_no, $answeredQuestions);
        if ($rounds === []) {
            return $messages;
        }
        if (min($rounds) <= 1 && ! empty($coaching['after_r1'])) {
            $messages[] = $coaching['after_r1'];
        }
        if (max($rounds) >= 3 && ! empty($coaching['after_r3'])) {
            $messages[] = $coaching['after_r3'];
        }
        if (max($rounds) >= 5 && ! empty($coaching['final'])) {
            $messages[] = $coaching['final'];
        }

        return $messages;
    }

    /**
     * @return array<int, string>
     */
    public static function extractJdKeywords(string $text): array
    {
        $normalized = preg_replace('/[\r\n\t]+/u', ' ', trim($text));
        if ($normalized === null || $normalized === '') {
            return [];
        }

        $tokens = preg_split('/[\s,，。；;、:：\/\\\\|()（）\[\]{}\-]+/u', $normalized) ?: [];
        $stopwords = [
            '以及', '并且', '负责', '相关', '能够', '具有', '进行', '参与', '优先', '以上', '以下',
            '工作', '岗位', '职位', '经验', '能力', '熟悉', '了解', '良好', '优秀', '团队',
            'and', 'the', 'for', 'with', 'from', 'that', 'this', 'will', 'you', 'your',
            'have', 'has', 'our', 'about', 'into', 'required', 'plus',
        ];

        $result = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            $lower = Str::lower($token);
            if (in_array($lower, $stopwords, true)) {
                continue;
            }
            if (mb_strlen($token) < 2 || mb_strlen($token) > 24) {
                continue;
            }
            if (! preg_match('/[\p{L}\p{N}]/u', $token)) {
                continue;
            }
            $result[$lower] = $token;
            if (count($result) >= 20) {
                break;
            }
        }

        return array_values($result);
    }

    private function resolveFocusDimension(string $type, int $roundNo): string
    {
        $dimensions = [
            'behavioral' => ['领导力', '团队协作', '问题解决', '抗压能力', '沟通表达'],
            'technical' => ['技术深度', '系统设计', '代码质量', '工程实践', '学习能力'],
            'general' => ['综合素质', '逻辑思维', '沟通表达', '学习能力', '职业规划'],
        ];

        $dims = $dimensions[$type] ?? $dimensions['general'];

        return $dims[($roundNo - 1) % count($dims)];
    }
}
