<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 薪资数据上报请求验证
 */
final class SalaryReportRequest extends FormRequest
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
            'city' => ['nullable', 'string', 'max:60'],
            'industry' => ['nullable', 'string', 'max:60'],
            'salary_min' => ['required', 'integer', 'min:0', 'max:10000000'],
            'salary_max' => ['required', 'integer', 'min:0', 'max:10000000', 'gte:salary_min'],
            'currency' => ['nullable', 'string', 'max:10'],
            'experience_level' => ['nullable', 'string', 'max:30'],
            'reported_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'job_title.required' => '岗位名称必填',
            'salary_min.required' => '薪资下限必填',
            'salary_max.required' => '薪资上限必填',
            'salary_max.gte' => '薪资上限不能低于下限',
        ];
    }
}
