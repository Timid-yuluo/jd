<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Support\Interview\AnswerAnalyzer;

final class InterviewSessionService
{
    public function __construct(
        private readonly InterviewAnswerSubmissionService $submissionService,
    ) {}

    public function maxQuestions(): int
    {
        return $this->submissionService->maxQuestions();
    }

    public function heartbeatCacheKey(int $interviewId): string
    {
        return "interview:heartbeat:{$interviewId}";
    }

    public function buildAnswerReply(InterviewQuestion $question): string
    {
        $answer = trim((string) ($question->answer ?? ''));
        if ($answer === '') {
            return '已收到你的回答，我们继续下一题。';
        }

        if (AnswerAnalyzer::isNonAnswer($answer)) {
            return '如果暂时想不起来，建议先给一个相近案例，按"背景-行动-结果-复盘"简要说明。';
        }

        $meta = AnswerAnalyzer::buildQualityMeta($answer);

        if ($meta['has_metric'] && $meta['has_action_verb'] && $meta['length'] >= 80) {
            return '这个回答很不错，已经体现了行动路径和结果指标，表达较有说服力。';
        }
        if ($meta['has_fluency_risk']) {
            return '你有思路，但表达略跳跃。建议分点短句作答，减少重复词，让重点更清晰。';
        }
        if ($meta['has_action_verb'] && ! $meta['has_metric']) {
            return '你的思路清晰，建议补充可量化结果（如转化率、成本、周期变化），说服力会更强。';
        }
        if (! $meta['has_action_verb'] && $meta['has_metric']) {
            return '你给出了结果数据，建议再补充关键动作与决策过程，形成完整闭环。';
        }

        return '已收到你的回答，建议按"背景-行动-结果-复盘"结构再补充细节。';
    }

    /**
     * @param  array{level:string,reasons:array<int,string>,tips:array<int,string>}  $quality
     */
    public function buildCounterQuestion(array $quality): ?string
    {
        if (($quality['level'] ?? 'good') === 'good') {
            return null;
        }

        $reasons = $quality['reasons'] ?? [];
        if (in_array('信息量偏低', $reasons, true)) {
            return '如果这题一时想不起来，请给一个最接近的真实场景：当时你的目标是什么，你具体做了哪3步？';
        }
        if (in_array('回答过短', $reasons, true)) {
            return '能否把这段经历展开成一句背景、两条关键动作、一个结果数据，再加一句复盘？';
        }
        if (in_array('缺少行动过程', $reasons, true)) {
            return '你在这个问题里具体做了哪些动作？请按时间顺序说出关键步骤。';
        }
        if (in_array('缺少结果指标', $reasons, true)) {
            return '这件事最终结果如何？能给出1-2个可量化指标吗（如效率、成本、成功率）？';
        }
        if (in_array('语言不够通顺', $reasons, true)) {
            return '能否用4句短句重述：背景一句、行动两句、结果一句？这样会更清晰。';
        }

        return '可以再补充一个更具体的案例吗？建议按"背景-行动-结果-复盘"来回答。';
    }

    /**
     * @param  array{level:string,reasons:array<int,string>,tips:array<int,string>}  $quality
     * @param  array<string,mixed>|null  $aiDialogueDecision
     * @return array<int, string>
     */
    public function resolveCounterQuestions(array $quality, ?array $aiDialogueDecision): array
    {
        $maxFollowUps = max(1, min(3, (int) config('interview.ai_dialogue_max_followups_per_answer', 2)));
        $aiAction = is_array($aiDialogueDecision) ? (string) ($aiDialogueDecision['action'] ?? '') : '';
        $aiFollowUp = is_array($aiDialogueDecision) ? trim((string) ($aiDialogueDecision['follow_up'] ?? '')) : '';
        $aiFollowUps = is_array($aiDialogueDecision['follow_ups'] ?? null)
            ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $aiDialogueDecision['follow_ups'])))
            : [];

        if ($aiAction === 'deep_probe') {
            $resolved = [];
            if ($aiFollowUp !== '') {
                $resolved[] = $aiFollowUp;
            }
            foreach ($aiFollowUps as $item) {
                if (count($resolved) >= $maxFollowUps) {
                    break;
                }
                $resolved[] = $item;
            }
            if ($resolved !== []) {
                return array_values(array_unique($resolved));
            }
        }

        if ($aiAction === 'probe' && $aiFollowUp !== '') {
            return [$aiFollowUp];
        }

        $fallback = $this->buildCounterQuestion($quality);

        return $fallback !== null ? [$fallback] : [];
    }

    /**
     * @param  array<string,mixed>|null  $aiDialogueDecision
     */
    public function resolveAnswerReply(InterviewQuestion $question, ?array $aiDialogueDecision): string
    {
        $coachReply = is_array($aiDialogueDecision) ? trim((string) ($aiDialogueDecision['coach_reply'] ?? '')) : '';
        if ($coachReply !== '') {
            return $coachReply;
        }

        return $this->buildAnswerReply($question);
    }

    /**
     * @param  array<string,mixed>|null  $aiDialogueDecision
     * @param  array<int,string>  $counterQuestions
     * @return array{action:string,confidence:float,followup_count:int}
     */
    public function resolveDialogueMeta(?array $aiDialogueDecision, array $counterQuestions): array
    {
        $action = is_array($aiDialogueDecision) ? (string) ($aiDialogueDecision['action'] ?? 'continue') : 'continue';
        $confidence = is_array($aiDialogueDecision)
            ? max(0.0, min(1.0, (float) ($aiDialogueDecision['confidence'] ?? 0.0)))
            : 0.0;

        if ($action === 'continue' && $counterQuestions !== []) {
            $action = count($counterQuestions) > 1 ? 'deep_probe' : 'probe';
        }

        return [
            'action' => $action,
            'confidence' => $confidence,
            'followup_count' => count($counterQuestions),
        ];
    }

    public function countConsecutiveLowQualityAnswers(int $interviewId): int
    {
        return $this->submissionService->countConsecutiveLowQualityAnswers($interviewId);
    }

    /**
     * 提前终止面试并生成报告
     *
     * @param  callable(InterviewSession): InterviewSession  $persistReportCallback
     * @return array{interview: InterviewSession, report: array<string,mixed>}
     */
    public function finishByEarlyTermination(
        InterviewSession $interview,
        InterviewQuestion $question,
        string $reason,
        int $consecutiveLowAnswers,
        callable $persistReportCallback
    ): array {
        $interview->update([
            'status' => InterviewSession::STATUS_COMPLETED,
            'answered_count' => (int) InterviewQuestion::query()
                ->where('interview_session_id', $interview->id)
                ->whereNotNull('answer')
                ->count(),
        ]);

        $interview = $persistReportCallback($interview->fresh());
        $report = is_array($interview->report) ? $interview->report : [];
        $report['early_termination'] = [
            'enabled_by_ai' => true,
            'reason' => $reason,
            'consecutive_low_answers' => $consecutiveLowAnswers,
            'ended_at' => now()->toDateTimeString(),
        ];
        $interview->forceFill(['report' => $report])->save();

        return ['interview' => $interview, 'report' => $report];
    }
}
