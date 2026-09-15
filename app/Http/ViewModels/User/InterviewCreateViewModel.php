<?php

declare(strict_types=1);

namespace App\Http\ViewModels\User;

use App\Models\Resume;
use Illuminate\Support\Collection;

final readonly class InterviewCreateViewModel
{
    /**
     * @param  Collection<int, Resume>  $resumes
     * @param  array<string, mixed>  $planSummary
     */
    public function __construct(
        public Collection $resumes,
        public array $planSummary,
    ) {}

    public function customQuestionsEnabled(): bool
    {
        return (bool) ($this->planSummary['customQuestionsEnabled'] ?? false);
    }

    public function interviewMaxQuestions(): int
    {
        return max(1, (int) ($this->planSummary['interviewMaxQuestions'] ?? config('interview.max_questions', 5)));
    }

    public function maxQuestionSlider(): int
    {
        return max(5, $this->interviewMaxQuestions());
    }

    public function minQuestionSlider(): int
    {
        return min(5, $this->maxQuestionSlider());
    }

    public function defaultQuestionCount(): int
    {
        return min(5, $this->maxQuestionSlider());
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionQuota(): array
    {
        return is_array($this->planSummary['interviewSessionQuotaCheck'] ?? null)
            ? $this->planSummary['interviewSessionQuotaCheck']
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluationQuota(): array
    {
        return ['allowed' => true, 'monthly_limit' => -1, 'monthly_used' => 0];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sessionQuotaNotice(): ?array
    {
        return is_array($this->planSummary['interviewSessionNotice'] ?? null)
            ? $this->planSummary['interviewSessionNotice']
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function evaluationQuotaNotice(): ?array
    {
        return null;
    }

    public function sessionAutoCreditReady(): bool
    {
        $notice = $this->sessionQuotaNotice();
        $autoCreditCheck = is_array($this->planSummary['interviewSessionAutoCreditCheck'] ?? null)
            ? $this->planSummary['interviewSessionAutoCreditCheck']
            : [];

        return (($notice['credit_available'] ?? false) === true)
            && (($autoCreditCheck['allowed'] ?? false) === true);
    }

    public function hasCreditContinuation(): bool
    {
        $sessionNotice = $this->sessionQuotaNotice();
        $evalNotice = $this->evaluationQuotaNotice();

        return (($sessionNotice['credit_available'] ?? false) === true)
            || (($evalNotice['credit_available'] ?? false) === true);
    }

    public function formatQuotaSummary(array $quota): string
    {
        $limit = (int) ($quota['monthly_limit'] ?? 0);
        $used = (int) ($quota['monthly_used'] ?? 0);

        if ($limit === -1) {
            return '不限';
        }

        $remaining = max(0, $limit - $used);

        return "本月已用 {$used}/{$limit}，剩余 {$remaining}";
    }

    public function resumesIsEmpty(): bool
    {
        return $this->resumes->isEmpty();
    }

    /**
     * @return array<string, mixed>
     */
    public function interviewTypeGroups(): array
    {
        return config('interview.interview_type_groups', []);
    }

    /**
     * @return array<string, array<string>>
     */
    public function profileTypeRecommendations(): array
    {
        return config('interview.profile_type_recommendations', []);
    }
}
