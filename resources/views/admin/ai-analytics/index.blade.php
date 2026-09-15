@extends('layouts.admin')

@section('title', 'AI 用量分析')
@section('page-pretitle', '系统监控')
@section('page-title', 'AI 用量趋势分析')

@section('content')
<div data-json-dates="@json($dates)" data-json-callsdata="@json($callsData)" data-json-tokensdata="@json($tokensData)" data-json-scenariostats="@json($scenarioStats)">
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">时间范围</h3>
                <div class="card-actions">
                    <div class="btn-group">
                        <a href="?days=7" class="btn btn-sm {{ $days == 7 ? 'btn-primary' : 'btn-outline-primary' }}">近7日</a>
                        <a href="?days=30" class="btn btn-sm {{ $days == 30 ? 'btn-primary' : 'btn-outline-primary' }}">近30日</a>
                        <a href="?days=90" class="btn btn-sm {{ $days == 90 ? 'btn-primary' : 'btn-outline-primary' }}">近90日</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="ti ti-api"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_calls']) }}</div>
                                <div class="text-secondary">总调用次数</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-azure text-white avatar"><i class="ti ti-code"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_tokens']) }}</div>
                                <div class="text-secondary">总 Token</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar"><i class="ti ti-coin"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_cost']) }}</div>
                                <div class="text-secondary">总费用</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-orange text-white avatar"><i class="ti ti-clock"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['avg_latency'] }} ms</div>
                                <div class="text-secondary">平均延迟</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">每日调用趋势</h3>
            </div>
            <div class="card-body">
                <canvas id="callsChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">场景分布</h3>
            </div>
            <div class="card-body">
                <canvas id="scenarioChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Token 消耗趋势</h3>
            </div>
            <div class="card-body">
                <canvas id="tokensChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">供应商分布</h3>
            </div>
            <div class="card-body">
                @if($providerStats->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>供应商</th>
                                <th class="text-end">调用次数</th>
                                <th class="text-end">占比</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $total = $providerStats->sum('count'); @endphp
                            @foreach($providerStats as $p)
                            <tr>
                                <td><span class="badge bg-blue-lt">{{ $p->provider ?? '未知' }}</span></td>
                                <td class="text-end">{{ number_format($p->count) }}</td>
                                <td class="text-end">{{ $total > 0 ? round($p->count / $total * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-secondary py-5">暂无数据</div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-ai-analytics-index.js') }}"></script>
@endpush