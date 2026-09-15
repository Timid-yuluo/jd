<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Services\Admin\MailRuntimeConfigService;
use App\Services\Admin\SystemSettingService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 邮箱验证控制器
 */
final class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly RateLimiter $rateLimiter,
        private readonly SystemSettingService $systemSettingService,
        private readonly MailRuntimeConfigService $mailRuntimeConfigService,
    ) {}

    /**
     * 发送验证邮件
     */
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->back()->with('info', '您的邮箱已经验证过了。');
        }

        $ipKey = 'email-verify:ip:'.$request->ip();

        if ($this->rateLimiter->tooManyAttempts($ipKey, 3)) {
            $seconds = $this->rateLimiter->availableIn($ipKey);

            return redirect()->back()->with('warning', "发送过于频繁，请 {$seconds} 秒后再试。");
        }

        $this->rateLimiter->hit($ipKey, 600);

        // 检查发送频率限制
        $rateLimit = (int) $this->systemSettingService->get('mail_email_verification_rate_limit', '60');

        if ($rateLimit > 0) {
            $lastSent = session('verification_email_sent_at');
            if ($lastSent && now()->diffInSeconds($lastSent) < $rateLimit) {
                $waitSeconds = $rateLimit - now()->diffInSeconds($lastSent);

                return redirect()->back()->with('warning', "请等待 {$waitSeconds} 秒后再试。");
            }
        }

        // 记录发送时间
        session(['verification_email_sent_at' => now()]);

        // 生成验证令牌
        $token = Str::random(64);
        $user->verification_token = hash('sha256', $token);
        $user->verification_token_expires_at = now()->addHours(24);
        $user->save();

        try {
            // 应用邮件配置
            $this->applyMailConfig();

            // 发送验证邮件
            Mail::to($user->email)->queue(new VerifyEmailMail($user, $token));

            Log::info('验证邮件已发送', ['user_id' => $user->id]);

            return redirect()->back()->with('success', '验证邮件已发送到您的邮箱，请查收。');
        } catch (\Exception $e) {
            Log::error('验证邮件发送失败', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', '邮件发送失败，请稍后重试。若持续失败请联系管理员。');
        }
    }

    /**
     * 验证邮箱
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $ipKey = 'email-verify-check:'.$request->ip();
        if ($this->rateLimiter->tooManyAttempts($ipKey, 10)) {
            $seconds = $this->rateLimiter->availableIn($ipKey);

            return redirect()->route('user.dashboard')
                ->with('error', "尝试次数过多，请 {$seconds} 秒后再试。");
        }
        $this->rateLimiter->hit($ipKey, 300);

        $user = DB::transaction(function () use ($token) {
            $tokenHash = hash('sha256', $token);
            $user = User::query()
                ->where('verification_token', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            if ($user->verification_token_expires_at && now()->gt($user->verification_token_expires_at)) {
                return null;
            }

            if ($user->email_verified_at !== null) {
                return $user;
            }

            $user->email_verified_at = now();
            $user->verification_token = null;
            $user->verification_token_expires_at = null;
            $user->save();

            return $user;
        });

        if (! $user) {
            return redirect()->route('user.dashboard')
                ->with('error', '验证链接无效或已过期。');
        }

        $this->rateLimiter->clear($ipKey);

        Log::info('邮箱验证成功', ['user_id' => $user->id]);

        return redirect()->route('user.dashboard')->with('success', '邮箱验证成功！');
    }

    /**
     * 应用邮件配置
     */
    private function applyMailConfig(): void
    {
        $this->mailRuntimeConfigService->applyFromSystemSettings();
    }
}
