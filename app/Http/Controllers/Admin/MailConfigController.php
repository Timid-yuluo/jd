<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Services\Admin\MailRuntimeConfigService;
use App\Services\Admin\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * 邮件配置管理控制器
 */
final class MailConfigController extends Controller
{
    /**
     * 邮件配置页面
     */
    public function index(SystemSettingService $settings): View
    {
        $config = [
            'mailer' => $settings->get('mail_mailer', (string) config('mail.default', 'log')),
            'host' => $settings->get('mail_host', (string) config('mail.mailers.smtp.host', '')),
            'port' => $settings->get('mail_port', (string) config('mail.mailers.smtp.port', '587')),
            'username' => $settings->get('mail_username', (string) config('mail.mailers.smtp.username', '')),
            'password' => $settings->get('mail_password', (string) config('mail.mailers.smtp.password', '')),
            'encryption' => $settings->get('mail_encryption', (string) config('mail.mailers.smtp.encryption', 'tls')),
            'from_address' => $settings->get('mail_from_address', (string) config('mail.from.address', '')),
            'from_name' => $settings->get('mail_from_name', (string) config('mail.from.name', config('app.name'))),
            'mail_email_verification_rate_limit' => $settings->get('mail_email_verification_rate_limit', '60'),
        ];

        // 邮件发送统计
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::sent()->count(),
            'failed' => EmailLog::failed()->count(),
            'today' => EmailLog::whereDate('created_at', today())->count(),
        ];

        // 最近的邮件日志
        $recentLogs = EmailLog::with('user')
            ->orderByDesc('created_at')
            ->limit((int) config('ui.limit.mail_test', 10))
            ->get();

        return view('admin.mail-config.index', compact('config', 'stats', 'recentLogs'));
    }

    /**
     * 保存邮件配置
     */
    public function update(Request $request, SystemSettingService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'mailer' => 'required|string|in:smtp,sendmail,log,array',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|string|in:tls,ssl,null',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'email_verification_rate_limit' => 'nullable|integer|min:0|max:3600',
        ]);

        // 保存到系统设置
        foreach ($validated as $key => $value) {
            $settings->set("mail_$key", $value);
        }

        // 清除配置缓存
        $this->clearConfigCache();

        return redirect()->route('admin.mail-config.index')
            ->with('success', '邮件配置已保存。');
    }

    /**
     * 测试邮件发送
     */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // 临时设置邮件配置
            $this->applyMailConfig();

            $startTime = microtime(true);

            Mail::raw('这是一封测试邮件。如果您收到此邮件，说明邮件配置正确。', function ($message) use ($validated) {
                $message->to($validated['test_email'])
                    ->subject('邮件配置测试 - '.config('app.name'));
            });

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // 记录测试日志
            EmailLog::create([
                'recipient_email' => $validated['test_email'],
                'subject' => '邮件配置测试',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '测试邮件发送成功',
                'duration' => $duration.'ms',
            ]);
        } catch (\Exception $e) {
            Log::error('邮件测试失败', [
                'email' => $validated['test_email'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '发送失败: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 重试发送失败的邮件
     */
    public function retry(Request $request, EmailLog $emailLog): JsonResponse
    {
        if ($emailLog->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => '只有失败的邮件可以重试',
            ], 400);
        }

        try {
            $this->applyMailConfig();

            Mail::html($emailLog->content ?? '邮件内容', function ($message) use ($emailLog) {
                $message->to($emailLog->recipient_email)
                    ->subject($emailLog->subject);
            });

            $emailLog->markAsSent();

            return response()->json([
                'success' => true,
                'message' => '邮件重发成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '重发失败: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取邮件统计API
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::sent()->count(),
            'failed' => EmailLog::failed()->count(),
            'pending' => EmailLog::where('status', 'pending')->count(),
            'today' => EmailLog::whereDate('created_at', today())->count(),
            'this_week' => EmailLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        // 最近7天的发送趋势
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[] = [
                'date' => $date->format('m-d'),
                'count' => EmailLog::whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'trend' => $trend,
        ]);
    }

    /**
     * 应用邮件配置
     */
    private function applyMailConfig(): void
    {
        app(MailRuntimeConfigService::class)->applyFromSystemSettings();
    }

    /**
     * 清除配置缓存
     */
    private function clearConfigCache(): void
    {
        Artisan::call('config:clear');
    }
}
