<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\UserActionLog;
use App\Services\Admin\SystemSettingService;
use Carbon\CarbonInterface;

final class OAuthDiagnosticsService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function diagnose(int $hours = 24): array
    {
        $hours = max(1, min($hours, 720));
        $since = now()->subHours($hours);

        return [
            'since' => $since->toDateTimeString(),
            'hours' => $hours,
            'providers' => [
                'github' => $this->diagnoseProvider('github', $since),
                'alipay' => $this->diagnoseProvider('alipay', $since),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diagnoseProvider(string $provider, CarbonInterface $since): array
    {
        $enabled = $provider === 'github'
            ? $this->isGithubEnabled()
            : $this->isAlipayEnabled();

        $credentialDiagnosis = $provider === 'github'
            ? $this->diagnoseGithubCredentials()
            : $this->diagnoseAlipayCredentials();
        $credentialsOk = $credentialDiagnosis['ok'];

        $successPrefix = 'oauth_login_success_'.$provider;
        $failurePrefix = 'oauth_%_'.$provider;

        $successCount = UserActionLog::query()
            ->where('action', $successPrefix)
            ->where('created_at', '>=', $since)
            ->count();

        $failureCount = UserActionLog::query()
            ->where('action', 'like', $failurePrefix)
            ->where('action', '!=', $successPrefix)
            ->where('created_at', '>=', $since)
            ->count();

        $totalCount = $successCount + $failureCount;
        $failureRate = $totalCount > 0 ? round(($failureCount / $totalCount) * 100, 2) : 0.0;

        $topFailures = UserActionLog::query()
            ->selectRaw('action, COUNT(*) as aggregate')
            ->where('action', 'like', $failurePrefix)
            ->where('action', '!=', $successPrefix)
            ->where('created_at', '>=', $since)
            ->groupBy('action')
            ->orderByDesc('aggregate')
            ->limit((int) config('ui.limit.oauth_diag_recent', 5))
            ->get()
            ->map(static fn ($item): array => [
                'action' => (string) $item->action,
                'count' => (int) $item->aggregate,
            ])
            ->values()
            ->all();

        $timeline = $this->buildTimeline($provider, $since);
        $recentFailures = UserActionLog::query()
            ->select(['id', 'action', 'route_name', 'method', 'path', 'payload', 'created_at'])
            ->where('action', 'like', $failurePrefix)
            ->where('action', '!=', $successPrefix)
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->limit((int) config('ui.limit.oauth_diag_list', 20))
            ->get()
            ->map(static function (UserActionLog $log): array {
                return [
                    'id' => (int) $log->id,
                    'action' => (string) $log->action,
                    'route_name' => (string) ($log->route_name ?? ''),
                    'method' => (string) ($log->method ?? ''),
                    'path' => (string) ($log->path ?? ''),
                    'payload' => is_array($log->payload) ? $log->payload : [],
                    'created_at' => $log->created_at?->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        return [
            'enabled' => $enabled,
            'credentials_ok' => $credentialsOk,
            'credentials_reason' => $credentialDiagnosis['reason'],
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failure_rate' => $failureRate,
            'top_failures' => $topFailures,
            'timeline' => $timeline,
            'recent_failures' => $recentFailures,
        ];
    }

    /**
     * @return array<int, array{time:string,success:int,failure:int}>
     */
    private function buildTimeline(string $provider, CarbonInterface $since): array
    {
        $from = $since->copy()->startOfHour();
        $now = now()->startOfHour();
        $rows = [];
        $cursor = $from->copy();

        $index = [];
        while ($cursor->lessThanOrEqualTo($now)) {
            $key = $cursor->format('Y-m-d H:00:00');
            $index[$key] = [
                'time' => $key,
                'success' => 0,
                'failure' => 0,
            ];
            $cursor->addHour();
        }

        $actions = UserActionLog::query()
            ->select(['action', 'created_at'])
            ->where('action', 'like', 'oauth_%_'.$provider)
            ->where('created_at', '>=', $since)
            ->get();

        foreach ($actions as $log) {
            $timeKey = $log->created_at?->copy()->startOfHour()->format('Y-m-d H:00:00');
            if (! is_string($timeKey) || ! isset($index[$timeKey])) {
                continue;
            }

            if ($log->action === 'oauth_login_success_'.$provider) {
                $index[$timeKey]['success']++;

                continue;
            }

            $index[$timeKey]['failure']++;
        }

        foreach ($index as $item) {
            $rows[] = $item;
        }

        return $rows;
    }

    private function isGithubEnabled(): bool
    {
        return (int) $this->settings->get('auth_quick_login_enabled', '0') === 1
            && (int) $this->settings->get('auth_provider_github_enabled', '0') === 1;
    }

    private function isAlipayEnabled(): bool
    {
        return (int) $this->settings->get('auth_quick_login_enabled', '0') === 1
            && (int) $this->settings->get('auth_provider_alipay_enabled', '0') === 1;
    }

    /**
     * @return array{ok:bool,reason:string}
     */
    private function diagnoseGithubCredentials(): array
    {
        $clientId = $this->settings->get('auth_github_client_id');
        $clientSecret = $this->settings->get('auth_github_client_secret');
        $redirectUrl = $this->settings->get('auth_github_redirect_url');

        if (! $this->isFilled($clientId)) {
            return ['ok' => false, 'reason' => '缺少 GitHub OAuth App Client ID'];
        }

        if (! $this->hasValidGithubClientId($clientId)) {
            return ['ok' => false, 'reason' => 'GitHub Client ID 疑似填成了纯数字 App ID'];
        }

        if (! $this->isFilled($clientSecret)) {
            return ['ok' => false, 'reason' => '缺少 GitHub OAuth App Client Secret'];
        }

        if (! $this->isFilled($redirectUrl)) {
            return ['ok' => false, 'reason' => '缺少 GitHub 回调地址'];
        }

        return ['ok' => true, 'reason' => '配置完整'];
    }

    /**
     * @return array{ok:bool,reason:string}
     */
    private function diagnoseAlipayCredentials(): array
    {
        if (! $this->isFilled($this->settings->get('auth_alipay_app_id'))) {
            return ['ok' => false, 'reason' => '缺少支付宝应用 ID'];
        }

        if (! $this->isFilled($this->settings->get('auth_alipay_public_key'))) {
            return ['ok' => false, 'reason' => '缺少支付宝公钥'];
        }

        if (! $this->isFilled($this->settings->get('auth_alipay_private_key'))) {
            return ['ok' => false, 'reason' => '缺少支付宝应用私钥'];
        }

        if (! $this->isFilled($this->settings->get('auth_alipay_redirect_url'))) {
            return ['ok' => false, 'reason' => '缺少支付宝回调地址'];
        }

        return ['ok' => true, 'reason' => '配置完整'];
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
