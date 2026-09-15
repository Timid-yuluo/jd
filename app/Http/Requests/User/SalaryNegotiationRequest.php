<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 薪资谈判请求验证
 */
final class SalaryNegotiationRequest extends FormRequest
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
            'job_title' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'current_salary' => ['required', 'integer', 'min:0', 'max:10000000'],
            'target_salary' => ['required', 'integer', 'min:0', 'max:10000000', 'gte:current_salary'],
            'city' => ['nullable', 'string', 'max:60'],
            'experience_years' => ['nullable', 'string', 'in:0-1,1-3,3-5,5-10,10+'],
            'context' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_title.required' => '岗位名称必填',
            'current_salary.required' => '当前薪资必填',
            'target_salary.required' => '期望薪资必填',
            'target_salary.gte' => '期望薪资不能低于当前薪资',
            'experience_years.in' => '经验年限格式不正确',
        ];
    }
}
