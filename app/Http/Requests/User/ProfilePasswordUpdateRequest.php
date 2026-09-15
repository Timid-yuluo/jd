<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\PasswordHistory;
use App\Services\Admin\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class ProfilePasswordUpdateRequest extends FormRequest
{
    protected $errorBag = 'passwordUpdate';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $passwordMinLength = $this->resolvePasswordMinLength();

        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'min:'.$passwordMinLength,
                Password::defaults(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->validatePasswordHistory($value, $fail);
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '请输入当前密码。',
            'current_password.current_password' => '当前密码不正确，请重新输入。',
            'password.required' => '请输入新密码。',
            'password.min' => '新密码长度不能少于 :min 位。',
            'password.confirmed' => '两次输入的新密码不一致。',
        ];
    }

    private function validatePasswordHistory(mixed $value, \Closure $fail): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $historyLimit = 5;
        $recentHashes = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit($historyLimit)
            ->pluck('password_hash');

        foreach ($recentHashes as $oldHash) {
            if (Hash::check((string) $value, $oldHash)) {
                $fail('新密码不能与最近 '.$historyLimit.' 次使用的密码相同。');

                return;
            }
        }
    }

    private function resolvePasswordMinLength(): int
    {
        $raw = app(SystemSettingService::class)->get('password_min_length', '8');
        $value = (int) $raw;

        if ($value < 6) {
            return 8;
        }

        return min($value, 32);
    }
}
