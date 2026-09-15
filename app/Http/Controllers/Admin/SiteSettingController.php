<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSettingAuditLog;
use App\Services\Admin\SystemSettingService;
use App\Services\Admin\SystemSettingUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
        private readonly SystemSettingUpdateService $updateService,
    ) {}

    public function index(): View
    {
        $settings = $this->systemSettingService->all();
        $recentAuditLogs = SystemSettingAuditLog::query()
            ->with('changedByUser')
            ->latest('id')
            ->limit((int) config('ui.limit.admin_audit', 20))
            ->get();

        return view('admin.site-settings.index', [
            'settings' => $settings,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // 基础信息
            'site_name' => 'required|string|max:120',
            'site_subtitle' => 'nullable|string|max:180',
            'site_url' => 'nullable|url|max:255',
            'site_logo_url' => 'nullable|url|max:255',
            'favicon_url' => 'nullable|url|max:255',
            'site_logo_file' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:'.config('ui.upload.image_max_kb', 2048),
            'favicon_file' => 'nullable|image|mimes:png,jpg,jpeg,webp,ico|max:1024',
            'remove_site_logo' => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',
            'contact_email' => 'nullable|email|max:120',
            'copyright_text' => 'nullable|string|max:255',
            'maintenance_notice' => 'nullable|string|max:2000',
            'seo_keywords' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:300',
            'icp_number' => 'nullable|string|max:120',
            'police_record_number' => 'nullable|string|max:120',
            'police_record_url' => 'nullable|url|max:255',
            'wechat_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            // 功能开关
            'maintenance_mode' => 'nullable|boolean',
            'allow_registration' => 'nullable|boolean',
            'email_verification_required' => 'nullable|boolean',
            'allow_guest_resume_view' => 'nullable|boolean',
            // 主题外观
            'theme_primary_color' => 'nullable|string|max:7',
            'theme_layout_mode' => 'nullable|string|in:light,dark,auto',
            'theme_sidebar_collapsed' => 'nullable|boolean',
            'editor_scroll_mode' => 'nullable|string|in:natural,split',
            // 邮件设置
            'mail_from_address' => 'nullable|email|max:120',
            'mail_from_name' => 'nullable|string|max:120',
            // 快捷登录与账号绑定
            'auth_quick_login_enabled' => 'nullable|boolean',
            'auth_account_binding_enabled' => 'nullable|boolean',
            'auth_provider_github_enabled' => 'nullable|boolean',
            'auth_provider_alipay_enabled' => 'nullable|boolean',
            'auth_quick_login_auto_register' => 'nullable|boolean',
            'auth_quick_login_link_by_email' => 'nullable|boolean',
            'auth_oauth_state_ttl_seconds' => 'nullable|integer|min:60|max:1800',
            'auth_github_client_id' => 'nullable|string|max:120',
            'auth_github_client_secret' => 'nullable|string|max:255',
            'auth_github_redirect_url' => 'nullable|url|max:255',
            'auth_alipay_app_id' => 'nullable|string|max:120',
            'auth_alipay_public_key' => 'nullable|string|max:5000',
            'auth_alipay_private_key' => 'nullable|string|max:5000',
            'auth_alipay_redirect_url' => 'nullable|url|max:255',
            'auth_alipay_verify_sign_enabled' => 'nullable|boolean',
            'auth_binding_allow_unbind' => 'nullable|boolean',
            'auth_binding_require_password_confirm' => 'nullable|boolean',
            // 安全设置
            'max_login_attempts' => 'nullable|integer|min:1|max:10',
            'password_min_length' => 'nullable|integer|min:6|max:32',
            'session_lifetime' => 'nullable|integer|min:10|max:480',
            // 第三方统计
            'analytics_google' => 'nullable|string|max:5000',
            'analytics_baidu' => 'nullable|string|max:5000',
            'analytics_clarity' => 'nullable|string|max:5000',
            // 自定义代码
            'custom_head_code' => 'nullable|string|max:5000',
            'custom_body_code' => 'nullable|string|max:5000',
            // OCR 设置
            'ocr_provider' => 'nullable|string|in:baidu,tencent,aliyun',
            'baidu_ocr_api_key' => 'nullable|string|max:255',
            'baidu_ocr_secret_key' => 'nullable|string|max:255',
            'tencent_ocr_secret_id' => 'nullable|string|max:255',
            'tencent_ocr_secret_key' => 'nullable|string|max:255',
            'tencent_ocr_region' => 'nullable|string|max:50',
            'aliyun_ocr_access_key_id' => 'nullable|string|max:255',
            'aliyun_ocr_access_key_secret' => 'nullable|string|max:255',
            // 支付配置
            'payment_alipay_enabled' => 'nullable|boolean',
            'payment_alipay_app_id' => 'nullable|string|max:64',
            'payment_alipay_private_key' => 'nullable|string|max:5000',
            'payment_alipay_public_key' => 'nullable|string|max:5000',
            'payment_alipay_notify_url' => 'nullable|url|max:255',
        ]);

        $githubClientId = trim((string) ($validated['auth_github_client_id'] ?? ''));
        if ($githubClientId !== '' && preg_match('/^\d+$/', $githubClientId) === 1) {
            return back()
                ->withErrors([
                    'auth_github_client_id' => 'GitHub 这里需要填写 OAuth App 的 Client ID，不能填写纯数字的 App ID。',
                ])
                ->withInput();
        }

        $result = $this->updateService->update($validated, $request);

        return redirect()->route('admin.site-settings.index')
            ->with('success', $result['message']);
    }

    /**
     * 清除系统缓存
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => '缓存已清除成功',
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    public function toggleMaintenance(): JsonResponse
    {
        $isDown = file_exists(storage_path('framework/down'));
        if ($isDown) {
            Artisan::call('up');
            $message = '维护模式已关闭，网站已恢复正常访问。';
        } else {
            Artisan::call('down', ['--secret' => bin2hex(random_bytes(16))]);
            $message = '维护模式已开启，普通用户无法访问。';
        }

        return response()->json(['success' => true, 'message' => $message, 'maintenance' => !$isDown]);
    }

    /**
     * 导出设置备份
     */
    public function export(): StreamedResponse
    {
        $settings = $this->systemSettingService->all();

        $secretKeys = [
            'payment_alipay_private_key', 'payment_alipay_public_key',
            'auth_alipay_private_key', 'auth_alipay_public_key',
            'auth_github_client_secret',
            'baidu_ocr_secret_key', 'tencent_ocr_secret_key', 'aliyun_ocr_access_key_secret',
        ];

        foreach ($secretKeys as $key) {
            if (isset($settings[$key]) && is_string($settings[$key]) && $settings[$key] !== '') {
                $len = strlen($settings[$key]);
                $settings[$key] = $len <= 8 ? '***' : substr($settings[$key], 0, 4).str_repeat('*', $len - 8).substr($settings[$key], -4);
            }
        }

        $backup = [
            'exported_at' => now()->toDateTimeString(),
            'version' => config('app.version', '1.0.0'),
            'settings' => $settings,
        ];

        $filename = 'site-settings-backup-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($backup) {
            echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function sanitizeHeadSnippets(Request $request): JsonResponse
    {
        $updatedKeys = $this->updateService->sanitizeSnippetsBulk();

        if ($updatedKeys === []) {
            return response()->json([
                'success' => true,
                'message' => '未检测到需要清理的头部异常代码。',
                'updated_keys' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '已完成头部异常代码清理。',
            'updated_keys' => $updatedKeys,
        ]);
    }
}
