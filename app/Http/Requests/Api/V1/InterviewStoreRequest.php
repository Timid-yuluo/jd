<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class InterviewStoreRequest extends FormRequest
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
            'resume_id' => ['nullable', 'integer', 'exists:resumes,id'],
            'position' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'type' => ['required', 'string', 'in:hr,technical,comprehensive'],
        ];
    }
}
