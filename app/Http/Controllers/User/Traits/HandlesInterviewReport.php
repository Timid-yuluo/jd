<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Services\Interview\InterviewReportService;

trait HandlesInterviewReport
{
    abstract protected function interviewReportService(): InterviewReportService;

    private function persistInterviewReport(InterviewSession $interview): InterviewSession
    {
        return $this->interviewReportService()->persistInterviewReport($interview);
    }

    /**
     * @param  array<int, InterviewQuestion>  $answeredQuestions
     * @return array<int, array{dimension:string,score:int,count:int}>
     */
    private function buildDimensionScores(InterviewSession $interview, array $answeredQuestions): array
    {
        return $this->interviewReportService()->buildDimensionScores($interview, $answeredQuestions);
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
    private function buildJdAlignmentMetrics(InterviewSession $interview, array $answeredQuestions): array
    {
        return $this->interviewReportService()->buildJdAlignmentMetrics($interview, $answeredQuestions);
    }

    /**
     * @return array<int, string>
     */
    private function extractJdKeywords(string $text): array
    {
        return $this->interviewReportService()->extractJdKeywords($text);
    }
}
