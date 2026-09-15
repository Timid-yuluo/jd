@extends('layouts.admin')

@section('title', '推荐效果看板')
@section('page-pretitle', '数据分析')
@section('page-title', '推荐效果看板')

@section('content')
<div class="row row-deck row-cards mb-3">
    {{-- 概览卡片 --}}
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-primary-lt"><i class="ti ti-list-checks"></i></span>
                    <div class="ms-auto text-secondary">
                        <i class="ti ti-trending-up"></i>
                    </div>
                </div>
                <div class="metric">
                    <div class="metric-value text-primary">{{ number_format($metrics['total']) }}</div>
                    <div class="metric-label">30 天总推荐</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-info-lt"><i class="ti ti-eye"></i></span>
                    <div class="ms-auto text-secondary">
                        <i class="ti ti-trending-up"></i>
                    </div>
                </div>
                <div class="metric">
                    <div class="metric-value text-info">{{ $metrics['view_rate'] }}%</div>
                    <div class="metric-label">查看率（{{ number_format($metrics['viewed']) }}）</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-success-lt"><i class="ti ti-check"></i></span>
                    <div class="ms-auto text-secondary">
                        <i class="ti ti-trending-up"></i>
                    </div>
                </div>
                <div class="metric">
                    <div class="metric-value text-success">{{ $metrics['apply_rate'] }}%</div>
                    <div class="metric-label">投递率（{{ number_format($metrics['applied']) }}）</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-danger-lt"><i class="ti ti-x"></i></span>
                    <div class="ms-auto text-secondary">
                        <i class="ti ti-trending-up"></i>
                    </div>
                </div>
                <div class="metric">
                    <div class="metric-value text-danger">{{ $metrics['dismiss_rate'] }}%</div>
                    <div class="metric-label">忽略率（{{ number_format($metrics['dismissed']) }}）</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-deck row-cards">
    {{-- 每日趋势 --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-chart-line me-2"></i>每日推荐趋势</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>日期</th>
                            <th class="text-end">推荐数</th>
                            <th class="text-end">查看数</th>
                            <th class="text-end">投递数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyTrend as $row)
                            <tr>
                                <td>{{ $row->date }}</td>
                                <td class="text-end">{{ number_format($row->cnt) }}</td>
                                <td class="text-end">{{ number_format($row->viewed) }}</td>
                                <td class="text-end">{{ number_format($row->applied) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 平均匹配分 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-target me-2"></i>平均匹配分</h3>
            </div>
            <div class="card-body text-center">
                <div class="display-3 fw-bold text-primary">
                    {{ number_format($metrics['avg_score'], 1) }}
                </div>
                <div class="text-secondary mt-2">满分 100</div>
            </div>
        </div>
    </div>
</div>

<div class="row row-deck row-cards">
    {{-- Top 公司 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-building me-2"></i>Top 10 推荐公司</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>公司</th>
                            <th class="text-end">推荐数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCompanies as $i => $row)
                            <tr>
                                <td class="text-secondary">{{ $loop->iteration }}</td>
                                <td>{{ $row->company }}</td>
                                <td class="text-end">{{ number_format($row->cnt) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 忽略原因分布 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-list-details me-2"></i>忽略原因分布</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>原因</th>
                            <th class="text-end">次数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dismissReasons as $row)
                            <tr>
                                <td>
                                    @switch($row->reason)
                                        @case('salary') 薪资不匹配 @break
                                        @case('location') 地点不匹配 @break
                                        @case('industry') 行业不匹配 @break
                                        @case('seniority') 资历不匹配 @break
                                        @case('other') 其他 @break
                                        @default {{ $row->reason ?: '未填写' }}
                                    @endswitch
                                </td>
                                <td class="text-end">{{ number_format($row->cnt) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-secondary">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- #30 最近运行历史 --}}
<div class="row row-deck row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-history me-2"></i>最近 20 次生成运行</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户</th>
                            <th>状态</th>
                            <th class="text-end">候选</th>
                            <th class="text-end">生成</th>
                            <th class="text-end">高匹配</th>
                            <th class="text-end">耗时</th>
                            <th>时间</th>
                            <th>失败原因</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRuns as $run)
                            <tr>
                                <td class="text-secondary">{{ $run->id }}</td>
                                <td>{{ $run->user?->name ?? '-' }}</td>
                                <td>
                                    @switch($run->status)
                                        @case('running') <span class="badge bg-warning-lt">运行中</span> @break
                                        @case('completed') <span class="badge bg-success-lt">成功</span> @break
                                        @case('failed') <span class="badge bg-danger-lt">失败</span> @break
                                        @default <span class="badge bg-secondary-lt">{{ $run->status }}</span>
                                    @endswitch
                                </td>
                                <td class="text-end">{{ number_format($run->candidate_count) }}</td>
                                <td class="text-end">{{ number_format($run->created_count) }}</td>
                                <td class="text-end">{{ number_format($run->high_match_count) }}</td>
                                <td class="text-end">{{ $run->duration_ms }} ms</td>
                                <td class="text-secondary small">{{ $run->created_at?->format('m-d H:i:s') }}</td>
                                <td class="text-danger small text-truncate" style="max-width: 220px;" title="{{ $run->error_message }}">
                                    {{ Str::limit($run->error_message, 50) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-secondary">暂无运行记录</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
