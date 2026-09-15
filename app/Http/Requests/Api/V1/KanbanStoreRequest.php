<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class KanbanStoreRequest extends FormRequest
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
            'company' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'status' => ['required', 'string', 'in:wishlist,applied,written,interview,offer,rejected'],
            'deadline' => ['nullable', 'date'],
            'channel' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string'],
        ];
    }
}
