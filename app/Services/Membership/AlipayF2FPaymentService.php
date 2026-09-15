<?php

declare(strict_types=1);

namespace App\Services\Membership;

use App\Models\CreditPackOrder;
use App\Models\Order;
use App\Services\Admin\SystemSettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AlipayF2FPaymentService
{
    private const GATEWAY_URL = 'https://openapi.alipay.com/gateway.do';

    public function __construct(
        private readonly SystemSettingService $systemSettingService,
    ) {}

    /**
     * @return array{qr_code:string,out_trade_no:string}
     */
    public function precreateSubscription(Order $order): array
    {
        return $this->precreate(
            outTradeNo: (string) $order->order_no,
            amountFen: (int) $order->amount,
            subject: '会员订阅 - '.($order->plan->name ?? '套餐订单')
        );
    }

    /**
     * @return array{qr_code:string,out_trade_no:string}
     */
    public function precreateCreditPack(CreditPackOrder $order): array
    {
        return $this->precreate(
            outTradeNo: (string) $order->order_no,
            amountFen: (int) $order->amount,
            subject: '次卡购买 - '.($order->creditPack->name ?? '次卡订单')
        );
    }

    public function isEnabled(): bool
    {
        $settings = $this->systemSettingService->all();

        return (int) ($settings['payment_alipay_enabled'] ?? '0') === 1
            && trim((string) ($settings['payment_alipay_app_id'] ?? '')) !== ''
            && trim((string) ($settings['payment_alipay_private_key'] ?? '')) !== ''
            && trim((string) ($settings['payment_alipay_public_key'] ?? '')) !== '';
    }

    public function verifySign(array $payload): bool
    {
        $sign = trim((string) ($payload['sign'] ?? ''));
        if ($sign === '') {
            return false;
        }

        unset($payload['sign'], $payload['sign_type']);
        $content = $this->buildSignContent($payload);
        $publicKey = $this->normalizePublicKey((string) ($this->systemSettingService->get('payment_alipay_public_key', '') ?? ''));

        if ($publicKey === null) {
            return false;
        }

        $verifyResult = openssl_verify(
            $content,
            base64_decode($sign, true) ?: '',
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        return $verifyResult === 1;
    }

    private function formatFenToYuan(int $amountFen): string
    {
        return number_format($amountFen / 100, 2, '.', '');
    }

    private function resolveNotifyUrl(): string
    {
        $configured = trim((string) ($this->systemSettingService->get('payment_alipay_notify_url', '') ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        return url('/alipay-pay/notify');
    }

    private function resolveGatewayUrl(): string
    {
        $raw = trim((string) ($this->systemSettingService->get('payment_alipay_gateway_url', '') ?? ''));
        if ($raw !== '' && Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        return self::GATEWAY_URL;
    }

    /**
     * @return array{qr_code:string,out_trade_no:string}
     */
    private function precreate(string $outTradeNo, int $amountFen, string $subject): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('支付宝支付未启用或配置不完整');
        }

        $bizContent = [
            'out_trade_no' => $outTradeNo,
            'total_amount' => $this->formatFenToYuan($amountFen),
            'subject' => mb_substr($subject, 0, 128),
            'timeout_express' => '30m',
            'product_code' => 'FACE_TO_FACE_PAYMENT',
        ];

        $params = [
            'app_id' => (string) ($this->systemSettingService->get('payment_alipay_app_id', '') ?? ''),
            'method' => 'alipay.trade.precreate',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $this->resolveNotifyUrl(),
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $params['sign'] = $this->sign($params);

        $response = Http::asForm()
            ->timeout(12)
            ->post($this->resolveGatewayUrl(), $params);

        if (! $response->ok()) {
            throw new RuntimeException('支付宝网关请求失败：HTTP '.$response->status());
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('支付宝网关返回异常');
        }

        $result = $body['alipay_trade_precreate_response'] ?? null;
        if (! is_array($result)) {
            throw new RuntimeException('支付宝返回结构异常');
        }

        if (($result['code'] ?? '') !== '10000' || empty($result['qr_code'])) {
            $message = (string) ($result['sub_msg'] ?? $result['msg'] ?? '支付宝预下单失败');
            throw new RuntimeException($message);
        }

        return [
            'qr_code' => (string) $result['qr_code'],
            'out_trade_no' => $outTradeNo,
        ];
    }

    private function sign(array $params): string
    {
        $privateKey = $this->normalizePrivateKey((string) ($this->systemSettingService->get('payment_alipay_private_key', '') ?? ''));
        if ($privateKey === null) {
            throw new RuntimeException('支付宝私钥格式错误');
        }

        $content = $this->buildSignContent($params);
        $signature = '';
        $ok = openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('支付宝签名失败');
        }

        return base64_encode($signature);
    }

    private function buildSignContent(array $params): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }
            $pairs[] = $key.'='.(string) $value;
        }

        return implode('&', $pairs);
    }

    private function normalizePrivateKey(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, 'BEGIN')) {
            return $raw;
        }

        return "-----BEGIN PRIVATE KEY-----\n".
            chunk_split(str_replace(["\r", "\n", ' '], '', $raw), 64, "\n").
            '-----END PRIVATE KEY-----';
    }

    private function normalizePublicKey(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, 'BEGIN')) {
            return $raw;
        }

        return "-----BEGIN PUBLIC KEY-----\n".
            chunk_split(str_replace(["\r", "\n", ' '], '', $raw), 64, "\n").
            '-----END PUBLIC KEY-----';
    }
}
