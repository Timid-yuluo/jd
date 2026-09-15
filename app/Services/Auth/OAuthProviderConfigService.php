<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Admin\SystemSettingService;

final class OAuthProviderConfigService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function isSupportedProvider(string $provider): bool
    {
        return in_array($provider, ['github', 'alipay'], true);
    }

    public function isQuickLoginProviderEnabled(string $provider): bool
    {
        if (! $this->isSupportedProvider($provider)) {
            return false;
        }

        $quickLoginEnabled = (int) $this->settings->get('auth_quick_login_enabled', '0') === 1;
        $providerEnabled = (int) $this->settings->get('auth_provider_'.$provider.'_enabled', '0') === 1;

        return $quickLoginEnabled && $providerEnabled;
    }

    public function isBindingProviderEnabled(string $provider): bool
    {
        if (! $this->isSupportedProvider($provider)) {
            return false;
        }

        $bindingEnabled = (int) $this->settings->get('auth_account_binding_enabled', '0') === 1;
        $providerEnabled = (int) $this->settings->get('auth_provider_'.$provider.'_enabled', '0') === 1;

        return $bindingEnabled && $providerEnabled;
    }

    public function hasProviderCredentials(string $provider): bool
    {
        return match ($provider) {
            'github' => $this->hasValidGithubClientId($this->settings->get('auth_github_client_id'))
                && $this->isFilled($this->settings->get('auth_github_client_secret'))
                && $this->isFilled($this->settings->get('auth_github_redirect_url')),
            'alipay' => $this->isFilled($this->settings->get('auth_alipay_app_id'))
                && $this->isFilled($this->settings->get('auth_alipay_public_key'))
                && $this->isFilled($this->settings->get('auth_alipay_private_key'))
                && $this->isFilled($this->settings->get('auth_alipay_redirect_url')),
            default => false,
        };
    }

    public function isUnbindAllowed(): bool
    {
        return (int) $this->settings->get('auth_binding_allow_unbind', '0') === 1;
    }

    public function requiresPasswordConfirmOnUnbind(): bool
    {
        return (int) $this->settings->get('auth_binding_require_password_confirm', '0') === 1;
    }

    public function canAutoRegister(): bool
    {
        return (int) $this->settings->get('auth_quick_login_auto_register', '0') === 1;
    }

    public function canLinkByEmail(): bool
    {
        return (int) $this->settings->get('auth_quick_login_link_by_email', '1') === 1;
    }

    private function isFilled(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function hasValidGithubClientId(?string $value): bool
    {
        if (! $this->isFilled($value)) {
            return false;
        }

        return preg_match('/^\d+$/', trim((string) $value)) !== 1;
    }
}
