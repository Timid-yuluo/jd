<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use App\Services\Admin\SystemSettingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RuntimeException;

final class GithubOAuthProvider
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly HttpFactory $http,
    ) {}

    public function buildAuthorizationUrl(string $state): string
    {
        $clientId = trim($this->settings->get('auth_github_client_id', ''));
        $redirectUri = trim($this->settings->get('auth_github_redirect_url', ''));

        if (! $this->isValidClientId($clientId) || $redirectUri === '') {
            throw new RuntimeException('GitHub OAuth 配置不完整。');
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user user:email',
            'state' => $state,
        ]);

        return 'https://github.com/login/oauth/authorize?'.$query;
    }

    /**
     * @return array{provider_user_id:string,provider_email:string,provider_name:string}
     */
    public function fetchUserIdentityByCode(string $code): array
    {
        $clientId = trim($this->settings->get('auth_github_client_id', ''));
        $clientSecret = trim($this->settings->get('auth_github_client_secret', ''));
        $redirectUri = trim($this->settings->get('auth_github_redirect_url', ''));

        if (! $this->isValidClientId($clientId) || $clientSecret === '' || $redirectUri === '') {
            throw new RuntimeException('GitHub OAuth 配置不完整。');
        }

        $tokenResponse = $this->githubRequest()
            ->asForm()
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('GitHub token 交换失败。');
        }

        $accessToken = $this->extractAccessToken($tokenResponse);
        if ($accessToken === '') {
            throw new RuntimeException('GitHub token 为空。');
        }

        $profileResponse = $this->githubRequest()
            ->withToken($accessToken)
            ->get('https://api.github.com/user');

        if (! $profileResponse->successful()) {
            throw new RuntimeException('GitHub 用户信息获取失败。');
        }

        $providerUserId = (string) $profileResponse->json('id', '');
        $providerName = trim((string) $profileResponse->json('name', ''));
        $providerEmail = trim((string) $profileResponse->json('email', ''));

        if ($providerEmail === '') {
            $providerEmail = $this->fetchPrimaryEmail($accessToken);
        }

        if ($providerUserId === '') {
            throw new RuntimeException('GitHub 用户标识缺失。');
        }

        return [
            'provider_user_id' => $providerUserId,
            'provider_email' => $providerEmail,
            'provider_name' => $providerName,
        ];
    }

    private function fetchPrimaryEmail(string $accessToken): string
    {
        $emailResponse = $this->githubRequest()
            ->withToken($accessToken)
            ->get('https://api.github.com/user/emails');

        if (! $emailResponse->successful()) {
            return '';
        }

        $emails = $emailResponse->json();
        if (! is_array($emails)) {
            return '';
        }

        foreach ($emails as $email) {
            if (! is_array($email)) {
                continue;
            }

            if (($email['primary'] ?? false) === true && ($email['verified'] ?? false) === true) {
                return trim((string) ($email['email'] ?? ''));
            }
        }

        return '';
    }

    private function extractAccessToken(Response $response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            $token = trim((string) ($json['access_token'] ?? ''));
            if ($token !== '') {
                return $token;
            }

            $error = trim((string) ($json['error_description'] ?? $json['error'] ?? ''));
            if ($error !== '') {
                throw new RuntimeException('GitHub token 交换失败：'.$error);
            }
        }

        $body = trim($response->body());
        if ($body === '') {
            return '';
        }

        parse_str($body, $parsed);
        if (is_array($parsed)) {
            $token = trim((string) ($parsed['access_token'] ?? ''));
            if ($token !== '') {
                return $token;
            }

            $error = trim((string) ($parsed['error_description'] ?? $parsed['error'] ?? ''));
            if ($error !== '') {
                throw new RuntimeException('GitHub token 交换失败：'.$error);
            }
        }

        return '';
    }

    private function isValidClientId(string $clientId): bool
    {
        return $clientId !== '' && preg_match('/^\d+$/', $clientId) !== 1;
    }

    private function githubRequest(): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => config('app.name', 'Laravel'),
            ])
            ->connectTimeout(15)
            ->timeout(30)
            ->retry(2, 800, function (\Throwable $exception): bool {
                return $exception instanceof ConnectionException;
            }, throw: false);
    }
}
