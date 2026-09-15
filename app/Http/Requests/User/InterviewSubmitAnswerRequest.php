<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class InterviewSubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:interview_questions,id'],
            'answer' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'answer.min' => '回答至少需要 10 个字。',
            'answer.max' => '回答不能超过 10000 个字。',
            'answer.required' => '请输入你的回答。',
        ];
    }
}
