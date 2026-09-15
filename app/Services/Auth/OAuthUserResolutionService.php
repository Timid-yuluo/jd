<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserActionLog;
use App\Models\UserOAuthAccount;
use App\Services\Auth\Providers\AlipayOAuthProvider;
use App\Services\Auth\Providers\GithubOAuthProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class OAuthUserResolutionService
{
    public function __construct(
        private readonly OAuthProviderConfigService $config,
    ) {}

    /**
     * @return array{provider_user_id:string,provider_email:string,provider_name:string}|null
     */
    public function resolveProviderIdentityFromCallback(Request $request, string $provider): ?array
    {
        $providerUserId = trim((string) $request->query('provider_user_id', ''));
        if ($providerUserId !== '') {
            return [
                'provider_user_id' => $providerUserId,
                'provider_email' => trim((string) $request->query('provider_email', '')),
                'provider_name' => trim((string) $request->query('provider_name', '')),
            ];
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '' && $provider === 'alipay') {
            $code = trim((string) $request->query('auth_code', ''));
        }
        if ($code === '') {
            return null;
        }

        try {
            return match ($provider) {
                'github' => app(GithubOAuthProvider::class)->fetchUserIdentityByCode($code),
                'alipay' => app(AlipayOAuthProvider::class)->fetchUserIdentityByAuthCode($code),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->recordUserAudit($request->user()?->id, 'oauth_callback_fetch_identity_failed_'.$provider, $request, [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            Log::error('OAuth identity fetch failed', [
                'provider' => $provider,
                'has_code' => $code !== '',
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);

            return null;
        }
    }

    public function buildAuthorizationUrl(string $provider, string $state): ?string
    {
        if ($state === '') {
            return null;
        }

        return match ($provider) {
            'github' => app(GithubOAuthProvider::class)->buildAuthorizationUrl($state),
            'alipay' => app(AlipayOAuthProvider::class)->buildAuthorizationUrl($state),
            default => null,
        };
    }

    public function resolveOrCreateUserForLogin(
        string $provider,
        string $providerUserId,
        string $providerEmail,
        string $providerName
    ): ?User {
        return DB::transaction(function () use ($provider, $providerUserId, $providerEmail, $providerName): ?User {
            $account = UserOAuthAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->lockForUpdate()
                ->first();

            if ($account !== null) {
                return User::query()->find($account->user_id);
            }

            $targetUser = null;
            if ($this->config->canLinkByEmail() && filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
                $targetUser = User::query()
                    ->where('email', $providerEmail)
                    ->lockForUpdate()
                    ->first();
            }

            if ($targetUser === null && ! $this->config->canAutoRegister()) {
                return null;
            }

            if ($targetUser === null) {
                $targetUser = User::query()->create([
                    'name' => $providerName !== '' ? $providerName : $this->generateDefaultName($provider),
                    'email' => $this->resolveRegistrationEmail($provider, $providerUserId, $providerEmail),
                    'password' => Str::password(32),
                ]);
            }

            UserOAuthAccount::query()->updateOrCreate(
                [
                    'user_id' => $targetUser->id,
                    'provider' => $provider,
                ],
                [
                    'provider_user_id' => $providerUserId,
                    'provider_email' => $providerEmail !== '' ? $providerEmail : null,
                    'provider_name' => $providerName !== '' ? $providerName : null,
                    'bound_at' => now(),
                ]
            );

            return $targetUser;
        });
    }

    public function bindProviderToUser(
        User $user,
        string $provider,
        string $providerUserId,
        string $providerEmail,
        string $providerName
    ): bool {
        return DB::transaction(function () use ($user, $provider, $providerUserId, $providerEmail, $providerName): bool {
            $conflict = UserOAuthAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->lockForUpdate()
                ->first();

            if ($conflict !== null && $conflict->user_id !== $user->id) {
                return true;
            }

            UserOAuthAccount::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ],
                [
                    'provider_user_id' => $providerUserId,
                    'provider_email' => $providerEmail !== '' ? $providerEmail : null,
                    'provider_name' => $providerName !== '' ? $providerName : null,
                    'bound_at' => now(),
                ]
            );

            return false;
        });
    }

    private function resolveRegistrationEmail(string $provider, string $providerUserId, string $providerEmail): string
    {
        if (filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
            return $providerEmail;
        }

        return sprintf('%s_%s@oauth.local', $provider, $providerUserId);
    }

    private function generateDefaultName(string $provider): string
    {
        return ucfirst($provider).'用户'.Str::random(6);
    }

    /**
     * @param  array<string, string>  $payload
     */
    public function recordUserAudit(?int $userId, string $action, Request $request, array $payload = []): void
    {
        UserActionLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'route_name' => (string) ($request->route()?->getName() ?? ''),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $payload,
        ]);
    }
}
