@extends('layouts.admin')

@section('title', 'AI 用量日志')
@section('page-pretitle', '系统监控')
@section('page-title', 'AI 用量日志')

@php
    $costYuan = round($summary['total_cost_micros'] / 1000000, 4);
    $todayCostYuan = round($todayStats['cost_micros'] / 1000000, 4);
@endphp

@section('content')
<div data-var-trendcalls="{{ json_encode($trendCalls) }}" data-var-trendcosts="{{ json_encode($trendCosts) }}" data-var-trendlabels="{{ json_encode($trendLabels) }}">
<div class="row row-cards">
    {{-- 顶部统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards">
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
                                <span class="bg-azure text-white avatar"><i class="ti ti-arrow-back-up"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_prompt_tokens']) }}</div>
                                <div class="text-secondary">输入 Token</div>
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
                                <span class="bg-lime text-white avatar"><i class="ti ti-arrow-forward-up"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ number_format($summary['total_completion_tokens']) }}</div>
                                <div class="text-secondary">输出 Token</div>
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
                                <div class="font-weight-medium">¥{{ $costYuan }}</div>
                                <div class="text-secondary">总费用</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 延迟统计 + 今日统计 --}}
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-orange-lt me-3"><i class="ti ti-clock"></i></span>
                    <div>
                        <h3 class="mb-0">{{ $summary['avg_latency'] }} ms</h3>
                        <div class="text-secondary">平均延迟</div>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="font-weight-medium">{{ $summary['p95_latency'] }} ms</div>
                        <div class="text-secondary small">P95</div>
                    </div>
                    <div class="col-4">
                        <div class="font-weight-medium">{{ $summary['max_latency'] }} ms</div>
                        <div class="text-secondary small">最大</div>
                    </div>
                    <div class="col-4">
                        <div class="font-weight-medium">{{ number_format($summary['total_tokens']) }}</div>
                        <div class="text-secondary small">总 Token</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 今日统计 --}}
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-blue-lt me-3"><i class="ti ti-calendar"></i></span>
                    <div>
                        <h3 class="mb-0">今日</h3>
                        <div class="text-secondary">{{ now()->format('Y-m-d') }}</div>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-3">
                        <div class="font-weight-medium">{{ number_format($todayStats['calls']) }}</div>
                        <div class="text-secondary small">调用</div>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-medium">{{ number_format($todayStats['prompt_tokens']) }}</div>
                        <div class="text-secondary small">输入</div>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-medium">{{ number_format($todayStats['completion_tokens']) }}</div>
                        <div class="text-secondary small">输出</div>
                    </div>
                    <div class="col-3">
                        <div class="font-weight-medium">¥{{ $todayCostYuan }}</div>
                        <div class="text-secondary small">费用</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 14天趋势图 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">14 天调用趋势</h3>
            </div>
            <div class="card-body">
                <div id="trend-chart" style="height:160px"></div>
            </div>
        </div>
    </div>

    {{-- 按场景分组 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">按场景统计</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>场景</th>
                            <th>调用次数</th>
                            <th>输入 Token</th>
                            <th>输出 Token</th>
                            <th>费用</th>
                            <th>平均延迟</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byScenario as $row)
                        <tr>
                            <td><span class="badge bg-blue-lt">{{ $row->scenario }}</span></td>
                            <td>{{ number_format($row->calls) }}</td>
                            <td>{{ number_format($row->prompt_tokens) }}</td>
                            <td>{{ number_format($row->completion_tokens) }}</td>
                            <td>¥{{ round($row->cost_micros / 1000000, 4) }}</td>
                            <td>{{ $row->avg_latency }} ms</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 按供应商分组 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">按供应商统计</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>供应商</th>
                            <th>调用次数</th>
                            <th>输入 Token</th>
                            <th>输出 Token</th>
                            <th>费用</th>
                            <th>平均延迟</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byProvider as $row)
                        <tr>
                            <td><span class="badge bg-purple-lt">{{ $row->provider }}</span></td>
                            <td>{{ number_format($row->calls) }}</td>
                            <td>{{ number_format($row->prompt_tokens) }}</td>
                            <td>{{ number_format($row->completion_tokens) }}</td>
                            <td>¥{{ round($row->cost_micros / 1000000, 4) }}</td>
                            <td>{{ $row->avg_latency }} ms</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 调用记录列表 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">调用记录</h3>
                <div class="card-actions">
                    <form method="GET" action="{{ route('admin.usage-logs.index') }}" class="d-flex gap-2 flex-wrap">
                        <select name="scenario" class="form-select form-select-sm" style="width:130px">
                            <option value="">全部场景</option>
                            @foreach($scenarios as $s)
                                <option value="{{ $s }}" {{ request('scenario') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="user_id" class="form-control form-control-sm" placeholder="用户ID" value="{{ request('user_id') }}" style="width:90px">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="width:140px" title="开始日期">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="width:140px" title="结束日期">
                        <button type="submit" class="btn btn-sm btn-primary">筛选</button>
                        @if(request('scenario') || request('user_id') || request('date_from') || request('date_to'))
                            <a href="{{ route('admin.usage-logs.index') }}" class="btn btn-sm btn-secondary">重置</a>
                        @endif
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>用户</th>
                            <th>场景</th>
                            <th>供应商</th>
                            <th>输入 Token</th>
                            <th>输出 Token</th>
                            <th>延迟</th>
                            <th>费用</th>
                            <th>时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td><span class="text-secondary">{{ $log->id }}</span></td>
                            <td>{{ $log->user?->name ?? '未知' }}</td>
                            <td><span class="badge bg-blue-lt">{{ $log->scenario }}</span></td>
                            <td>{{ $log->provider ?? '-' }}</td>
                            <td>
                                @if($log->prompt_tokens > 0)
                                    {{ number_format($log->prompt_tokens) }}
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->completion_tokens > 0)
                                    {{ number_format($log->completion_tokens) }}
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->latency_ms > 0)
                                    <span class="{{ $log->latency_ms > 10000 ? 'text-danger' : ($log->latency_ms > 5000 ? 'text-warning' : '') }}">
                                        {{ $log->latency_ms }} ms
                                    </span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->cost_micros > 0)
                                    ¥{{ round($log->cost_micros / 1000000, 4) }}
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $log->created_at->format('m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.usage-logs.show', $log) }}" class="btn btn-sm btn-outline-info">详情</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary">暂无数据</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/apexcharts/apexcharts.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-usage-logs-index.js') }}"></script>
@endpush