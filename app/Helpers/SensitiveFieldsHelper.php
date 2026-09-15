<?php

declare(strict_types=1);

namespace App\Helpers;

final class SensitiveFieldsHelper
{
    /**
     * 统一的敏感字段列表，供所有日志中间件使用。
     *
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'api_token',
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'card_number',
        'cvv',
        'bank_account',
        'id_card',
        'phone',
        'mobile',
        'email',
        'verification_token',
        'recovery_token',
        'alipay_key',
        'alipay_secret',
        'private_key',
        'app_secret',
        'authorization',
        'cookie',
        'session_id',
    ];

    /**
     * 过滤 payload 中的敏感字段，支持嵌套结构。
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function filter(array $payload): array
    {
        return self::filterRecursive($payload);
    }

    /**
     * @param  array<string|int, mixed>  $data
     * @return array<string|int, mixed>
     */
    private static function filterRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = self::filterRecursive($value);
            }
        }

        return $data;
    }
}
