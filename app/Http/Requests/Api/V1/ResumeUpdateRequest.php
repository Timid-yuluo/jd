<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class ResumeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'target_job' => ['nullable', 'string', 'max:120'],
            'content_raw' => ['nullable', 'string'],
            'content_structured' => ['nullable', 'array'],
        ];
    }
}
