<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TurnstileValid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('人机验证失败，请重试。');

            return;
        }

        $secretKey = config('services.turnstile.secret_key');

        if ($secretKey === null || $secretKey === '') {
            $fail('人机验证服务未配置，请联系管理员。');

            return;
        }

        try {
            $response = Http::timeout(5)
                ->asForm()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('Turnstile verification HTTP error', [
                    'status' => $response->status(),
                ]);
                $fail('人机验证服务暂时不可用，请稍后重试。');

                return;
            }

            $data = $response->json();

            if (($data['success'] ?? false) !== true) {
                $errorCodes = $data['error-codes'] ?? [];
                Log::info('Turnstile verification failed', [
                    'error_codes' => $errorCodes,
                ]);
                $fail('人机验证失败，请重试。');
            }
        } catch (\Throwable $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
            ]);
            $fail('人机验证服务暂时不可用，请稍后重试。');
        }
    }
}
