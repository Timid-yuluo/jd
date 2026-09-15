<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\PageVisit;
use App\Models\User;
use App\Services\VisitTrackingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class VisitorAnalyticsService
{
    public function __construct(
        private readonly VisitTrackingService $trackingService,
    ) {}

    public function getDashboardData(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?: now()->subDays(29)->format('Y-m-d');
        $dateTo = $dateTo ?: now()->format('Y-m-d');

        $days = max(1, \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);
        $prevFrom = \Carbon\Carbon::parse($dateFrom)->subDays($days)->format('Y-m-d');
        $prevTo = \Carbon\Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        return [
            'summary' => $this->getSummary($dateFrom, $dateTo),
            'comparison' => $this->getComparison($dateFrom, $dateTo, $prevFrom, $prevTo),
            'todayStats' => $this->getTodayStats(),
            'onlineCount' => $this->trackingService->getOnlineCount(),
            'dailyTrend' => $this->getDailyTrend($dateFrom, $dateTo),
            'topPages' => $this->getTopPages($dateFrom, $dateTo),
            'deviceStats' => $this->getDeviceStats($dateFrom, $dateTo),
            'brandStats' => $this->getBrandStats($dateFrom, $dateTo),
            'browserStats' => $this->getBrowserStats($dateFrom, $dateTo),
            'osStats' => $this->getOsStats($dateFrom, $dateTo),
            'ispStats' => $this->getIspStats($dateFrom, $dateTo),
            'botStats' => $this->getBotStats($dateFrom, $dateTo),
            'topUsers' => $this->getTopUsers($dateFrom, $dateTo),
            'topIps' => $this->getTopIps($dateFrom, $dateTo),
            'topReferers' => $this->getTopReferers($dateFrom, $dateTo),
            'hourlyDistribution' => $this->getHourlyDistribution($dateFrom, $dateTo),
            'topCities' => $this->getTopCities($dateFrom, $dateTo),
            'topCountries' => $this->getTopCountries($dateFrom, $dateTo),
        ];
    }

    public function getSummary(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:summary:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            $base = PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo);

            $totalPv = (clone $base)->count();
            $totalUv = (clone $base)->distinct('session_id')->count('session_id');
            $avgDuration = (int) ((clone $base)->whereNotNull('duration_ms')->avg('duration_ms') ?: 0);

            $days = max(1, \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);

            return [
                'total_pv' => $totalPv,
                'total_uv' => $totalUv,
                'avg_daily_pv' => (int) round($totalPv / $days),
                'avg_daily_uv' => (int) round($totalUv / $days),
                'avg_duration_sec' => (int) round($avgDuration / 1000),
                'total_events' => PageVisit::query()->events()->excludeBots()->dateRange($dateFrom, $dateTo)->count(),
                'bot_pv' => PageVisit::query()->pageviews()->where('is_bot', true)->dateRange($dateFrom, $dateTo)->count(),
            ];
        });
    }

    public function getComparison(string $dateFrom, string $dateTo, string $prevFrom, string $prevTo): array
    {
        $cacheKey = "visitor:comparison:{$dateFrom}:{$dateTo}:{$prevFrom}:{$prevTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $prevFrom, $prevTo) {
            $curPv = PageVisit::query()->pageviews()->excludeBots()->dateRange($dateFrom, $dateTo)->count();
            $prevPv = PageVisit::query()->pageviews()->excludeBots()->dateRange($prevFrom, $prevTo)->count();

            $curUv = PageVisit::query()->pageviews()->excludeBots()->dateRange($dateFrom, $dateTo)->distinct('session_id')->count('session_id');
            $prevUv = PageVisit::query()->pageviews()->excludeBots()->dateRange($prevFrom, $prevTo)->distinct('session_id')->count('session_id');

            return [
                'pv_change' => $prevPv > 0 ? round(($curPv - $prevPv) / $prevPv * 100, 1) : 0,
                'uv_change' => $prevUv > 0 ? round(($curUv - $prevUv) / $prevUv * 100, 1) : 0,
                'prev_pv' => $prevPv,
                'prev_uv' => $prevUv,
            ];
        });
    }

    public function getTodayStats(): array
    {
        $cacheKey = 'visitor:today';

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.short', 60), function () {
            $today = now()->startOfDay();
            $base = PageVisit::query()->pageviews()->excludeBots()->where('created_at', '>=', $today);

            return [
                'pv' => (clone $base)->count(),
                'uv' => (clone $base)->distinct('session_id')->count('session_id'),
                'events' => PageVisit::query()->events()->excludeBots()->where('created_at', '>=', $today)->count(),
                'avg_duration_sec' => (int) round(((clone $base)->whereNotNull('duration_ms')->avg('duration_ms') ?: 0) / 1000),
            ];
        });
    }

    public function getDailyTrend(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:daily:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            $startDate = \Carbon\Carbon::parse($dateFrom);
            $endDate = \Carbon\Carbon::parse($dateTo);

            $dailyStats = PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->selectRaw('DATE(created_at) as date, count(*) as pv, count(distinct session_id) as uv')
                ->groupByRaw('DATE(created_at)')
                ->get()
                ->keyBy('date');

            $trend = [];
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $dateKey = $cursor->format('Y-m-d');
                $stats = $dailyStats->get($dateKey);

                $trend[] = [
                    'date' => $dateKey,
                    'label' => $cursor->format('m-d'),
                    'pv' => (int) ($stats?->pv ?? 0),
                    'uv' => (int) ($stats?->uv ?? 0),
                ];

                $cursor->addDay();
            }

            return $trend;
        });
    }

    public function getTopPages(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $cacheKey = "visitor:top-pages:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->selectRaw('path, count(*) as pv, count(distinct session_id) as uv')
                ->groupBy('path')
                ->orderByDesc('pv')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getDeviceStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:devices:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('device_type')
                ->selectRaw('device_type, count(*) as cnt')
                ->groupBy('device_type')
                ->orderByDesc('cnt')
                ->get()
                ->toArray();
        });
    }

    public function getBrandStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:brands:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('device_brand')
                ->where('device_brand', '!=', '')
                ->selectRaw('device_brand, count(*) as cnt')
                ->groupBy('device_brand')
                ->orderByDesc('cnt')
                ->limit(15)
                ->get()
                ->toArray();
        });
    }

    public function getBrowserStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:browsers:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('browser')
                ->selectRaw('browser, count(*) as cnt')
                ->groupBy('browser')
                ->orderByDesc('cnt')
                ->get()
                ->toArray();
        });
    }

    public function getOsStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:os:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('os')
                ->selectRaw('os, count(*) as cnt')
                ->groupBy('os')
                ->orderByDesc('cnt')
                ->get()
                ->toArray();
        });
    }

    public function getIspStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:isp:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('isp')
                ->where('isp', '!=', '')
                ->selectRaw('isp, count(*) as cnt')
                ->groupBy('isp')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }

    public function getBotStats(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:bots:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            $total = PageVisit::query()->pageviews()->dateRange($dateFrom, $dateTo)->count();
            $bots = PageVisit::query()->pageviews()->dateRange($dateFrom, $dateTo)->where('is_bot', true)->count();

            $byBot = PageVisit::query()
                ->pageviews()
                ->dateRange($dateFrom, $dateTo)
                ->where('is_bot', true)
                ->whereNotNull('bot_name')
                ->selectRaw('bot_name, count(*) as cnt')
                ->groupBy('bot_name')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get()
                ->toArray();

            return [
                'total' => $total,
                'bots' => $bots,
                'humans' => $total - $bots,
                'bot_rate' => $total > 0 ? round($bots / $total * 100, 1) : 0,
                'by_bot' => $byBot,
            ];
        });
    }

    public function getTopUsers(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $cacheKey = "visitor:top-users:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, count(*) as pv, count(distinct session_id) as uv, avg(duration_ms) as avg_duration')
                ->groupBy('user_id')
                ->orderByDesc('pv')
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    $user = User::find($row->user_id);

                    return [
                        'user_id' => $row->user_id,
                        'name' => $user?->name ?? '未知',
                        'email' => $user?->email ?? '-',
                        'pv' => (int) $row->pv,
                        'uv' => (int) $row->uv,
                        'avg_duration_sec' => (int) round(($row->avg_duration ?: 0) / 1000),
                    ];
                })
                ->toArray();
        });
    }

    public function getTopIps(string $dateFrom, string $dateTo, int $limit = 15): array
    {
        $cacheKey = "visitor:top-ips:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->selectRaw('COALESCE(real_ip, ip_address) as display_ip, count(*) as pv, count(distinct session_id) as uv, count(distinct user_id) as users, MAX(country) as country, MAX(city) as city, MAX(isp) as isp')
                ->groupByRaw('COALESCE(real_ip, ip_address)')
                ->orderByDesc('pv')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getTopReferers(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $cacheKey = "visitor:referers:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('referer')
                ->where('referer', '!=', '')
                ->selectRaw("CASE
                    WHEN referer LIKE '%google%' THEN 'Google'
                    WHEN referer LIKE '%baidu%' THEN 'Baidu'
                    WHEN referer LIKE '%bing%' THEN 'Bing'
                    WHEN referer LIKE '%github%' THEN 'GitHub'
                    WHEN referer LIKE '%weibo%' THEN 'Weibo'
                    WHEN referer LIKE '%zhihu%' THEN 'Zhihu'
                    WHEN referer LIKE '%douyin%' THEN 'Douyin'
                    WHEN referer LIKE '%weixin%' OR referer LIKE '%wechat%' THEN 'WeChat'
                    ELSE SUBSTR(referer, 1, 50)
                END as source, count(*) as cnt")
                ->groupByRaw('source')
                ->orderByDesc('cnt')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getHourlyDistribution(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "visitor:hourly:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo) {
            $hourly = PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->selectRaw('HOUR(created_at) as hour, count(*) as cnt')
                ->groupByRaw('HOUR(created_at)')
                ->pluck('cnt', 'hour')
                ->toArray();

            $result = [];
            for ($h = 0; $h < 24; $h++) {
                $result[] = [
                    'hour' => $h,
                    'label' => sprintf('%02d:00', $h),
                    'cnt' => (int) ($hourly[$h] ?? 0),
                ];
            }

            return $result;
        });
    }

    public function getTopCities(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $cacheKey = "visitor:cities:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->selectRaw('city, count(*) as cnt')
                ->groupBy('city')
                ->orderByDesc('cnt')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getTopCountries(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $cacheKey = "visitor:countries:{$dateFrom}:{$dateTo}";

        return Cache::remember($cacheKey, (int) config('cache_ttl.ttl.standard', 300), function () use ($dateFrom, $dateTo, $limit) {
            return PageVisit::query()
                ->pageviews()
                ->excludeBots()
                ->dateRange($dateFrom, $dateTo)
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->selectRaw('country, count(*) as cnt')
                ->groupBy('country')
                ->orderByDesc('cnt')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    public function getVisitDetails(?string $search = null, ?string $eventType = null, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $deviceType = null)
    {
        $query = PageVisit::query()->with('user')->latest('created_at');

        if ($search) {
            $safeSearch = escapeLike($search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('path', 'like', "%{$safeSearch}%")
                    ->orWhere('ip_address', 'like', "%{$safeSearch}%")
                    ->orWhere('real_ip', 'like', "%{$safeSearch}%")
                    ->orWhere('event_label', 'like', "%{$safeSearch}%")
                    ->orWhere('country', 'like', "%{$safeSearch}%")
                    ->orWhere('city', 'like', "%{$safeSearch}%");
            });
        }

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($deviceType) {
            $query->where('device_type', $deviceType);
        }

        $query->dateRange($dateFrom, $dateTo);

        return $query->paginate((int) config('ui.pagination.admin_table', 20));
    }

    public function exportCsv(?string $dateFrom = null, ?string $dateTo = null): string
    {
        $query = PageVisit::query()
            ->pageviews()
            ->dateRange($dateFrom, $dateTo)
            ->latest('created_at')
            ->limit(10000);

        $csv = "ID,用户ID,会话ID,路径,设备类型,品牌,浏览器,浏览器版本,操作系统,OS版本,真实IP,国家,城市,ISP,停留(ms),来源,是否爬虫,时间\n";

        foreach ($query->cursor() as $v) {
            $csv .= implode(',', [
                $v->id,
                $v->user_id ?? '',
                $v->session_id,
                '"'.str_replace('"', '""', $v->path).'"',
                $v->device_type ?? '',
                $v->device_brand ?? '',
                $v->browser ?? '',
                $v->browser_version ?? '',
                $v->os ?? '',
                $v->os_version ?? '',
                $v->real_ip ?? $v->ip_address,
                $v->country ?? '',
                $v->city ?? '',
                $v->isp ?? '',
                $v->duration_ms ?? '',
                '"'.str_replace('"', '""', $v->referer ?? '').'"',
                $v->is_bot ? 'Y' : 'N',
                $v->created_at?->format('Y-m-d H:i:s') ?? '',
            ])."\n";
        }

        return $csv;
    }
}
