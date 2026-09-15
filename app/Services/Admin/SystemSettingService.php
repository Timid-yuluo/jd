<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\SystemSetting;
use App\Providers\ViewServiceProvider;
use Illuminate\Support\Facades\Cache;

final class SystemSettingService
{
    private const CACHE_KEY = 'admin:system_settings:all';

    /** @var array<string,string>|null 请求级内存缓存，避免同一请求多次读取 Cache */
    private static ?array $requestCache = null;

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        // 请求级内存缓存：同一请求内直接返回，避免重复 Cache::get 开销
        if (self::$requestCache !== null) {
            return self::$requestCache;
        }

        /** @var array<string,string> $settings */
        $settings = Cache::remember(self::CACHE_KEY, (int) config('cache_ttl.ttl.standard', 300), static function (): array {
            return SystemSetting::query()
                ->pluck('value', 'key')
                ->map(static fn ($value): string => (string) $value)
                ->toArray();
        });

        self::$requestCache = $settings;

        return $settings;
    }

    public function get(string $key, ?string $default = ''): string
    {
        $settings = $this->all();

        return (string) ($settings[$key] ?? $default ?? '');
    }

    /**
     * 清除请求级内存缓存（设置变更后调用）
     */
    public static function clearRequestCache(): void
    {
        self::$requestCache = null;
    }

    /**
     * 保存单个设置
     */
    public function set(string $key, ?string $value): void
    {
        $this->saveMany([$key => $value]);
    }

    /**
     * @param  array<string,string|null>  $input
     * @return array<int, array{key:string,old_value:string|null,new_value:string|null}>
     */
    public function saveMany(array $input): array
    {
        if ($input === []) {
            return [];
        }

        $keys = array_keys($input);
        /** @var array<string,string|null> $originalMap */
        $originalMap = SystemSetting::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();

        $changes = [];
        foreach ($input as $key => $value) {
            $newValue = $value !== null ? trim((string) $value) : null;
            $oldValue = array_key_exists($key, $originalMap) ? $originalMap[$key] : null;

            if ($oldValue === $newValue) {
                continue;
            }

            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $newValue]);

            $changes[] = [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ];
        }

        if ($changes !== []) {
            Cache::forget(self::CACHE_KEY);
            Cache::forget('security:csp_template');
            // 联动清除视图共享变量缓存，确保前台立即看到设置变更
            ViewServiceProvider::clearSharedVarsCache();
            self::clearRequestCache();
        }

        return $changes;
    }
}
