<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 面试评级请求校验（#37）
 *
 * 关联路由：POST /user/companies/{company}/rate-interview
 */
final class RateInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 校验规则
     */
    public function rules(): array
    {
        return [
            'had_interview' => ['required', 'boolean'],
            'interview_result' => [
                'nullable',
                'string',
                'in:offer,rejected,withdrew,negotiating',
            ],
            'interview_difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'interview_question_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'interview_duration' => ['nullable', 'integer', 'min:1', 'max:480'],
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'had_interview.required' => '请选择是否参加过面试',
            'had_interview.boolean' => '面试状态参数非法',
            'interview_result.in' => '面试结果取值非法',
            'interview_difficulty.min' => '面试难度评级需在 1-5 之间',
            'interview_difficulty.max' => '面试难度评级需在 1-5 之间',
        ];
    }
}
