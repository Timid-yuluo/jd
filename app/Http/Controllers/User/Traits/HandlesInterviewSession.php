<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Services\Interview\InterviewAnswerSubmissionService;
use App\Services\Interview\InterviewSessionService;
use Illuminate\Http\JsonResponse;

trait HandlesInterviewSession
{
    abstract protected function interviewSessionService(): InterviewSessionService;

    abstract protected function submissionService(): InterviewAnswerSubmissionService;

    private function buildSubmitAnswerResponse(
        InterviewSession $interview,
        InterviewQuestion $question,
        bool $idempotent,
        ?array $aiDialogueDecision = null
    ): JsonResponse {
        if ($interview->status === InterviewSession::STATUS_COMPLETED) {
            $report = is_array($interview->report) ? $interview->report : [];
            $earlyTermination = is_array($report['early_termination'] ?? null) ? $report['early_termination'] : null;
            $answerReply = '';

            return response()->json([
                'finished' => true,
                'terminated_early' => $earlyTermination !== null,
                'termination_reason' => is_array($earlyTermination) ? (string) ($earlyTermination['reason'] ?? '') : null,
                'idempotent' => $idempotent,
                'question_id' => (int) $question->id,
                'scoring_pending' => false,
                'score' => $question->score !== null ? (int) $question->score : null,
                'feedback' => is_array($question->feedback) ? $question->feedback : [],
                'answer_reply' => $answerReply,
                'answer_quality' => $this->submissionService()->buildAnswerQualityMeta((string) ($question->answer ?? '')),
                'counter_question' => null,
                'redirect' => route('user.interviews.report', $interview),
            ]);
        }

        $nextQuestion = InterviewQuestion::where('interview_session_id', $interview->id)
            ->whereNull('answer')
            ->orderBy('round_no')
            ->first();

        $scoringPending = $question->answer !== null && $question->score === null;
        $feedback = is_array($question->feedback) ? $question->feedback : [];
        $quality = $this->submissionService()->buildAnswerQualityMeta((string) ($question->answer ?? ''));
        $counterQuestions = $this->interviewSessionService()->resolveCounterQuestions($quality, $aiDialogueDecision);
        $counterQuestion = $counterQuestions[0] ?? null;
        $answerReply = $this->interviewSessionService()->resolveAnswerReply($question, $aiDialogueDecision);
        $dialogueMeta = $this->interviewSessionService()->resolveDialogueMeta($aiDialogueDecision, $counterQuestions);

        if (! $nextQuestion) {
            if ($interview->status !== InterviewSession::STATUS_COMPLETED) {
                $interview->update(['status' => InterviewSession::STATUS_COMPLETED]);
            }
            $interview = $this->persistInterviewReport($interview->fresh());

            return response()->json([
                'finished' => true,
                'idempotent' => $idempotent,
                'question_id' => (int) $question->id,
                'scoring_pending' => $scoringPending,
                'score' => $question->score !== null ? (int) $question->score : null,
                'feedback' => $feedback,
                'answer_reply' => $answerReply,
                'answer_quality' => $quality,
                'counter_question' => $counterQuestion,
                'counter_questions' => $counterQuestions,
                'dialogue' => $dialogueMeta,
                'redirect' => route('user.interviews.report', $interview),
            ]);
        }

        return response()->json([
            'finished' => false,
            'idempotent' => $idempotent,
            'question_id' => (int) $question->id,
            'scoring_pending' => $scoringPending,
            'score' => $question->score !== null ? (int) $question->score : null,
            'feedback' => $feedback,
            'answer_reply' => $answerReply,
            'answer_quality' => $quality,
            'counter_question' => $counterQuestion,
            'counter_questions' => $counterQuestions,
            'dialogue' => $dialogueMeta,
            'question' => [
                'id' => $nextQuestion->id,
                'round_no' => $nextQuestion->round_no,
                'dimension' => $nextQuestion->dimension,
                'question' => $nextQuestion->question,
            ],
        ]);
    }

    private function heartbeatCacheKey(int $interviewId): string
    {
        return $this->interviewSessionService()->heartbeatCacheKey($interviewId);
    }

    private function finishInterviewByEarlyTermination(
        InterviewSession $interview,
        InterviewQuestion $question,
        string $reason,
        int $consecutiveLowAnswers
    ): JsonResponse {
        $result = $this->interviewSessionService()->finishByEarlyTermination(
            $interview,
            $question,
            $reason,
            $consecutiveLowAnswers,
            fn (InterviewSession $session) => $this->persistInterviewReport($session)
        );

        $interview = $result['interview'];

        return response()->json([
            'finished' => true,
            'terminated_early' => true,
            'termination_reason' => $reason,
            'idempotent' => false,
            'question_id' => (int) $question->id,
            'scoring_pending' => false,
            'score' => $question->score !== null ? (int) $question->score : null,
            'feedback' => is_array($question->feedback) ? $question->feedback : [],
            'answer_reply' => $this->interviewSessionService()->buildAnswerReply($question),
            'answer_quality' => $this->submissionService()->buildAnswerQualityMeta((string) ($question->answer ?? '')),
            'counter_question' => null,
            'redirect' => route('user.interviews.report', $interview),
        ]);
    }
}
