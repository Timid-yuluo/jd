<?php

declare(strict_types=1);

namespace App\Services\Resume\Template;

use App\Models\ResumeTemplate;
use App\Models\UserActionLog;
use Illuminate\Support\Collection;

final class TemplateAnalyticsService
{
    /**
     * @return array{overview: array<string, mixed>, rows: Collection<int, array<string, mixed>>}
     */
    public function computeAnalytics(int $userId, int $days = 30): array
    {
        $days = max(7, min($days, 90));
        $from = now()->subDays($days);

        $logs = UserActionLog::query()
            ->where('user_id', $userId)
            ->whereIn('action', ['resume_template_detail_view', 'resume_template_compare_view', 'resume_template_apply'])
            ->where('created_at', '>=', $from)
            ->orderByDesc('id')
            ->limit((int) config('ui.limit.template_content', 5000))
            ->get(['action', 'payload', 'created_at']);

        $overview = [
            'detail_views' => 0,
            'compare_views' => 0,
            'apply_count' => 0,
            'apply_new_count' => 0,
            'apply_existing_count' => 0,
        ];

        $templateStats = [];
        foreach ($logs as $log) {
            $payload = is_array($log->payload) ? $log->payload : [];
            $templateId = (int) ($payload['template_id'] ?? 0);
            if ($templateId <= 0) {
                continue;
            }
            if (! isset($templateStats[$templateId])) {
                $templateStats[$templateId] = [
                    'template_id' => $templateId,
                    'detail_views' => 0,
                    'compare_views' => 0,
                    'apply_count' => 0,
                    'apply_new_count' => 0,
                    'apply_existing_count' => 0,
                ];
            }

            if ($log->action === 'resume_template_detail_view') {
                $overview['detail_views']++;
                $templateStats[$templateId]['detail_views']++;
            } elseif ($log->action === 'resume_template_compare_view') {
                $overview['compare_views']++;
                $templateStats[$templateId]['compare_views']++;
            } elseif ($log->action === 'resume_template_apply') {
                $overview['apply_count']++;
                $templateStats[$templateId]['apply_count']++;
                $mode = (string) ($payload['apply_mode'] ?? '');
                if ($mode === 'existing') {
                    $overview['apply_existing_count']++;
                    $templateStats[$templateId]['apply_existing_count']++;
                } else {
                    $overview['apply_new_count']++;
                    $templateStats[$templateId]['apply_new_count']++;
                }
            }
        }

        $templateIds = array_keys($templateStats);
        $templateNames = ResumeTemplate::query()
            ->whereIn('id', $templateIds)
            ->pluck('name', 'id')
            ->all();

        $rows = collect(array_values($templateStats))
            ->map(static function (array $row) use ($templateNames): array {
                $detailViews = (int) $row['detail_views'];
                $compareViews = (int) $row['compare_views'];
                $applyCount = (int) $row['apply_count'];
                $row['template_name'] = (string) ($templateNames[$row['template_id']] ?? ('模板 #'.$row['template_id']));
                $row['apply_rate'] = $detailViews > 0 ? round(($applyCount / $detailViews) * 100, 1) : 0.0;
                $row['compare_rate'] = $detailViews > 0 ? round(($compareViews / $detailViews) * 100, 1) : 0.0;

                return $row;
            })
            ->sortByDesc('apply_count')
            ->take((int) config('ui.limit.template_list', 20))
            ->values();

        $overview['apply_rate'] = $overview['detail_views'] > 0
            ? round(($overview['apply_count'] / $overview['detail_views']) * 100, 1)
            : 0.0;

        return [
            'overview' => $overview,
            'rows' => $rows,
        ];
    }
}
