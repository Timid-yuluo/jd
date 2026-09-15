<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 简历优化请求验证 — 从 ResumeOptimizeController::optimize() 抽离
 */
final class ResumeOptimizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_job' => ['nullable', 'string', 'max:255'],
            'target_company' => ['nullable', 'string', 'max:120'],
            'target_job_title' => ['nullable', 'string', 'max:120'],
            'target_job_description' => ['nullable', 'string', 'max:5000'],
            'optimize_goals' => ['nullable', 'array'],
            'optimize_goals.*' => ['string', 'in:ats_keywords,structure,quantified,skill_match,language,highlights,tailor_job,concise,authenticity,readability,industry_fit,career_pivot,leadership,i18n_expression,project_impact,tech_depth,cross_cultural,certification,innovation,data_driven'],
        ];
    }

    /**
     * 获取经过清理的优化 payload
     *
     * @return array{target_job:string,target_company:string,target_job_title:string,target_job_description:string,optimize_goals:array<int,string>}
     */
    public function optimizePayload(array $resumeDefaults = []): array
    {
        $validated = $this->validated();

        return [
            'target_job' => (string) ($validated['target_job'] ?? $resumeDefaults['target_job'] ?? ''),
            'target_company' => (string) ($validated['target_company'] ?? $resumeDefaults['target_company'] ?? ''),
            'target_job_title' => (string) ($validated['target_job_title'] ?? $resumeDefaults['target_job_title'] ?? ''),
            'target_job_description' => (string) ($validated['target_job_description'] ?? $resumeDefaults['target_job_description'] ?? ''),
            'optimize_goals' => $validated['optimize_goals'] ?? [],
        ];
    }
}
