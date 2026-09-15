<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreditPackPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credit_pack_id' => ['required', 'integer', 'exists:credit_packs,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_pack_id.required' => '请选择要购买的次卡。',
            'credit_pack_id.exists' => '所选次卡不存在或已下架。',
        ];
    }
}
