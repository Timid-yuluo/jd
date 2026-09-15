<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOAuthAccount;
use App\Services\Auth\OAuthProviderConfigService;
use App\Services\Auth\OAuthStateService;
use App\Services\Auth\OAuthUserResolutionService;
use App\Services\LoginHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class OAuthController extends Controller
{
    private ?OAuthProviderConfigService $providerConfig = null;

    private ?OAuthUserResolutionService $userResolution = null;

    private function providerConfig(): OAuthProviderConfigService
    {
        return $this->providerConfig ??= app(OAuthProviderConfigService::class);
    }

    private function userResolution(): OAuthUserResolutionService
    {
        return $this->userResolution ??= app(OAuthUserResolutionService::class);
    }

    public function redirectForLogin(Request $request, string $provider): RedirectResponse
    {
        if (! $this->providerConfig()->isQuickLoginProviderEnabled($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => '该快捷登录渠道未启用。']);
        }

        if (! $this->providerConfig()->hasProviderCredentials($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => '快捷登录配置不完整，请联系管理员。']);
        }

        $state = app(OAuthStateService::class)->issue($request, 'login', $provider);

        $authorizationUrl = $this->userResolution()->buildAuthorizationUrl($provider, $state);
        if ($authorizationUrl !== null) {
            return redirect()->away($authorizationUrl);
        }

        return redirect()->route('login')
            ->withErrors(['email' => 'OAuth 回调功能接入中，请先使用账号密码登录。']);
    }

    public function callbackForLogin(Request $request, string $provider): RedirectResponse
    {
        if (! $this->providerConfig()->isSupportedProvider($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => '不支持的快捷登录渠道。']);
        }

        if (! $this->providerConfig()->isQuickLoginProviderEnabled($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => '该快捷登录渠道未启用。']);
        }

        if (! $this->providerConfig()->hasProviderCredentials($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => '快捷登录配置不完整，请联系管理员。']);
        }

        $state = trim((string) $request->query('state', ''));
        if (! app(OAuthStateService::class)->consume($request, 'login', $provider, $state)) {
            $this->userResolution()->recordUserAudit(null, 'oauth_login_state_failed_'.$provider, $request, [
                'provider' => $provider,
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => '授权状态校验失败，请重新发起快捷登录。']);
        }

        $identity = $this->userResolution()->resolveProviderIdentityFromCallback($request, $provider);

        if ($identity === null) {
            return redirect()->route('login')
                ->withErrors(['email' => '登录失败：未获取到第三方用户信息。']);
        }

        $providerUserId = trim((string) ($identity['provider_user_id'] ?? ''));
        if ($providerUserId === '') {
            $this->userResolution()->recordUserAudit(null, 'oauth_login_missing_provider_user_id_'.$provider, $request, [
                'provider' => $provider,
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => '登录失败：缺少第三方账户标识。']);
        }

        $providerEmail = trim((string) ($identity['provider_email'] ?? ''));
        $providerName = trim((string) ($identity['provider_name'] ?? ''));

        $user = $this->userResolution()->resolveOrCreateUserForLogin($provider, $providerUserId, $providerEmail, $providerName);

        if (! $user instanceof User) {
            $this->userResolution()->recordUserAudit(null, 'oauth_login_unbound_account_'.$provider, $request, [
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => '当前账号未绑定该快捷登录渠道，且系统未开启自动注册。']);
        }

        if ($user->isSuspended()) {
            return redirect()->route('login')
                ->withErrors(['email' => '账号已被封禁，请联系管理员处理。']);
        }

        Auth::login($user, true);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        app(LoginHistoryService::class)->record($user, $request, true);
        $request->session()->regenerate();

        $this->userResolution()->recordUserAudit($user->id, 'oauth_login_success_'.$provider, $request, [
            'provider' => $provider,
        ]);

        return redirect()->intended(route('user.profile'));
    }

    public function callbackUnified(Request $request, string $provider): RedirectResponse
    {
        try {
            Log::info('OAuth callback start', ['provider' => $provider, 'has_state' => $request->has('state'), 'has_auth_code' => $request->has('auth_code')]);

            $state = trim((string) $request->query('state', ''));
            $stateService = app(OAuthStateService::class);

            $scene = $stateService->detectScene($provider, $state);
            Log::info('OAuth callback scene', ['scene' => $scene]);

            if (
                $stateService->detectScene($provider, $state) === 'binding'
                || $stateService->peek($request, 'binding', $provider, $state)
            ) {
                Log::info('OAuth entering binding branch', ['has_user' => $request->user() !== null]);

                if (! $request->user()) {
                    $bindingUserId = $stateService->extractBindingUserId($provider, $state);
                    Log::info('OAuth binding auto-login', ['user_id' => $bindingUserId]);

                    if ($bindingUserId !== null) {
                        try {
                            $bindingUser = User::query()->find($bindingUserId);
                            if ($bindingUser instanceof User && ! $bindingUser->isSuspended()) {
                                Auth::login($bindingUser, true);
                                $request->setUserResolver(static fn (): User => $bindingUser);
                            }
                        } catch (\Throwable $e) {
                            Log::error('OAuth auto-login failed', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    }
                }

                return $this->callbackForBinding($request, $provider);
            }

            return $this->callbackForLogin($request, $provider);
        } catch (\Throwable $e) {
            Log::error('OAuth callbackUnified exception', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => '快捷登录处理异常，请稍后重试。']);
        }
    }

    public function redirectForBinding(Request $request, string $provider): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $this->providerConfig()->isBindingProviderEnabled($provider)) {
            return redirect()->route('user.profile')
                ->with('warning', '该账号绑定渠道未启用。');
        }

        if (! $this->providerConfig()->hasProviderCredentials($provider)) {
            return redirect()->route('user.profile')
                ->with('warning', '账号绑定配置不完整，请联系管理员。');
        }

        $state = app(OAuthStateService::class)->issue($request, 'binding', $provider);

        $authorizationUrl = $this->userResolution()->buildAuthorizationUrl($provider, $state);
        if ($authorizationUrl !== null) {
            return redirect()->away($authorizationUrl);
        }

        return redirect()->route('user.profile')
            ->with('warning', '账号绑定功能接入中，敬请期待。');
    }

    public function callbackForBinding(Request $request, string $provider): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $this->providerConfig()->isSupportedProvider($provider)) {
            return redirect()->route('user.profile')
                ->with('warning', '不支持的账号绑定渠道。');
        }

        if (! $this->providerConfig()->isBindingProviderEnabled($provider)) {
            return redirect()->route('user.profile')
                ->with('warning', '该账号绑定渠道未启用。');
        }

        $state = trim((string) $request->query('state', ''));
        if (! app(OAuthStateService::class)->consume($request, 'binding', $provider, $state)) {
            $this->userResolution()->recordUserAudit((int) $request->user()->id, 'oauth_bind_state_failed_'.$provider, $request, [
                'provider' => $provider,
            ]);

            return redirect()->route('user.profile')
                ->with('warning', '绑定失败：授权状态校验失败，请重新发起绑定。');
        }

        $identity = $this->userResolution()->resolveProviderIdentityFromCallback($request, $provider);

        if ($identity === null) {
            return redirect()->route('user.profile')
                ->with('warning', '绑定失败：未获取到第三方用户信息。');
        }

        $providerUserId = trim((string) ($identity['provider_user_id'] ?? ''));
        if ($providerUserId === '') {
            return redirect()->route('user.profile')
                ->with('warning', '绑定失败：缺少第三方账户标识。');
        }

        $providerEmail = trim((string) ($identity['provider_email'] ?? ''));
        $providerName = trim((string) ($identity['provider_name'] ?? ''));
        $user = $request->user();

        $boundByOther = $this->userResolution()->bindProviderToUser($user, $provider, $providerUserId, $providerEmail, $providerName);

        if ($boundByOther) {
            return redirect()->route('user.profile')
                ->with('warning', '该第三方账号已绑定到其他用户，无法重复绑定。');
        }

        $this->userResolution()->recordUserAudit((int) $user->id, 'oauth_bind_success_'.$provider, $request, [
            'provider' => $provider,
        ]);

        return redirect()->route('user.profile')
            ->with('success', '账号绑定成功。');
    }

    public function disconnect(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->providerConfig()->isSupportedProvider($provider)) {
            return redirect()->route('user.profile')
                ->with('warning', '不支持的账号绑定渠道。');
        }

        if (! $this->providerConfig()->isUnbindAllowed()) {
            return redirect()->route('user.profile')
                ->with('warning', '当前系统不允许解绑第三方账号。');
        }

        if ($this->providerConfig()->requiresPasswordConfirmOnUnbind()) {
            $password = (string) $request->input('password', '');
            if ($password === '' || ! Hash::check($password, (string) $user->password)) {
                return redirect()->route('user.profile')
                    ->with('warning', '解绑失败：请先输入正确的登录密码。');
            }
        }

        $deleted = UserOAuthAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        if ($deleted === 0) {
            return redirect()->route('user.profile')
                ->with('warning', '该渠道当前未绑定，无需解绑。');
        }

        $this->userResolution()->recordUserAudit((int) $user->id, 'oauth_unbind_success_'.$provider, $request, [
            'provider' => $provider,
        ]);

        return redirect()->route('user.profile')
            ->with('success', '账号解绑成功。');
    }
}
