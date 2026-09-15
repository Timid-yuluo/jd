@extends('layouts.admin')

@section('title', '访问统计')
@section('page-pretitle', '数据分析')
@section('page-title', '访客行为统计')

@section('page-actions')
<div class="d-flex gap-2 align-items-center flex-wrap">
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route('admin.visitor-analytics.index', ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" class="btn btn-outline-secondary {{ $dateFrom === now()->format('Y-m-d') ? 'active' : '' }}">今日</a>
        <a href="{{ route('admin.visitor-analytics.index', ['date_from' => now()->subDays(6)->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">近7天</a>
        <a href="{{ route('admin.visitor-analytics.index', ['date_from' => now()->subDays(29)->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" class="btn btn-outline-secondary">近30天</a>
        <a href="{{ route('admin.visitor-analytics.index') }}" class="btn btn-outline-secondary">默认</a>
    </div>
    <form method="GET" action="{{ route('admin.visitor-analytics.index') }}" class="d-flex gap-2">
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" style="width:140px">
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}" style="width:140px">
        <button type="submit" class="btn btn-sm btn-primary">查询</button>
    </form>
    <a href="{{ route('admin.visitor-analytics.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-success">
        <i class="ti ti-download me-1"></i>导出CSV
    </a>
    <a href="{{ route('admin.visitor-analytics.detail') }}" class="btn btn-sm btn-outline-primary">访问明细</a>
</div>
@endsection

@section('content')
@php
    $trendLabels = array_column($dailyTrend, 'label');
    $trendPv = array_column($dailyTrend, 'pv');
    $trendUv = array_column($dailyTrend, 'uv');
    $hourlyLabels = array_column($hourlyDistribution, 'label');
    $hourlyData = array_column($hourlyDistribution, 'cnt');

    function changeBadge($pct) {
        if ($pct > 0) return '<span class="text-green">+' . $pct . '%</span>';
        if ($pct < 0) return '<span class="text-red">' . $pct . '%</span>';
        return '<span class="text-secondary">0%</span>';
    }
@endphp

<div class="row row-cards">
    {{-- 顶部统计卡片（已排除爬虫） --}}
    <div class="col-12">
        <div class="row row-cards">
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="ti ti-eye"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_pv']) }}</div>
                                <div class="text-secondary">真实 PV</div>
                                @if($comparison['prev_pv'] > 0)
                                    <div class="small">{!! changeBadge($comparison['pv_change']) !!}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-azure text-white avatar"><i class="ti ti-users"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_uv']) }}</div>
                                <div class="text-secondary">真实 UV</div>
                                @if($comparison['prev_uv'] > 0)
                                    <div class="small">{!! changeBadge($comparison['uv_change']) !!}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar"><i class="ti ti-calendar"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($todayStats['pv']) }}</div>
                                <div class="text-secondary">今日 PV</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-lime text-white avatar"><i class="ti ti-user"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($todayStats['uv']) }}</div>
                                <div class="text-secondary">今日 UV</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-orange text-white avatar"><i class="ti ti-clock"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['avg_duration_sec'] }}s</div>
                                <div class="text-secondary">平均停留</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-purple text-white avatar"><i class="ti ti-radio"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($onlineCount) }}</div>
                                <div class="text-secondary">实时在线</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 爬虫占比指示条 --}}
    @if($botStats['bots'] > 0)
    <div class="col-12">
        <div class="alert alert-info py-2 px-3 d-flex align-items-center gap-2 mb-0">
            <i class="ti ti-robot"></i>
            <span class="small">
                爬虫访问 <strong>{{ number_format($botStats['bots']) }}</strong> 次，占比 <strong>{{ $botStats['bot_rate'] }}%</strong>
                （真实 PV {{ number_format($summary['total_pv']) }} 已排除爬虫）
            </span>
        </div>
    </div>
    @endif

    {{-- PV/UV 趋势图 --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">PV / UV 趋势</h3>
                <div class="card-subtitle text-secondary">{{ $dateFrom }} ~ {{ $dateTo }} ({{ count($dailyTrend) }}天) · 已排除爬虫</div>
            </div>
            <div class="card-body">
                <div id="trend-chart" style="height: 280px;"></div>
            </div>
        </div>
    </div>

    {{-- 访客时段分布 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">24 小时分布</h3>
            </div>
            <div class="card-body">
                <div id="hourly-chart" style="height: 280px;"></div>
            </div>
        </div>
    </div>

    {{-- 热门页面 TOP 10 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">热门页面 TOP 10</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>页面路径</th>
                            <th class="text-end">PV</th>
                            <th class="text-end">UV</th>
                            <th class="text-end">占比</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topPages as $i => $page)
                        <tr>
                            <td><span class="text-secondary">{{ $i + 1 }}</span></td>
                            <td><code class="text-sm">{{ Str::limit($page['path'], 60) }}</code></td>
                            <td class="text-end">{{ number_format($page['pv']) }}</td>
                            <td class="text-end">{{ number_format($page['uv']) }}</td>
                            <td class="text-end">{{ $summary['total_pv'] > 0 ? round($page['pv'] / $summary['total_pv'] * 100, 1) : 0 }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 活跃用户 TOP 10 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">活跃用户 TOP 10</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>用户</th>
                            <th class="text-end">PV</th>
                            <th class="text-end">会话</th>
                            <th class="text-end">平均停留</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topUsers as $i => $u)
                        <tr>
                            <td><span class="text-secondary">{{ $i + 1 }}</span></td>
                            <td>
                                <a href="{{ route('admin.users.show', $u['user_id']) }}">{{ $u['name'] }}</a>
                                <div class="text-secondary small">{{ $u['email'] }}</div>
                            </td>
                            <td class="text-end">{{ number_format($u['pv']) }}</td>
                            <td class="text-end">{{ number_format($u['uv']) }}</td>
                            <td class="text-end">{{ $u['avg_duration_sec'] }}s</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 设备类型分布 --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">设备类型</h3>
            </div>
            <div class="card-body">
                <div id="device-chart" style="height: 220px;"></div>
            </div>
        </div>
    </div>

    {{-- 设备品牌 TOP --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">设备品牌</h3>
            </div>
            <div class="table-responsive" style="max-height: 270px; overflow-y: auto;">
                <table class="table card-table table-vcenter text-nowrap">
                    <tbody>
                        @forelse($brandStats as $i => $brand)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <div class="progress progress-xs">
                                            <div class="progress-bar bg-primary" style="width: {{ $brandStats[0]['cnt'] > 0 ? round($brand['cnt'] / $brandStats[0]['cnt'] * 100) : 0 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="ms-2 text-end" style="min-width: 50px;">
                                        <span class="badge bg-primary-lt">{{ $brand['cnt'] }}</span>
                                    </div>
                                    <div class="ms-2" style="min-width: 70px;">{{ $brand['device_brand'] }}</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 浏览器分布 --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">浏览器</h3>
            </div>
            <div class="card-body">
                <div id="browser-chart" style="height: 220px;"></div>
            </div>
        </div>
    </div>

    {{-- 操作系统分布 --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">操作系统</h3>
            </div>
            <div class="card-body">
                <div id="os-chart" style="height: 220px;"></div>
            </div>
        </div>
    </div>

    {{-- IP 排行榜 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">IP 排行 TOP 15</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>IP 地址</th>
                            <th>地域</th>
                            <th>运营商</th>
                            <th class="text-end">PV</th>
                            <th class="text-end">UV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topIps as $i => $ip)
                        <tr>
                            <td><span class="text-secondary">{{ $i + 1 }}</span></td>
                            <td>
                                <code>{{ $ip['display_ip'] }}</code>
                                <a href="{{ route('admin.visitor-analytics.detail', ['search' => $ip['display_ip']]) }}" class="ms-1" title="查看该 IP 访问记录">
                                    <i class="ti ti-external-link text-secondary" style="font-size: 0.75rem;"></i>
                                </a>
                            </td>
                            <td>
                                @if($ip['country'] || $ip['city'])
                                    <span class="text-nowrap">{{ $ip['country'] ?? '' }} {{ $ip['city'] ?? '' }}</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $ip['isp'] ?? '-' }}</td>
                            <td class="text-end">{{ number_format($ip['pv']) }}</td>
                            <td class="text-end">{{ number_format($ip['uv']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 运营商分布 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">运营商分布</h3>
            </div>
            <div class="card-body">
                @if(count($ispStats) > 0)
                    @foreach($ispStats as $item)
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-fill">
                            <div class="progress progress-xs">
                                <div class="progress-bar bg-purple" style="width: {{ $ispStats[0]['cnt'] > 0 ? round($item['cnt'] / $ispStats[0]['cnt'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="ms-3 text-end" style="min-width: 60px;">
                            <span class="badge bg-purple-lt">{{ number_format($item['cnt']) }}</span>
                        </div>
                        <div class="ms-2" style="min-width: 80px;">{{ $item['isp'] }}</div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-secondary py-4">暂无数据</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 地域分布 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">地域分布</h3>
            </div>
            <div class="card-body">
                @if(count($topCountries) > 0 || count($topCities) > 0)
                    @if(count($topCountries) > 0)
                    <h4 class="mb-3">国家/地区</h4>
                    <div class="mb-4">
                        @foreach($topCountries as $item)
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-primary" style="width: {{ $topCountries[0]['cnt'] > 0 ? round($item['cnt'] / $topCountries[0]['cnt'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="ms-3 text-end" style="min-width: 80px;">
                                <span class="badge bg-primary-lt">{{ number_format($item['cnt']) }}</span>
                            </div>
                            <div class="ms-2" style="min-width: 80px;">{{ $item['country'] }}</div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(count($topCities) > 0)
                    <h4 class="mb-3">城市</h4>
                    <div>
                        @foreach($topCities as $item)
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-fill">
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-azure" style="width: {{ $topCities[0]['cnt'] > 0 ? round($item['cnt'] / $topCities[0]['cnt'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="ms-3 text-end" style="min-width: 80px;">
                                <span class="badge bg-azure-lt">{{ number_format($item['cnt']) }}</span>
                            </div>
                            <div class="ms-2" style="min-width: 80px;">{{ $item['city'] }}</div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                @else
                    <div class="text-center text-secondary py-5">
                        <i class="ti ti-map-pin" style="font-size: 2rem;"></i>
                        <div class="mt-2">暂无地域数据</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 爬虫详情 --}}
    @if($botStats['bots'] > 0)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-robot me-2"></i>爬虫详情</h3>
                <div class="card-subtitle text-secondary">{{ number_format($botStats['bots']) }} 次 / {{ $botStats['bot_rate'] }}%</div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th class="w-1">#</th>
                            <th>机器人</th>
                            <th class="text-end">次数</th>
                            <th class="text-end">占比</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($botStats['by_bot'] as $i => $bot)
                        <tr>
                            <td><span class="text-secondary">{{ $i + 1 }}</span></td>
                            <td><span class="badge bg-orange-lt">{{ $bot['bot_name'] }}</span></td>
                            <td class="text-end">{{ number_format($bot['cnt']) }}</td>
                            <td class="text-end">{{ $botStats['bots'] > 0 ? round($bot['cnt'] / $botStats['bots'] * 100, 1) : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/apexcharts/apexcharts.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var trendLabels = @json($trendLabels);
    var trendPv = @json($trendPv);
    var trendUv = @json($trendUv);
    var hourlyLabels = @json($hourlyLabels);
    var hourlyData = @json($hourlyData);
    var deviceData = @json($deviceStats);
    var browserData = @json($browserStats);
    var osData = @json($osStats);

    new ApexCharts(document.querySelector('#trend-chart'), {
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: 'PV', data: trendPv },
            { name: 'UV', data: trendUv }
        ],
        xaxis: { categories: trendLabels, labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { style: { fontSize: '11px' } } },
        colors: ['#206bc4', '#45aaf2'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
        legend: { position: 'top', fontSize: '12px' },
        grid: { strokeDashArray: 4 },
        tooltip: { shared: true }
    }).render();

    new ApexCharts(document.querySelector('#hourly-chart'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'PV', data: hourlyData }],
        xaxis: { categories: hourlyLabels, labels: { style: { fontSize: '10px' }, rotate: -45 } },
        yaxis: { labels: { style: { fontSize: '11px' } } },
        colors: ['#206bc4'],
        plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
        dataLabels: { enabled: false },
        grid: { strokeDashArray: 4 }
    }).render();

    function renderPie(selector, data, nameKey, colors) {
        if (!data || data.length === 0) {
            var el = document.querySelector(selector);
            if (el) el.innerHTML = '<div class="text-center text-secondary py-4">暂无数据</div>';
            return;
        }
        var labels = data.map(function(d) { return d[nameKey] || 'Unknown'; });
        var series = data.map(function(d) { return parseInt(d.cnt); });
        new ApexCharts(document.querySelector(selector), {
            chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
            series: series,
            labels: labels,
            colors: colors,
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: true, formatter: function(val) { return Math.round(val) + '%'; } },
            plotOptions: { pie: { donut: { size: '60%' } } }
        }).render();
    }

    renderPie('#device-chart', deviceData, 'device_type', ['#206bc4', '#45aaf2', '#7795f8', '#e74c3c']);
    renderPie('#browser-chart', browserData, 'browser', ['#206bc4', '#f6c343', '#45aaf2', '#e74c3c', '#9b59b6', '#2ecc71', '#f39c12']);
    renderPie('#os-chart', osData, 'os', ['#206bc4', '#45aaf2', '#f6c343', '#e74c3c', '#2ecc71', '#9b59b6']);
})();
</script>
@endpush
@endsection
