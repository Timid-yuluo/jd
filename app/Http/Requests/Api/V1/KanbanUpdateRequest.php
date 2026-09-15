<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class KanbanUpdateRequest extends FormRequest
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
            'company' => ['sometimes', 'required', 'string', 'max:120'],
            'position' => ['sometimes', 'required', 'string', 'max:120'],
            'status' => ['sometimes', 'required', 'string', 'in:wishlist,applied,written,interview,offer,rejected'],
            'deadline' => ['nullable', 'date'],
            'channel' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string'],
        ];
    }
}
