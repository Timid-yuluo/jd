<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

final class ResumeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'target_job' => ['nullable', 'string', 'max:255'],
            'content_raw' => ['sometimes', 'required', 'string'],
            'template' => ['nullable', 'string', 'in:classic,modern,minimal,timeline,creative,elegant'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '请输入简历标题。',
            'content_raw.required' => '请输入简历内容。',
        ];
    }
}
