<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use Illuminate\Database\Eloquent\Collection;

final class InterviewFallbackQuestionBuilder
{
    public function __construct(
        private readonly InterviewResumeContextBuilder $resumeContextBuilder,
    ) {}

    /**
     * @param  array<int, string>  $historyQuestions
     * @param  array<int, string>  $resumeAnchors
     * @param  array<int, string>  $jdKeywords
     */
    public function build(
        InterviewSession $session,
        int $round,
        array $historyQuestions,
        string $focusDimension,
        array $resumeAnchors,
        array $jdKeywords
    ): string {
        $type = (string) $session->type;
        $position = (string) $session->position;
        $company = trim((string) $session->company);
        $candidateProfile = $this->normalizeCandidateProfile((string) ($session->candidate_profile ?? 'fresh_graduate'));
        $resumeSignal = $this->resumeContextBuilder->buildResumeExperienceSignal($session->resume);
        $anchor = $resumeAnchors[0] ?? null;
        $anchorPrefix = is_string($anchor) && $anchor !== '' ? "请围绕你简历中的「{$anchor}」展开，" : '';
        $jdKeyword = is_string($jdKeywords[0] ?? null) ? (string) $jdKeywords[0] : '';
        $jdPrefix = $jdKeyword !== '' ? "并结合 JD 要求「{$jdKeyword}」，" : '';
        $beginnerMode = $this->isBeginnerProfile($candidateProfile);
        $beginnerCaseScope = ($resumeSignal['has_project'] ?? false)
            ? '课程或项目任务'
            : '课程作业、校园实践或个人练习';

        if ($beginnerMode) {
            $bank = match ($type) {
                'technical' => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请分享一个你最熟悉的{$beginnerCaseScope}，按".'"背景-行动-结果"讲清你具体做了什么。',
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}如果你负责的功能出现 bug，你通常会按什么步骤定位问题并验证修复？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请举一个你从不会到会掌握新技术的例子，重点说学习路径和最终产出。",
                ],
                'behavioral' => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请分享一次你在团队作业、社团或项目协作中推进任务落地的经历，你的具体贡献是什么？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}当你遇到不熟悉的问题时，你会如何求助、拆解并推进？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请讲一次你根据反馈改进表达或产出质量的经历，最终变化是什么？",
                ],
                default => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请结合 {$position} 岗位，讲一个最能代表你能力的小项目或实践案例。",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}如果你入职后前 30 天需要快速上手，你会如何安排学习和交付节奏？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请讲一次你在压力或时间紧张情况下完成任务的经历，你如何保证质量？",
                ],
            };
        } else {
            $bank = match ($type) {
                'technical' => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}讲一个你主导排查线上复杂故障的案例，重点说明定位路径与最终改进。",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}如果系统在高并发下响应明显变慢，你会如何分层定位瓶颈并给出优化方案？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请选一个你最熟悉的项目模块，说明核心设计取舍、潜在风险和重构计划。",
                ],
                'behavioral' => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}讲一个你推动团队协作达成关键目标的案例，你承担了什么角色，遇到哪些阻力，结果如何？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}当你与同事在方案上出现明显分歧时，你通常如何达成一致？请给出真实案例。",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}分享一次你收到负面反馈后完成改进的经历，以及你如何验证改进有效性。",
                ],
                default => [
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请结合 {$position} 岗位，分享一个你最能体现业务价值的项目案例，重点讲目标、行动、结果。",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}如果让你在 90 天内在 {$position} 岗位做出成果，你会如何制定优先级与推进节奏？",
                    "围绕「{$focusDimension}」，{$anchorPrefix}{$jdPrefix}请讲一次你在压力较大的环境下保证交付质量的经历，以及复盘中你学到了什么。",
                ],
            };
        }

        if ($company !== '') {
            if ($beginnerMode) {
                $bank[] = "围绕「{$focusDimension}」，如果你入职 {$company} 担任 {$position}，前 30 天你会优先补齐哪些知识并交付哪些可见成果？";
            } else {
                $bank[] = "围绕「{$focusDimension}」，如果你入职 {$company} 担任 {$position}，你认为前 30 天最关键的工作目标是什么？你会怎么推进？";
            }
        }

        foreach ($bank as $question) {
            if (! in_array($question, $historyQuestions, true)) {
                return $question;
            }
        }

        if ($beginnerMode) {
            return "第 {$round} 轮追问（关注「{$focusDimension}」）：请结合一个真实的小项目或课程实践，说明你做了什么、结果如何、下次会怎么改进。";
        }

        return "第 {$round} 轮追问（关注「{$focusDimension}」）：请结合一个真实案例，说明你在 {$position} 相关工作中的关键决策、结果与复盘。";
    }

    public function shouldFallback(string $question, InterviewSession $session, string $candidateProfile): bool
    {
        if ($question === '') {
            return true;
        }
        if (mb_strlen($question) < 16) {
            return true;
        }
        if ($this->isDuplicateQuestion($question, $session->questions)) {
            return true;
        }
        if ($this->isBeginnerProfile($candidateProfile) && $this->containsAdvancedScenario($question)) {
            return true;
        }

        return false;
    }

    public function sanitizeQuestion(string $question): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $question) ?? '');
        if ($normalized === '') {
            return '';
        }

        return $normalized;
    }

    /**
     * @param  Collection<int, InterviewQuestion>  $existingQuestions
     */
    private function isDuplicateQuestion(string $candidate, $existingQuestions): bool
    {
        $normalizedCandidate = $this->normalizeForCompare($candidate);
        if ($normalizedCandidate === '') {
            return true;
        }

        foreach ($existingQuestions as $question) {
            if (! is_string($question->question)) {
                continue;
            }
            if ($this->normalizeForCompare($question->question) === $normalizedCandidate) {
                return true;
            }
        }

        return false;
    }

    private function normalizeForCompare(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/[\p{P}\p{S}\s]+/u', '', (string) $normalized);

        return (string) $normalized;
    }

    private function containsAdvancedScenario(string $question): bool
    {
        $dangerKeywords = [
            '线上复杂故障',
            '高并发',
            '容灾',
            'SLA',
            '灰度发布',
            '分布式事务',
            '百万级',
        ];
        foreach ($dangerKeywords as $keyword) {
            if (mb_stripos($question, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isBeginnerProfile(string $candidateProfile): bool
    {
        return in_array($candidateProfile, ['fresh_graduate', 'no_experience'], true);
    }

    private function normalizeCandidateProfile(string $candidateProfile): string
    {
        $value = trim($candidateProfile);
        if (in_array($value, ['fresh_graduate', 'no_experience', 'junior', 'experienced'], true)) {
            return $value;
        }

        return 'fresh_graduate';
    }
}
