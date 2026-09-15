<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use App\Services\Admin\SystemSettingService;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;
use Throwable;

final class AlipayOAuthProvider
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly HttpFactory $http,
    ) {}

    public function buildAuthorizationUrl(string $state): string
    {
        $appId = trim($this->settings->get('auth_alipay_app_id', ''));
        $redirectUri = trim($this->settings->get('auth_alipay_redirect_url', ''));

        if ($appId === '' || $redirectUri === '') {
            throw new RuntimeException('支付宝 OAuth 配置不完整。');
        }

        $query = http_build_query([
            'app_id' => $appId,
            'scope' => 'auth_user',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?'.$query;
    }

    /**
     * @return array{provider_user_id:string,provider_email:string,provider_name:string}
     */
    public function fetchUserIdentityByAuthCode(string $authCode): array
    {
        $tokenResponse = $this->requestGateway(array_merge(
            $this->baseParams('alipay.system.oauth.token'),
            [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
            ]
        ));

        $oauthPayload = (array) ($tokenResponse['alipay_system_oauth_token_response'] ?? []);
        $accessToken = trim((string) ($oauthPayload['access_token'] ?? ''));
        $userId = trim((string) ($oauthPayload['user_id'] ?? ''));

        if ($accessToken === '') {
            throw new RuntimeException('支付宝 access_token 获取失败。');
        }

        $userInfoResponse = $this->requestGateway(array_merge(
            $this->baseParams('alipay.user.info.share'),
            [
                'auth_token' => $accessToken,
            ]
        ));

        $userInfo = (array) ($userInfoResponse['alipay_user_info_share_response'] ?? []);
        $providerUserId = trim((string) ($userInfo['user_id'] ?? $userId));
        $providerName = trim((string) ($userInfo['nick_name'] ?? ''));
        $providerEmail = trim((string) ($userInfo['email'] ?? ''));

        if ($providerUserId === '') {
            throw new RuntimeException('支付宝用户标识缺失。');
        }

        return [
            'provider_user_id' => $providerUserId,
            'provider_email' => $providerEmail,
            'provider_name' => $providerName,
        ];
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    private function requestGateway(array $params): array
    {
        $signed = $this->signParams($params);

        $response = $this->http
            ->acceptJson()
            ->get('https://openapi.alipay.com/gateway.do', $signed);

        if (! $response->successful()) {
            throw new RuntimeException('支付宝网关请求失败。');
        }

        $rawBody = (string) $response->body();
        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('支付宝网关返回格式异常。');
        }

        $responseNode = $this->resolveResponseNodeKey($payload);
        if ($responseNode !== null) {
            $this->guardGatewaySuccess($payload, $responseNode);
            $this->verifyResponseSignatureIfNeeded($payload, $rawBody, $responseNode);
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, string>
     */
    private function signParams(array $params): array
    {
        $privateKey = $this->formatPrivateKey(trim($this->settings->get('auth_alipay_private_key', '')));
        if ($privateKey === '') {
            throw new RuntimeException('支付宝私钥未配置。');
        }

        ksort($params);
        $content = $this->buildSignContent($params);
        $signature = '';
        $result = openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if ($result !== true) {
            throw new RuntimeException('支付宝签名失败。');
        }

        $params['sign'] = base64_encode($signature);

        return $params;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function buildSignContent(array $params): string
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === '' || str_starts_with($value, '@')) {
                continue;
            }
            $pairs[] = $key.'='.$value;
        }

        return implode('&', $pairs);
    }

    /**
     * @return array<string, string>
     */
    private function baseParams(string $method): array
    {
        $appId = trim($this->settings->get('auth_alipay_app_id', ''));
        if ($appId === '') {
            throw new RuntimeException('支付宝 app_id 未配置。');
        }

        return [
            'app_id' => $appId,
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'version' => '1.0',
        ];
    }

    private function formatPrivateKey(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, 'BEGIN')) {
            return $raw;
        }

        $normalized = chunk_split(str_replace(["\r", "\n", ' '], '', $raw), 64, "\n");

        return "-----BEGIN PRIVATE KEY-----\n{$normalized}-----END PRIVATE KEY-----";
    }

    private function formatPublicKey(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, 'BEGIN')) {
            return $raw;
        }

        $normalized = chunk_split(str_replace(["\r", "\n", ' '], '', $raw), 64, "\n");

        return "-----BEGIN PUBLIC KEY-----\n{$normalized}-----END PUBLIC KEY-----";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveResponseNodeKey(array $payload): ?string
    {
        foreach (array_keys($payload) as $key) {
            if (is_string($key) && str_ends_with($key, '_response')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function guardGatewaySuccess(array $payload, string $responseNode): void
    {
        $node = $payload[$responseNode] ?? null;
        if (! is_array($node)) {
            throw new RuntimeException('支付宝返回数据结构异常。');
        }

        $code = trim((string) ($node['code'] ?? ''));
        if ($code === '' || $code === '10000') {
            return;
        }

        $subCode = trim((string) ($node['sub_code'] ?? ''));
        $message = trim((string) ($node['sub_msg'] ?? ($node['msg'] ?? '支付宝接口调用失败。')));
        if ($message === '') {
            $message = '支付宝接口调用失败。';
        }

        $fullCode = $subCode !== '' ? ($code.':'.$subCode) : $code;
        throw new RuntimeException(sprintf('支付宝接口调用失败[%s] %s', $fullCode, $message));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function verifyResponseSignatureIfNeeded(array $payload, string $rawBody, string $responseNode): void
    {
        if (! $this->shouldVerifyResponseSignature()) {
            return;
        }

        $sign = (string) ($payload['sign'] ?? '');
        if ($sign === '') {
            throw new RuntimeException('支付宝返回缺少签名。');
        }

        $publicKey = $this->formatPublicKey(trim($this->settings->get('auth_alipay_public_key', '')));
        if ($publicKey === '') {
            throw new RuntimeException('支付宝公钥未配置，无法验签。');
        }

        $signContent = $this->extractSignContent($rawBody, $responseNode);
        if ($signContent === null) {
            $node = $payload[$responseNode] ?? [];
            if (! is_array($node)) {
                throw new RuntimeException('支付宝返回数据结构异常。');
            }

            try {
                $encoded = json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (Throwable) {
                $encoded = false;
            }

            if ($encoded === false) {
                throw new RuntimeException('支付宝验签内容构建失败。');
            }
            $signContent = $encoded;
        }

        $decodedSign = base64_decode($sign, true);
        if ($decodedSign === false) {
            throw new RuntimeException('支付宝签名格式无效。');
        }

        $verified = openssl_verify($signContent, $decodedSign, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('支付宝响应验签失败。');
        }
    }

    private function shouldVerifyResponseSignature(): bool
    {
        return (int) $this->settings->get('auth_alipay_verify_sign_enabled', '1') === 1;
    }

    private function extractSignContent(string $rawBody, string $responseNode): ?string
    {
        $pattern = sprintf('/"%s"\s*:\s*(\{.*\})\s*,\s*"sign"\s*:/Us', preg_quote($responseNode, '/'));
        if (preg_match($pattern, $rawBody, $matches) === 1) {
            return (string) ($matches[1] ?? '');
        }

        return null;
    }
}
