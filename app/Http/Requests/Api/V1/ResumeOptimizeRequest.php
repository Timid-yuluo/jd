<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ResumeOptimizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'target_job' => ['required', 'string', 'max:255'],
            'target_company' => ['nullable', 'string', 'max:120'],
            'target_job_title' => ['nullable', 'string', 'max:120'],
            'target_job_description' => ['nullable', 'string', 'max:5000'],
            'optimize_goals' => ['nullable', 'array'],
            'optimize_goals.*' => ['string', 'in:ats_keywords,structure,quantified,skill_match,language,highlights,tailor_job,concise,authenticity,readability,industry_fit,career_pivot,leadership,i18n_expression,project_impact,tech_depth,cross_cultural,certification,innovation,data_driven'],
        ];
    }
}
