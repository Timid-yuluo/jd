<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Models\ResumeOptimizeSession;

final class ResumeOptimizeGainService
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function summarizeByStrategy(Resume $resume, int $userId, int $limit = 20): array
    {
        $sessions = ResumeOptimizeSession::query()
            ->with('version')
            ->where('resume_id', $resume->id)
            ->where('user_id', $userId)
            ->whereIn('status', [ResumeOptimizeSession::STATUS_SUCCEEDED, ResumeOptimizeSession::STATUS_APPLIED])
            ->orderByDesc('id')
            ->limit((int) config('ui.limit.optimize_gain', 200))
            ->get();

        $bucket = [];
        foreach ($sessions as $session) {
            $config = is_array($session->config) ? $session->config : [];
            $template = trim((string) ($config['prompt_strategy_template'] ?? 'general'));
            $mode = trim((string) ($config['optimize_mode'] ?? 'balanced'));
            $targetJob = trim((string) ($config['target_job'] ?? ''));
            $groupKey = $template.'|'.$mode.'|'.$targetJob;
            $delta = (int) ($session->version?->score_delta['delta'] ?? 0);

            if (! isset($bucket[$groupKey])) {
                $bucket[$groupKey] = [
                    'template' => $template !== '' ? $template : 'general',
                    'mode' => $mode !== '' ? $mode : 'balanced',
                    'target_job' => $targetJob,
                    'total' => 0,
                    'sum_delta' => 0,
                    'avg_delta' => 0,
                    'latest_session_id' => (string) $session->uuid,
                    'latest_at' => optional($session->created_at)?->toDateTimeString(),
                ];
            }
            $bucket[$groupKey]['total']++;
            $bucket[$groupKey]['sum_delta'] += $delta;
            $bucket[$groupKey]['avg_delta'] = (int) round($bucket[$groupKey]['sum_delta'] / max(1, (int) $bucket[$groupKey]['total']));
        }

        $rows = array_values($bucket);
        usort($rows, static function (array $a, array $b): int {
            $cmp = (int) ($b['avg_delta'] ?? 0) <=> (int) ($a['avg_delta'] ?? 0);
            if ($cmp !== 0) {
                return $cmp;
            }

            return (int) ($b['total'] ?? 0) <=> (int) ($a['total'] ?? 0);
        });

        return array_slice($rows, 0, max(1, $limit));
    }
}
