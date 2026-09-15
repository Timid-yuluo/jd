<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 技能自评存储/更新请求
 */
final class SkillAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'skill_name' => ['required', 'string', 'max:80'],
            'skill_category' => ['required', Rule::in(['hard', 'soft'])],
            'proficiency' => ['required', 'integer', 'min:1', 'max:5'],
            'years_used' => ['nullable', 'integer', 'min:0', 'max:50'],
            'last_used_at' => ['nullable', 'date', 'before_or_equal:today'],
            'evidence' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'skill_name.required' => '技能名称必填',
            'skill_category.required' => '技能类别必填',
            'proficiency.required' => '熟练度必填',
            'proficiency.min' => '熟练度最低为 1',
            'proficiency.max' => '熟练度最高为 5',
        ];
    }
}
