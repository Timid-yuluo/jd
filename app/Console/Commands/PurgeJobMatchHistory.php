<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobMatchAnalysis;
use App\Models\JobMatchAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class PurgeJobMatchHistory extends Command
{
    protected $signature = 'job-match:purge-history
        {--days= : Override job match history retention days}
        {--audit-days= : Override job match audit retention days}
        {--dry-run : Preview affected rows without deleting}';

    protected $description = '清理超出保留期的岗位分析历史记录与审计日志';

    public function handle(): int
    {
        $historyRetentionDays = max(1, (int) ($this->option('days') ?: config('job-matching.history_retention_days', 180)));
        $auditRetentionDays = max(1, (int) ($this->option('audit-days') ?: config('job-matching.audit_retention_days', 365)));
        $dryRun = (bool) $this->option('dry-run');

        $historyCutoff = now()->subDays($historyRetentionDays);
        $auditCutoff = now()->subDays($auditRetentionDays);

        $historyQuery = JobMatchAnalysis::query()->where('created_at', '<', $historyCutoff);
        $auditQuery = JobMatchAuditLog::query()
            ->whereNull('job_match_analysis_id')
            ->where('created_at', '<', $auditCutoff);

        $historyCount = (clone $historyQuery)->count();
        $auditCount = (clone $auditQuery)->count();

        if ($dryRun) {
            $this->table(
                ['Scope', 'Retention Days', 'Cutoff', 'Matched Rows'],
                [
                    ['history', (string) $historyRetentionDays, $historyCutoff->toDateTimeString(), (string) $historyCount],
                    ['audit', (string) $auditRetentionDays, $auditCutoff->toDateTimeString(), (string) $auditCount],
                ]
            );

            return self::SUCCESS;
        }

        $deletedHistories = $historyQuery->delete();
        $deletedAudits = $auditQuery->delete();

        $this->info("已清理岗位分析历史 {$deletedHistories} 条，独立审计日志 {$deletedAudits} 条。");

        Log::info('Job match history purged', [
            'history_retention_days' => $historyRetentionDays,
            'audit_retention_days' => $auditRetentionDays,
            'deleted_histories' => $deletedHistories,
            'deleted_audits' => $deletedAudits,
        ]);

        return self::SUCCESS;
    }
}
