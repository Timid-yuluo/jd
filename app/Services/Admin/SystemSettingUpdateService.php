<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\SystemSettingAuditLog;
use App\Providers\ViewServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 系统设置更新服务 — 从 SiteSettingController::update() 抽离
 */
final class SystemSettingUpdateService
{
    private const BOOLEAN_KEYS = [
        'maintenance_mode',
        'allow_registration',
        'email_verification_required',
        'allow_guest_resume_view',
        'theme_sidebar_collapsed',
        'auth_quick_login_enabled',
        'auth_account_binding_enabled',
        'auth_provider_github_enabled',
        'auth_provider_alipay_enabled',
        'auth_quick_login_auto_register',
        'auth_quick_login_link_by_email',
        'auth_alipay_verify_sign_enabled',
        'auth_binding_allow_unbind',
        'auth_binding_require_password_confirm',
        'payment_alipay_enabled',
    ];

    private const SNIPPET_KEYS = [
        'analytics_google',
        'analytics_baidu',
        'analytics_clarity',
        'custom_head_code',
        'custom_body_code',
    ];

    public function __construct(
        private readonly SystemSettingService $settingService,
    ) {}

    /**
     * 保存网站设置
     *
     * @param  array<string,mixed>  $validated
     * @return array{changes: array, message: string}
     */
    public function update(array $validated, Request $request): array
    {
        $currentSettings = $this->settingService->all();

        // 布尔字段归一化
        $validated = $this->normalizeBooleans($validated, $request);

        // 文件处理
        $validated = $this->handleFileUploads($validated, $request, $currentSettings);

        // Snippet 清洗
        $validated = $this->sanitizeSnippets($validated);

        // 移除临时字段
        unset(
            $validated['site_logo_file'],
            $validated['favicon_file'],
            $validated['remove_site_logo'],
            $validated['remove_favicon']
        );

        // 保存
        $changes = $this->settingService->saveMany($validated);

        // 审计日志
        $this->writeAuditLogs($changes, $request);

        return [
            'changes' => $changes,
            'message' => $changes !== [] ? '网站设置已保存。' : '未检测到配置变更。',
        ];
    }

    /**
     * 清洗头部 Snippet
     */
    public function sanitizeSnippetsBulk(): array
    {
        $currentSettings = $this->settingService->all();
        $toUpdate = [];

        foreach (self::SNIPPET_KEYS as $key) {
            $currentValue = is_string($currentSettings[$key] ?? null) ? (string) $currentSettings[$key] : '';
            $sanitized = ViewServiceProvider::sanitizeHeadSnippetPublic($currentValue);
            if ($currentValue !== $sanitized) {
                $toUpdate[$key] = $sanitized;
            }
        }

        if ($toUpdate !== []) {
            $this->settingService->saveMany($toUpdate);
        }

        return array_keys($toUpdate);
    }

    /**
     * @param  array<string,mixed>  $validated
     * @return array<string,mixed>
     */
    private function normalizeBooleans(array $validated, Request $request): array
    {
        foreach (self::BOOLEAN_KEYS as $key) {
            $validated[$key] = $request->boolean($key) ? '1' : '0';
        }

        return $validated;
    }

    /**
     * @param  array<string,mixed>  $validated
     * @param  array<string,string>  $currentSettings
     * @return array<string,mixed>
     */
    private function handleFileUploads(array $validated, Request $request, array $currentSettings): array
    {
        if ($request->boolean('remove_site_logo')) {
            $this->deleteManagedPublicFile((string) ($currentSettings['site_logo_url'] ?? ''));
            $validated['site_logo_url'] = null;
        }

        if ($request->boolean('remove_favicon')) {
            $this->deleteManagedPublicFile((string) ($currentSettings['favicon_url'] ?? ''));
            $validated['favicon_url'] = null;
        }

        if ($request->hasFile('site_logo_file')) {
            $file = $request->file('site_logo_file');
            $this->deleteManagedPublicFile((string) ($currentSettings['site_logo_url'] ?? ''));
            $path = $file->storeAs(
                'site-settings',
                'logo-'.Str::uuid().'.'.$file->getClientOriginalExtension(),
                'public'
            );
            $validated['site_logo_url'] = Storage::disk('public')->url($path);
        }

        if ($request->hasFile('favicon_file')) {
            $file = $request->file('favicon_file');
            $this->deleteManagedPublicFile((string) ($currentSettings['favicon_url'] ?? ''));
            $path = $file->storeAs(
                'site-settings',
                'favicon-'.Str::uuid().'.'.$file->getClientOriginalExtension(),
                'public'
            );
            $validated['favicon_url'] = Storage::disk('public')->url($path);
        }

        return $validated;
    }

    /**
     * @param  array<string,mixed>  $validated
     * @return array<string,mixed>
     */
    private function sanitizeSnippets(array $validated): array
    {
        foreach (self::SNIPPET_KEYS as $snippetKey) {
            if (isset($validated[$snippetKey]) && is_string($validated[$snippetKey])) {
                $validated[$snippetKey] = ViewServiceProvider::sanitizeHeadSnippetPublic($validated[$snippetKey]);
            }
        }

        return $validated;
    }

    /**
     * @param  array<int, array{key:string,old_value:string|null,new_value:string|null}>  $changes
     */
    private function writeAuditLogs(array $changes, Request $request): void
    {
        foreach ($changes as $change) {
            SystemSettingAuditLog::query()->create([
                'setting_key' => $change['key'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
                'changed_by_user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ]);
        }
    }

    public function deleteManagedPublicFile(string $url): void
    {
        if ($url === '') {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return;
        }

        if (! Str::startsWith($path, '/storage/site-settings/')) {
            return;
        }

        $relativePath = Str::after($path, '/storage/');
        if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
