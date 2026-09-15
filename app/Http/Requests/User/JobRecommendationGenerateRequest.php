<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 触发岗位推荐生成请求验证
 */
final class JobRecommendationGenerateRequest extends FormRequest
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
            'resume_id' => ['nullable', 'integer', 'exists:resumes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume_id.integer' => '简历 ID 必须为整数',
            'resume_id.exists' => '所选简历不存在',
        ];
    }
}
