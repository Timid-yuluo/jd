<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SystemSettingAuditLog;
use App\Services\Admin\SystemSettingService;
use App\Services\Auth\OAuthDiagnosticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class OAuthDiagnostics extends Command
{
    /**
     * @var string
     */
    protected $signature = 'oauth:diagnose
                            {--hours=24 : 统计时间窗口（小时）}
                            {--json : 以 JSON 输出结果}
                            {--alert-min-failures=5 : 触发告警的最小失败次数}
                            {--alert-failure-rate=80 : 触发告警的失败率阈值（百分比）}
                            {--fail-on-alert : 告警时返回失败退出码}
                            {--auto-mitigate : 告警时自动关闭对应 provider 开关}';

    /**
     * @var string
     */
    protected $description = '诊断 OAuth 配置健康度与近期登录失败情况';

    public function handle(OAuthDiagnosticsService $diagnostics, SystemSettingService $settings): int
    {
        $hours = (int) $this->option('hours');
        $report = $diagnostics->diagnose($hours);
        $alerts = $this->evaluateAlerts($report);

        if ((bool) $this->option('json')) {
            $jsonReport = $report;
            $jsonReport['alerts'] = $alerts;
            $this->line((string) json_encode($jsonReport, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            if ($alerts !== [] && (bool) $this->option('auto-mitigate')) {
                $this->mitigate($alerts, $settings);
            }

            if ($alerts !== [] && (bool) $this->option('fail-on-alert')) {
                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $this->info('OAuth 诊断报告');
        $this->line('时间窗口: 最近 '.$report['hours'].' 小时');
        $this->line('统计起点: '.$report['since']);
        $this->newLine();

        /** @var array<string, array<string, mixed>> $providers */
        $providers = $report['providers'];

        foreach ($providers as $provider => $data) {
            $this->info(strtoupper($provider));
            $this->line('  启用状态: '.($data['enabled'] ? '是' : '否'));
            $this->line('  凭据完整: '.($data['credentials_ok'] ? '是' : '否'));
            $this->line('  成功次数: '.(int) $data['success_count']);
            $this->line('  失败次数: '.(int) $data['failure_count']);
            $this->line('  失败率: '.(float) $data['failure_rate'].'%');

            /** @var array<int, array{action:string,count:int}> $topFailures */
            $topFailures = $data['top_failures'];
            if ($topFailures !== []) {
                $this->line('  高频失败:');
                foreach ($topFailures as $failure) {
                    $this->line('    - '.$failure['action'].' => '.$failure['count']);
                }
            }

            $this->newLine();
        }

        if ($alerts !== []) {
            $this->warn('已触发 OAuth 告警:');
            foreach ($alerts as $alert) {
                $this->warn(sprintf(
                    '  - %s: failures=%d, rate=%.2f%%',
                    $alert['provider'],
                    $alert['failure_count'],
                    $alert['failure_rate']
                ));
            }
            $this->newLine();

            Log::warning('OAuth diagnostics alerts detected', [
                'alerts' => $alerts,
                'hours' => $report['hours'],
            ]);
        }

        if ($alerts !== [] && (bool) $this->option('auto-mitigate')) {
            $this->mitigate($alerts, $settings);
        }

        if ($alerts !== [] && (bool) $this->option('fail-on-alert')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string,mixed>  $report
     * @return array<int, array{provider:string,failure_count:int,failure_rate:float}>
     */
    private function evaluateAlerts(array $report): array
    {
        $minFailures = max(1, (int) $this->option('alert-min-failures'));
        $failureRateThreshold = max(1.0, min(100.0, (float) $this->option('alert-failure-rate')));
        $alerts = [];

        /** @var array<string, array<string,mixed>> $providers */
        $providers = $report['providers'];
        foreach ($providers as $provider => $data) {
            $enabled = (bool) ($data['enabled'] ?? false);
            $credentialsOk = (bool) ($data['credentials_ok'] ?? false);
            $failureCount = (int) ($data['failure_count'] ?? 0);
            $failureRate = (float) ($data['failure_rate'] ?? 0.0);

            if (! $enabled || ! $credentialsOk) {
                continue;
            }

            if ($failureCount >= $minFailures && $failureRate >= $failureRateThreshold) {
                $alerts[] = [
                    'provider' => $provider,
                    'failure_count' => $failureCount,
                    'failure_rate' => $failureRate,
                ];
            }
        }

        return $alerts;
    }

    /**
     * @param  array<int, array{provider:string,failure_count:int,failure_rate:float}>  $alerts
     */
    private function mitigate(array $alerts, SystemSettingService $settings): void
    {
        foreach ($alerts as $alert) {
            $provider = $alert['provider'];
            $key = 'auth_provider_'.$provider.'_enabled';

            $oldValue = $settings->get($key, '0');
            if ((int) $oldValue === 0) {
                continue;
            }

            $settings->set($key, '0');
            SystemSettingAuditLog::query()->create([
                'setting_key' => $key,
                'old_value' => $oldValue,
                'new_value' => '0',
                'changed_by_user_id' => null,
                'ip_address' => null,
                'user_agent' => 'oauth:diagnose --auto-mitigate',
            ]);

            $this->error(sprintf(
                '已自动处置：关闭 %s 登录（failures=%d, rate=%.2f%%）',
                $provider,
                $alert['failure_count'],
                $alert['failure_rate']
            ));
        }
    }
}
