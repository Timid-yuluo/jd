<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 测评答案提交请求验证
 */
final class AssessmentSubmitRequest extends FormRequest
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
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'min:1'],
            'answers.*.option' => ['required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => '答案不能为空',
            'answers.array' => '答案格式不正确',
            'answers.*.question_id.required' => '题目ID必填',
            'answers.*.option.required' => '每题必须选择一个选项',
        ];
    }
}
