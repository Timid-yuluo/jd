<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class JobMatchAnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resume_id' => ['required', 'integer', 'exists:resumes,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'job_description' => ['required', 'string', 'max:10000'],
            'company' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_title.required' => '请输入岗位名称。',
            'job_description.required' => '请输入岗位描述。',
            'job_description.max' => '岗位描述不能超过 10000 字。',
        ];
    }
}
