<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 学习路径分析请求
 */
final class LearningPathAnalyzeRequest extends FormRequest
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
            'target_job' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:60'],
            'experience_years' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_job.required' => '目标岗位必填',
        ];
    }
}
