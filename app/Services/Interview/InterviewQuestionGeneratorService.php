<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Enums\Interview as I;
use App\Infrastructure\AI\AiManager;
use App\Models\InterviewSession;
use Illuminate\Support\Str;

final class InterviewQuestionGeneratorService
{
    private ?InterviewResumeContextBuilder $resumeContextBuilder = null;
    private ?InterviewFallbackQuestionBuilder $fallbackBuilder = null;
    private ?array $cachedDimensionMatrix = null;
    private ?array $cachedProfileStrategy = null;

    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    private function resumeContextBuilder(): InterviewResumeContextBuilder
    {
        return $this->resumeContextBuilder ??= app(InterviewResumeContextBuilder::class);
    }

    private function fallbackBuilder(): InterviewFallbackQuestionBuilder
    {
        return $this->fallbackBuilder ??= app(InterviewFallbackQuestionBuilder::class);
    }

    private function dimensionMatrix(): array
    {
        return $this->cachedDimensionMatrix ??= (array) config('interview.dimension_matrix', []);
    }

    private function profileStrategy(string $key = 'fresh_graduate'): array
    {
        $all = $this->cachedProfileStrategy ??= (array) config('interview.candidate_profile_strategy', []);
        return $all[$key] ?? $all[I::PROFILE_FRESH_GRAD] ?? [];
    }

    /**
     * @return array{question:string, dimension:string, tags?:array<string>, difficulty_level?:string}
     */
    public function generateForSession(InterviewSession $session, int $round, ?string $lastAnswer = null): array
    {
        $session->loadMissing(['resume.modules', 'questions']);
        $candidateProfile = $this->normalizeCandidateProfile((string) ($session->candidate_profile ?? I::PROFILE_FRESH_GRAD));
        $jobDescription = trim((string) ($session->job_description ?? ''));
        $techKeywords = trim((string) ($session->tech_keywords ?? ''));
        $position = trim((string) ($session->position ?? ''));
        $difficulty = (string) ($session->difficulty ?? I::DIFFICULTY_MEDIUM);
        $totalQuestions = (int) ($session->question_count ?? 5);
        $mode = (string) ($session->mode ?? I::MODE_TEXT);
        $company = trim((string) ($session->company ?? ''));
        $resumeTargetJob = trim((string) ($session->resume?->target_job ?? ''));

        $hasJD = $jobDescription !== '';
        $hasTechKws = $techKeywords !== '';
        $hasPosition = $position !== '';
        $hasCompany = $company !== '';

        $positionAligned = $this->detectPositionResumeAlign($position, $resumeTargetJob);

        $strategy = $this->buildProfileStrategy($candidateProfile, $difficulty);
        $resumeAnchors = $this->resumeContextBuilder()->extractResumeAnchors($session->resume);
        $resumeExperienceSignal = $this->resumeContextBuilder()->buildResumeExperienceSignal($session->resume);
        $jdKeywords = $this->extractJobKeywords($jobDescription);
        $resumeExcerpt = $this->resumeContextBuilder()->buildResumeExcerpt($session->resume);
        $focusPoints = $this->resumeContextBuilder()->extractFocusPoints($session->resume);

        // 历史问题一次性提取（避免 N+1）
        $historyQuestions = $session->questions
            ->sortBy('round_no')
            ->pluck('question')
            ->filter(static fn ($q): bool => is_string($q) && trim($q) !== '')
            ->values()
            ->all();

        // 已使用维度（传入 historyQuestions 避免重复 DB 查询）
        $usedDimensions = $this->extractUsedDimensions($session, $historyQuestions);

        // 数据丰富度评分
        $dataRichness = ($hasJD ? 40 : 0) + ($hasTechKws ? 20 : 0) + ($hasPosition ? 20 : 0) + ($resumeExcerpt !== '' ? 20 : 0);
        $dataRichness += ($hasCompany ? 5 : 0) + ($positionAligned ? 5 : 0);

        $focusDimension = $this->resolveSmartFocusDimension(
            (string) $session->type, $round, $hasJD, $hasTechKws, $hasPosition, $usedDimensions
        );

        $adjustedDifficulty = $this->adjustDifficultyByAnswer(
            $difficulty,
            $this->estimateLastAnswerQuality($lastAnswer, $round)
        );

        $remainingQuestions = max(0, $totalQuestions - $round);
        $pacingStrategy = $this->resolvePacingStrategy($round, $remainingQuestions, $totalQuestions);

        $context = [
            'interview_type' => (string) $session->type,
            'candidate_profile' => $candidateProfile,
            'candidate_profile_label' => (string) ($strategy['label'] ?? '应届生'),
            'candidate_tone' => (string) ($strategy['tone'] ?? 'friendly'),
            'candidate_difficulty' => (string) ($strategy['difficulty'] ?? 'low_to_mid'),
            'candidate_focus' => (array) ($strategy['focus'] ?? []),
            'difficulty' => $difficulty,
            'tech_keywords' => $techKeywords,
            'language' => (string) ($session->language ?? I::LANG_ZH),
            'company' => $company,
            'resume_title' => (string) ($session->resume?->title ?? ''),
            'resume_target_job' => $resumeTargetJob,
            'resume_excerpt' => $resumeExcerpt,
            'focus_points' => $focusPoints,
            'resume_anchors' => $resumeAnchors,
            'resume_experience_signal' => $resumeExperienceSignal,
            'job_description_excerpt' => $this->normalizeJobDescription($jobDescription),
            'job_keywords' => $jdKeywords,
            'previous_questions' => $historyQuestions,
            'last_answer_summary' => $this->summarizeLastAnswer($lastAnswer),
            'round_goal' => $this->dataAwareRoundGoal($round, (string) $session->type, $candidateProfile, $hasJD, $hasTechKws, $hasPosition),
            'focus_dimension' => $focusDimension,
            'data_richness' => $dataRichness,
            'has_jd' => $hasJD,
            'has_tech_keywords' => $hasTechKws,
            'has_position' => $hasPosition,
            'has_company' => $hasCompany,
            'total_questions' => $totalQuestions,
            'mode' => $mode,
            'position_aligned' => $positionAligned,
            'adjusted_difficulty' => $adjustedDifficulty,
            'pacing_strategy' => $pacingStrategy,
            'used_dimensions' => $usedDimensions,
            'remaining_questions' => $remainingQuestions,
        ];

        $rawQuestion = null;
        $rawTags = null;
        $rawDifficultyLevel = null;
        try {
            $payload = $this->aiManager->providerWithFallback()->generateInterviewQuestion((string) $session->position, $round, $context);
            $rawQuestion = is_array($payload) ? ($payload['question'] ?? null) : null;
            $rawTags = is_array($payload) ? ($payload['tags'] ?? null) : null;
            $rawDifficultyLevel = is_array($payload) ? ($payload['difficulty_level'] ?? null) : null;
        } catch (\Throwable) {
            $rawQuestion = null;
        }

        $question = $this->fallbackBuilder()->sanitizeQuestion(is_string($rawQuestion) ? $rawQuestion : '');
        if ($this->fallbackBuilder()->shouldFallback($question, $session, $candidateProfile)) {
            $question = $this->fallbackBuilder()->build($session, $round, $historyQuestions, $focusDimension, $resumeAnchors, $jdKeywords);
        }

        // 解析标签：AI返回或基于维度推断
        $tags = is_array($rawTags) ? array_slice(array_values(array_filter($rawTags, 'is_string')), 0, 4) : [$focusDimension];

        // 解析难度：AI返回或基于会话难度推断
        $validDifficulties = ['easy', 'medium', 'hard'];
        $difficultyLevel = in_array($rawDifficultyLevel, $validDifficulties, true)
            ? $rawDifficultyLevel
            : $adjustedDifficulty;

        return ['question' => $question, 'dimension' => $focusDimension, 'tags' => $tags, 'difficulty_level' => $difficultyLevel];
    }

    private function normalizeJobDescription(string $jobDescription): string
    {
        if ($jobDescription === '') return '';
        return Str::limit((string) preg_replace('/\s+/u', ' ', $jobDescription), 800, '...');
    }

    private function extractJobKeywords(string $jobDescription): array
    {
        if ($jobDescription === '') return [];
        $lines = preg_split('/[\r\n;,，。；、]+/u', $jobDescription) ?: [];
        $keywords = [];
        foreach ($lines as $line) {
            $text = trim((string) $line, " \t\n\r\0\x0B-•*");
            if ($text === '' || mb_strlen($text) < 4) continue;
            $keywords[] = Str::limit($text, 30, '...');
            if (count($keywords) >= 8) break;
        }
        return array_values(array_unique($keywords));
    }

    private function summarizeLastAnswer(?string $lastAnswer): string
    {
        if (! is_string($lastAnswer) || trim($lastAnswer) === '') return '';
        return Str::limit((string) preg_replace('/\s+/u', ' ', trim($lastAnswer)), 180, '...');
    }

    private function detectPositionResumeAlign(string $position, string $resumeTargetJob): string
    {
        if ($resumeTargetJob === '') return 'no_target';
        $posLower = mb_strtolower($position);
        $resLower = mb_strtolower($resumeTargetJob);
        if ($posLower === $resLower) return 'aligned';
        if (str_replace(' ', '', $posLower) === str_replace(' ', '', $resLower)) return 'aligned';

        $posTokens = preg_split('/[\/\-\s,，、]+/u', $posLower) ?: [];
        $resTokens = preg_split('/[\/\-\s,，、]+/u', $resLower) ?: [];
        $overlap = count(array_intersect($posTokens, $resTokens));
        $minTokens = min(count($posTokens), count($resTokens));
        if ($minTokens > 0 && $overlap / $minTokens >= 0.5) return 'aligned';
        return 'misaligned';
    }

    private function dataAwareRoundGoal(int $round, string $type, string $candidateProfile, bool $hasJD, bool $hasTechKws, bool $hasPosition): string
    {
        $typeLabel = $this->typeLabel($type);
        $sourceHint = match (true) {
            $hasJD => '（优先基于JD职责与要求出题）',
            $hasTechKws && $hasPosition => '（基于职位和技术关键词出题）',
            $hasPosition => '（基于应聘职位出题）',
            default => '（基于简历经历和通用能力出题）',
        };

        if ($candidateProfile === I::PROFILE_FRESH_GRAD) {
            $path = (array) config('interview.fresh_graduate_growth_path', []);
            if (isset($path[$round]) && is_array($path[$round])) {
                return "第{$round}轮「{$path[$round]['stage']}」{$sourceHint}：{$path[$round]['goal']}";
            }
        }

        $beginner = in_array($candidateProfile, I::BEGINNER_PROFILES, true);

        if ($beginner) {
            return match ($round) {
                1 => "温和开场{$sourceHint}，评估{$typeLabel}基础、学习动机与表达能力",
                2 => "围绕简历中的课程/实习经历追问{$sourceHint}，还原任务背景与个人贡献",
                3 => '聚焦问题拆解与复盘习惯，引导候选人用 STAR 框架描述一次解决难题的经历',
                4 => '评估团队协作意识与沟通能力，引入轻度工作场景模拟',
                5 => "递进追问{$sourceHint}，考察{$typeLabel}成长潜力与岗位匹配度",
                default => "综合收尾追问：请候选人回顾整场面试，总结自己最突出的2个优势和1个待提升领域，并阐述未来3年职业愿景。",
            };
        }

        return match ($round) {
            1 => "开场破题{$sourceHint}，聚焦{$typeLabel}的真实工作案例与核心贡献",
            2 => '深挖具体项目中的决策过程与推动能力，要求候选人给出量化结果',
            3 => '引入复杂工作场景（需求冲突/资源受限/多方博弈），评估应变与优先级',
            4 => '考察团队协作与影响力：如何推动跨角色/跨部门方案落地',
            5 => "综合复盘{$sourceHint}：方法论沉淀、业务视野、ROI意识与上升空间",
            default => "收尾追问{$sourceHint}：请候选人回顾本轮面试中印象最深的讨论，连接不同话题形成完整的成长叙事，并描述如果加入团队，30-60-90天的融入计划。",
        };
    }

    public function resolveSmartFocusDimension(string $type, int $round, bool $hasJD, bool $hasTechKws, bool $hasPosition, array $usedDimensions = []): string
    {
        $matrix = $this->dimensionMatrix();
        $types = I::supportedTypes();
        $typeKey = in_array($type, $types, true) ? $type : 'mixed';
        $allDimensions = $matrix[$typeKey] ?? [];
        $allDimensions = array_values(array_filter($allDimensions, static fn ($item): bool => is_string($item) && trim($item) !== ''));

        if ($allDimensions === []) return '岗位综合能力';

        $candidates = $allDimensions;
        if (count($usedDimensions) < count($allDimensions) - 1) {
            $candidates = array_filter($allDimensions, static fn ($dim): bool => ! in_array($dim, $usedDimensions, true));
            if ($candidates === []) $candidates = $allDimensions;
            $candidates = array_values($candidates);
        }

        $preferKeywords = match (true) {
            $round === 1 => ['基础', '学习', '入门', '核心能力', '岗位基础'],
            $round <= 3 => $hasJD ? ['业务', '项目', '技术', '客户', '运营', '管理', '设计', '财务', '增长', '教学'] : ['项目', '实践', '协作', '沟通', '拆解', '分析'],
            default => ['复盘', '优化', '规划', '前瞻', '领导', '战略', '推动', '沉淀', '决策', '风险'],
        };

        $scored = array_map(static function (string $dim) use ($preferKeywords): array {
            $score = 0;
            foreach ($preferKeywords as $kw) { if (mb_strpos($dim, $kw) !== false) $score++; }
            return ['dimension' => $dim, 'score' => $score];
        }, $candidates);
        usort($scored, static fn ($a, $b): int => $b['score'] <=> $a['score']);

        $top5 = array_column(array_slice($scored, 0, 5), 'dimension');
        $index = ($round - 1) % max(1, count($top5));
        return (string) ($top5[$index] ?? $allDimensions[$index % count($allDimensions)]);
    }

    private function extractUsedDimensions(InterviewSession $session, array $historyQuestions): array
    {
        $matrix = $this->dimensionMatrix();
        $types = I::supportedTypes();
        $typeKey = in_array((string) $session->type, $types, true) ? (string) $session->type : 'mixed';
        $allDimensions = $matrix[$typeKey] ?? [];

        // 优先从 DB 的 dimension 字段读取（如果有值）
        $fromDb = $session->questions()
            ->whereNotNull('dimension')
            ->where('dimension', '!=', '')
            ->pluck('dimension')
            ->unique()
            ->values()
            ->all();

        $matched = $fromDb;

        // 补充：对没有 dimension 的旧数据，用文本反推
        $needsInference = array_diff_key($historyQuestions, array_flip($matched));
        foreach ($needsInference as $question) {
            foreach ($allDimensions as $dim) {
                $fragment = mb_substr((string) $dim, 0, max(4, (int) (mb_strlen((string) $dim) * 0.4)));
                if (mb_stripos((string) $question, $fragment) !== false) {
                    $matched[] = $dim;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    private function estimateLastAnswerQuality(?string $lastAnswer, int $round): int
    {
        if ($round <= 1 || ! is_string($lastAnswer) || trim($lastAnswer) === '') return 50;

        $answer = trim($lastAnswer);
        $len = mb_strlen($answer);

        $lengthScore = match (true) {
            $len < 20 => 10, $len < 50 => 20, $len < 100 => 30, $len < 200 => 35, default => 40,
        };

        $structureScore = 0;
        if (preg_match('/背景|当时|之前|项目|场景|需求|任务|目标|要求/', $answer)) $structureScore += 10;
        if (preg_match('/我做|负责|主导|推动|实现|完成|采用了|设计了/', $answer)) $structureScore += 10;
        if (preg_match('/结果|效果|数据|提升|降低|优化了|达到了|完成了/', $answer)) $structureScore += 10;

        $richnessScore = 0;
        if (mb_strpos($answer, '，') !== false || mb_strpos($answer, '。') !== false) $richnessScore += 10;
        if (preg_match('/\d+%|\d+个|[0-9]+%/', $answer)) $richnessScore += 10;
        $uniqueWords = count(array_unique(preg_split('/[，。、；\s]+/u', $answer, -1, PREG_SPLIT_NO_EMPTY) ?: []));
        if ($uniqueWords > 10) $richnessScore += 10;

        return min(100, max(0, $lengthScore + $structureScore + $richnessScore));
    }

    private function adjustDifficultyByAnswer(string $baseDifficulty, int $quality): string
    {
        // 平滑过渡：使用 1-10 分制替代离散三档
        $level = match ($baseDifficulty) {
            I::DIFFICULTY_EASY => 3,
            I::DIFFICULTY_MEDIUM => 5,
            I::DIFFICULTY_HARD => 8,
            default => 5,
        };

        // 根据回答质量平滑调整 ±2
        if ($quality <= 30) $level = max(1, $level - 2);
        elseif ($quality >= 70) $level = min(10, $level + 2);

        if ($level <= 3) return I::DIFFICULTY_EASY;
        if ($level >= 7) return I::DIFFICULTY_HARD;
        return I::DIFFICULTY_MEDIUM;
    }

    private function resolvePacingStrategy(int $round, int $remaining, int $total): string
    {
        if ($remaining >= 5) return '稳步推进：每轮聚焦 1-2 个层面，充分展开';
        if ($remaining >= 2) return '中速推进：开始串联前序回答，做适度的综合提问';
        if ($remaining >= 1) return '加速追问：最后阶段，要求候选人快速归纳复盘';
        return '收尾总结：最后一题，聚焦整体反思与职业规划';
    }

    private function isBeginnerProfile(string $candidateProfile): bool
    {
        return in_array($candidateProfile, I::BEGINNER_PROFILES, true);
    }

    private function normalizeCandidateProfile(string $candidateProfile): string
    {
        $valid = [I::PROFILE_FRESH_GRAD, I::PROFILE_NO_EXPERIENCE, I::PROFILE_JUNIOR, I::PROFILE_EXPERIENCED];
        return in_array(trim($candidateProfile), $valid, true) ? trim($candidateProfile) : I::PROFILE_FRESH_GRAD;
    }

    private function buildProfileStrategy(string $candidateProfile, string $difficulty = I::DIFFICULTY_MEDIUM): array
    {
        $value = $this->profileStrategy($candidateProfile);
        $focus = is_array($value['focus'] ?? null)
            ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value['focus']), static fn (string $item): bool => $item !== ''))
            : [];

        $tone = (string) ($value['tone'] ?? 'balanced');
        $diff = (string) ($value['difficulty'] ?? 'mid');

        if ($difficulty === I::DIFFICULTY_HARD) { $diff = 'high'; $tone = 'strict'; }
        elseif ($difficulty === I::DIFFICULTY_EASY) { $diff = 'low'; $tone = 'friendly'; }

        return ['label' => (string) ($value['label'] ?? '应届生'), 'tone' => $tone, 'difficulty' => $diff, 'focus' => $focus];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'technical' => '技术能力与工作实践',
            'behavioral' => '行为协作与岗位匹配',
            'sales' => '销售策略与客户推进',
            'management' => '管理能力与团队领导',
            'creative' => '创意产出与设计思维',
            'finance' => '财务分析与业务决策',
            'retail' => '零售运营与增长策略',
            'manufacturing' => '生产管理与工业实践',
            'service' => '服务流程与客户体验',
            'media' => '内容传播与创意策划',
            'education' => '教学设计与学生发展',
            'deep' => '价值观与深度思考',
            default => '综合能力与专业素养',
        };
    }
}
