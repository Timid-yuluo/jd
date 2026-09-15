<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Support\Interview\AnswerAnalyzer;

final class InterviewAnswerSubmissionService
{
    public function __construct(
        private readonly InterviewAnswerEvaluationService $evaluationService,
        private readonly InterviewQuestionGeneratorService $questionGenerator,
    ) {}

    /**
     * @return array{
     *   question:InterviewQuestion,
     *   interview:InterviewSession,
     *   ai_dialogue_decision:array<string,mixed>|null,
     *   early_termination:array{reason:string,consecutive:int}|null,
     *   should_dispatch_job:bool,
     * }
     */
    public function submit(
        InterviewSession $interview,
        InterviewQuestion $question,
        string $answer,
        string $answerHash
    ): array {
        $question->update([
            'answer' => $answer,
            'answer_hash' => $answerHash,
            'score' => null,
            'feedback' => null,
        ]);

        $answeredCount = InterviewQuestion::where('interview_session_id', $interview->id)
            ->whereNotNull('answer')
            ->count();
        $maxQuestions = $this->maxQuestions();
        $currentQuality = $this->buildAnswerQualityMeta($answer);
        $consecutiveLowAnswers = $this->countConsecutiveLowQualityAnswers($interview->id);
        $aiDialogueDecision = null;
        $minAnsweredQuestions = max(1, (int) config('interview.ai_early_termination_min_answered_questions', 2));
        $minTerminationConfidence = max(0.0, min(1.0, (float) config('interview.ai_early_termination_min_confidence', 0.75)));

        $aiEvaluation = null;
        $shouldAskAiTermination = (bool) config('interview.ai_early_termination_enabled', true)
            && $currentQuality['level'] === 'low'
            && $answeredCount >= $minAnsweredQuestions
            && $consecutiveLowAnswers >= max(1, (int) config('interview.ai_early_termination_min_consecutive_low_answers', 2));
        $syncDialogueEnabled = (bool) config('interview.ai_dialogue_sync_enabled', true);
        $syncDialogueMaxAnswerChars = max(20, (int) config('interview.ai_dialogue_sync_max_answer_chars', 90));
        $syncDialogueForceLowOnly = (bool) config('interview.ai_dialogue_sync_low_quality_only', false);
        $shouldSyncDialogueEvaluation = $syncDialogueEnabled
            && (mb_strlen($answer) <= $syncDialogueMaxAnswerChars)
            && (! $syncDialogueForceLowOnly || $currentQuality['level'] !== 'good');

        $earlyTermination = null;
        $shouldDispatchJob = false;

        if ($shouldAskAiTermination) {
            $aiEvaluation = $this->evaluationService->evaluate(
                (string) $question->question,
                $answer,
                (int) $question->interview_session_id,
                (int) $question->id,
                true,
                [
                    'interview_type' => (string) $interview->type,
                    'dimension' => (string) $question->dimension,
                ]
            );
            $syncFeedback = is_array($aiEvaluation['feedback'] ?? null) ? $aiEvaluation['feedback'] : [];
            $syncFluency = is_array($aiEvaluation['fluency'] ?? null) ? $aiEvaluation['fluency'] : [];
            $syncFluencyIssues = is_array($syncFluency['issues'] ?? null) ? $syncFluency['issues'] : [];
            if ($syncFluencyIssues !== []) {
                $syncFeedback['fluency_issues'] = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $syncFluencyIssues)));
                $syncFeedback['fluency_severity'] = (string) ($syncFluency['severity'] ?? 'medium');
            }
            $syncFeedback['fluency_confidence'] = max(0.0, min(1.0, (float) ($syncFluency['confidence'] ?? 0.0)));
            $syncFeedback['fluency_detected_by'] = (string) ($syncFluency['detected_by'] ?? 'rule');
            $syncDialogue = is_array($aiEvaluation['dialogue'] ?? null) ? $aiEvaluation['dialogue'] : [];
            $syncFeedback['dialogue_action'] = (string) ($syncDialogue['action'] ?? 'continue');
            $syncFeedback['dialogue_confidence'] = max(0.0, min(1.0, (float) ($syncDialogue['confidence'] ?? 0.0)));
            $aiDialogueDecision = $syncDialogue;
            $question->forceFill([
                'score' => (int) ($aiEvaluation['score'] ?? 2),
                'feedback' => $syncFeedback !== [] ? $syncFeedback : [
                    'comment' => '该回答信息量偏低，暂时难以评估能力。',
                    'suggestion' => '建议使用"背景-行动-结果-复盘"补充一个真实案例。',
                ],
            ])->save();

            $termination = is_array($aiEvaluation['termination'] ?? null) ? $aiEvaluation['termination'] : [];
            $aiWantsTerminate = (bool) ($termination['should_end'] ?? false);
            $aiConfidence = max(0.0, min(1.0, (float) ($termination['confidence'] ?? 0.0)));
            if ($aiWantsTerminate && $aiConfidence >= $minTerminationConfidence) {
                $reason = trim((string) ($termination['reason'] ?? '连续多次回答信息量过低，面试提前结束。'));
                $earlyTermination = [
                    'reason' => $reason,
                    'consecutive' => $consecutiveLowAnswers,
                ];
            }
        } elseif ((bool) config('interview.ai_dialogue_decision_enabled', true) && $shouldSyncDialogueEvaluation) {
            $aiDialogue = $this->evaluationService->evaluate(
                (string) $question->question,
                $answer,
                (int) $question->interview_session_id,
                (int) $question->id,
                true,
                [
                    'interview_type' => (string) $interview->type,
                    'dimension' => (string) $question->dimension,
                ]
            );
            $aiDialogueDecision = is_array($aiDialogue['dialogue'] ?? null) ? $aiDialogue['dialogue'] : null;
            $dialogueFeedback = is_array($aiDialogue['feedback'] ?? null) ? $aiDialogue['feedback'] : [];
            $dialogueFluency = is_array($aiDialogue['fluency'] ?? null) ? $aiDialogue['fluency'] : [];
            $dialogueFluencyIssues = is_array($dialogueFluency['issues'] ?? null) ? $dialogueFluency['issues'] : [];
            if ($dialogueFluencyIssues !== []) {
                $dialogueFeedback['fluency_issues'] = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $dialogueFluencyIssues)));
                $dialogueFeedback['fluency_severity'] = (string) ($dialogueFluency['severity'] ?? 'medium');
            }
            $dialogueFeedback['fluency_confidence'] = max(0.0, min(1.0, (float) ($dialogueFluency['confidence'] ?? 0.0)));
            $dialogueFeedback['fluency_detected_by'] = (string) ($dialogueFluency['detected_by'] ?? 'rule');
            $dialogueFeedback['dialogue_action'] = (string) (($aiDialogueDecision['action'] ?? 'continue'));
            $dialogueFeedback['dialogue_confidence'] = max(0.0, min(1.0, (float) (($aiDialogueDecision['confidence'] ?? 0.0))));

            $question->forceFill([
                'score' => (int) ($aiDialogue['score'] ?? 6),
                'feedback' => $dialogueFeedback,
            ])->save();
        } else {
            $shouldDispatchJob = true;
        }

        if ($answeredCount < $maxQuestions) {
            $nextRound = $answeredCount + 1;
            $hasUnansweredQuestion = InterviewQuestion::where('interview_session_id', $interview->id)
                ->whereNull('answer')
                ->exists();
            $hasNextRoundQuestion = InterviewQuestion::where('interview_session_id', $interview->id)
                ->where('round_no', $nextRound)
                ->exists();

            if (! $hasUnansweredQuestion && ! $hasNextRoundQuestion) {
                $nextQuestionPayload = $this->questionGenerator->generateForSession($interview, $nextRound, $answer);
                InterviewQuestion::create([
                    'interview_session_id' => $interview->id,
                    'round_no' => $nextRound,
                    'dimension' => (string) $nextQuestionPayload['dimension'],
                    'question' => (string) $nextQuestionPayload['question'],
                    'tags' => $nextQuestionPayload['tags'] ?? null,
                    'difficulty_level' => $nextQuestionPayload['difficulty_level'] ?? null,
                ]);
            }
        }

        $interview->update([
            'answered_count' => $answeredCount,
            'question_count' => max((int) $interview->question_count, min($maxQuestions, $answeredCount + 1)),
            'status' => $interview->status === 'completed' ? 'completed' : 'in_progress',
        ]);

        return [
            'question' => $question->fresh(),
            'interview' => $interview->fresh(),
            'ai_dialogue_decision' => $aiDialogueDecision,
            'early_termination' => $earlyTermination,
            'should_dispatch_job' => $shouldDispatchJob,
        ];
    }

    public function maxQuestions(): int
    {
        return max(1, (int) config('interview.max_questions', 5));
    }

    /**
     * @return array{level:string,reasons:array<int,string>,tips:array<int,string>}
     */
    public function buildAnswerQualityMeta(string $answer): array
    {
        $meta = AnswerAnalyzer::buildQualityMeta($answer);

        $reasons = [];
        $tips = [];
        $level = 'good';

        if ($meta['is_non_answer']) {
            $level = 'low';
            $reasons[] = '信息量偏低';
            $tips[] = '先给一个相近案例，再说明你做了什么。';
        }
        if ($meta['length'] < 20) {
            $level = 'low';
            $reasons[] = '回答过短';
            $tips[] = '补充背景、动作、结果、复盘四个要点。';
        } elseif ($meta['length'] < 80 && $level !== 'low') {
            $level = 'medium';
            $reasons[] = '细节略少';
            $tips[] = '建议补充关键决策和推进难点。';
        }
        if (! $meta['has_action_verb']) {
            $level = $level === 'good' ? 'medium' : $level;
            $reasons[] = '缺少行动过程';
            $tips[] = '写明你具体做了哪些动作。';
        }
        if (! $meta['has_metric']) {
            $level = $level === 'good' ? 'medium' : $level;
            $reasons[] = '缺少结果指标';
            $tips[] = '补充数据指标，如耗时、成本、转化率。';
        }
        if ($meta['has_fluency_risk']) {
            $level = $level === 'good' ? 'medium' : $level;
            $reasons[] = '语言不够通顺';
            $tips[] = '建议使用短句并分点描述，减少"然后/就是"等口头禅重复。';
        }

        return [
            'level' => $level,
            'reasons' => array_values(array_unique($reasons)),
            'tips' => array_values(array_unique($tips)),
        ];
    }

    public function countConsecutiveLowQualityAnswers(int $interviewId): int
    {
        $questions = InterviewQuestion::query()
            ->where('interview_session_id', $interviewId)
            ->whereNotNull('answer')
            ->orderByDesc('round_no')
            ->limit((int) config('ui.limit.dashboard_recent', 5))
            ->get(['answer']);

        $count = 0;
        foreach ($questions as $item) {
            $quality = $this->buildAnswerQualityMeta((string) ($item->answer ?? ''));
            if (($quality['level'] ?? 'good') !== 'low') {
                break;
            }
            $count++;
        }

        return $count;
    }
}
