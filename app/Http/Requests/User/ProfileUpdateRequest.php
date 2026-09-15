<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    protected $errorBag = 'profileUpdate';

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'school' => is_string($this->school) ? trim($this->school) : $this->school,
            'major' => is_string($this->major) ? trim($this->major) : $this->major,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class.',email,'.$this->user()->id],
            'school' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
        ];
    }
}
