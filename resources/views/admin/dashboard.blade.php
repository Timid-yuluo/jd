@extends('layouts.admin')

@section('title', '控制台')
@section('page-pretitle', '概览')
@section('page-title', '控制台')

@section('content')
<div class="row row-deck row-cards">
    {{-- 核心统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar"><i class="ti ti-users"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['users'] }}</div>
                                <div class="text-secondary">总用户数</div>
                            </div>
                            <div class="col-auto">
                                <span class="text-green d-inline-flex align-items-center lh-1">
                                    {{ $summary['users_today'] }}
                                    <i class="ti ti-trending-up ms-1"></i>
                                </span>
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
                                <span class="bg-azure text-white avatar"><i class="ti ti-file-text"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['resumes'] }}</div>
                                <div class="text-secondary">简历总数</div>
                            </div>
                            <div class="col-auto">
                                <span class="text-green d-inline-flex align-items-center lh-1">
                                    {{ $summary['resumes_today'] }}
                                    <i class="ti ti-trending-up ms-1"></i>
                                </span>
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
                                <span class="bg-green text-white avatar"><i class="ti ti-message-chatbot"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['interviews'] }}</div>
                                <div class="text-secondary">面试次数</div>
                            </div>
                            <div class="col-auto">
                                <span class="text-green d-inline-flex align-items-center lh-1">
                                    {{ $summary['interviews_today'] }}
                                    <i class="ti ti-trending-up ms-1"></i>
                                </span>
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
                                <span class="bg-purple text-white avatar"><i class="ti ti-api"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">{{ $summary['ai_calls'] }}</div>
                                <div class="text-secondary">AI 调用</div>
                            </div>
                            <div class="col-auto">
                                <span class="text-green d-inline-flex align-items-center lh-1">
                                    {{ $summary['ai_calls_today'] }}
                                    <i class="ti ti-trending-up ms-1"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 营收统计卡片 --}}
    @isset($revenue)
    <div class="col-12">
        <div class="row row-cards">
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-yellow text-white avatar"><i class="ti ti-currency-yuan"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">&yen;{{ $revenue['total'] }}</div>
                                <div class="text-secondary">累计营收</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar"><i class="ti ti-coin"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">&yen;{{ $revenue['month'] }}</div>
                                <div class="text-secondary">本月营收</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-blue text-white avatar"><i class="ti ti-calendar-stats"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">&yen;{{ $revenue['today'] }}</div>
                                <div class="text-secondary">今日营收</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisset

    {{-- 待处理提醒 --}}
    @isset($pending_alerts)
    @php
        $hasAlerts = ($pending_alerts['pending_feedbacks'] ?? 0) > 0
            || ($pending_alerts['suspended_users'] ?? 0) > 0
            || ($pending_alerts['pending_deletion_users'] ?? 0) > 0
            || ($pending_alerts['queue_backlog'] ?? 0) > 5
            || ($pending_alerts['failed_jobs'] ?? 0) > 0;
    @endphp
    @if($hasAlerts)
    <div class="col-12">
        <div class="card bg-warning-lt">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-bell-ringing me-1"></i>待处理提醒</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if(($pending_alerts['pending_feedbacks'] ?? 0) > 0)
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('admin.feedbacks.index', ['status' => 'pending']) }}" class="card card-sm">
                            <div class="card-body text-center py-2">
                                <div class="text-warning font-weight-medium">{{ $pending_alerts['pending_feedbacks'] }} 条待处理反馈</div>
                                <div class="text-secondary small">点击查看</div>
                            </div>
                        </a>
                    </div>
                    @endif
                    @if(($pending_alerts['suspended_users'] ?? 0) > 0)
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}" class="card card-sm">
                            <div class="card-body text-center py-2">
                                <div class="text-danger font-weight-medium">{{ $pending_alerts['suspended_users'] }} 个封禁用户</div>
                                <div class="text-secondary small">点击查看</div>
                            </div>
                        </a>
                    </div>
                    @endif
                    @if(($pending_alerts['pending_deletion_users'] ?? 0) > 0)
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('admin.users.index', ['deletion' => 'pending']) }}" class="card card-sm">
                            <div class="card-body text-center py-2">
                                <div class="text-danger font-weight-medium">{{ $pending_alerts['pending_deletion_users'] }} 个待注销用户</div>
                                <div class="text-secondary small">点击查看</div>
                            </div>
                        </a>
                    </div>
                    @endif
                    @if(($pending_alerts['queue_backlog'] ?? 0) > 5)
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('admin.system-ops.index') }}" class="card card-sm">
                            <div class="card-body text-center py-2">
                                <div class="text-warning font-weight-medium">队列积压 {{ $pending_alerts['queue_backlog'] }} 个任务</div>
                                <div class="text-secondary small">点击查看</div>
                            </div>
                        </a>
                    </div>
                    @endif
                    @if(($pending_alerts['failed_jobs'] ?? 0) > 0)
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('admin.system-ops.index') }}" class="card card-sm">
                            <div class="card-body text-center py-2">
                                <div class="text-danger font-weight-medium">{{ $pending_alerts['failed_jobs'] }} 个失败任务</div>
                                <div class="text-secondary small">点击处理</div>
                            </div>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endisset

    {{-- ATS 评分分布 --}}
    @isset($ats_distribution)
    @if(($ats_distribution['total'] ?? 0) > 0)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-chart-dots-3 me-1"></i>ATS 评分分布</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.resumes.index') }}" class="btn btn-sm btn-outline-primary">查看简历</a>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-4">
                        <div class="text-secondary small">已评分简历</div>
                        <div class="h2 mb-0">{{ $ats_distribution['total'] }}</div>
                    </div>
                    <div class="me-4">
                        <div class="text-secondary small">全站平均分</div>
                        <div class="h2 mb-0">{{ $ats_distribution['avg'] }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">低分简历(&lt;60)</div>
                        <div class="h2 mb-0 text-danger">{{ $ats_distribution['low_score_count'] }}</div>
                    </div>
                </div>
                <div class="mb-2">
                    @foreach($ats_distribution['segments'] as $seg)
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-{{ $seg['color'] }}-lt me-2" style="min-width: 56px;">{{ $seg['label'] }}</span>
                        <div class="flex-fill">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $seg['color'] }}" style="width: {{ $seg['percent'] }}%"></div>
                            </div>
                        </div>
                        <span class="ms-2 small text-secondary" style="min-width: 70px; text-align: right;">{{ $seg['count'] }} ({{ $seg['percent'] }}%)</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
    @endisset
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">快捷入口</h3>
            </div>
            <div class="card-body">
                <div class="btn-list flex-wrap">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-users me-1"></i>用户管理</a>
                    <a href="{{ route('admin.resumes.index') }}" class="btn btn-outline-azure btn-sm"><i class="ti ti-file-text me-1"></i>简历管理</a>
                    <a href="{{ route('admin.interviews.index') }}" class="btn btn-outline-green btn-sm"><i class="ti ti-message-chatbot me-1"></i>面试记录</a>
                    <a href="{{ route('admin.job-applications.index') }}" class="btn btn-outline-purple btn-sm"><i class="ti ti-layout-kanban me-1"></i>投递看板</a>
                    <a href="{{ route('admin.feedbacks.index') }}" class="btn btn-outline-warning btn-sm"><i class="ti ti-message-circle me-1"></i>意见反馈</a>
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-yellow btn-sm"><i class="ti ti-crown me-1"></i>套餐管理</a>
                    <a href="{{ route('admin.credit-packs.index') }}" class="btn btn-outline-info btn-sm"><i class="ti ti-coin me-1"></i>次卡管理</a>
                    <a href="{{ route('admin.ai-config.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-robot me-1"></i>AI 配置</a>
                    <a href="{{ route('admin.email-logs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-mail me-1"></i>邮件日志</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">投递漏斗</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>阶段</th>
                            <th class="text-end">数量</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($status_distribution as $status)
                            <tr>
                                <td>{{ $status['label'] }}</td>
                                <td class="text-end"><span class="badge bg-azure-lt">{{ $status['value'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">AI 用量统计</h3>
            </div>
            <div class="card-body">
                <div class="d-flex mb-3">
                    <div class="me-4">
                        <div class="text-secondary small">总 Token 消耗</div>
                        <div class="h3 mb-0">{{ $ai_usage['total_tokens'] }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">总费用 (微单位)</div>
                        <div class="h3 mb-0">{{ $ai_usage['total_cost'] }}</div>
                    </div>
                </div>
                @if(!empty($ai_usage['by_scenario']))
                <table class="table table-sm table-vcenter">
                    <thead>
                        <tr>
                            <th>场景</th>
                            <th class="text-end">调用次数</th>
                            <th class="text-end">Token</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ai_usage['by_scenario'] as $item)
                            <tr>
                                <td><span class="badge bg-blue-lt">{{ $item['scenario'] }}</span></td>
                                <td class="text-end">{{ $item['count'] }}</td>
                                <td class="text-end">{{ number_format($item['tokens']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center text-secondary py-3">暂无 AI 调用记录</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">最近投递动态</h3>
            </div>
            <div class="card-body">
                @forelse ($activities as $activity)
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="avatar avatar-sm bg-{{ $activity['status_type'] }}-lt text-{{ $activity['status_type'] }}">
                                {{ mb_substr($activity['user'], 0, 1) }}
                            </span>
                        </div>
                        <div class="flex-fill">
                            <div><strong>{{ $activity['user'] }}</strong> {{ $activity['action'] }}</div>
                            <div class="text-secondary small">{{ $activity['time'] }}</div>
                        </div>
                        <div>
                            <span class="badge bg-{{ $activity['status_type'] }}">{{ $activity['status'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-secondary py-3">暂无动态</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">系统状态</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach ($system_usage as $usage)
                        <div class="col-md-4 mb-3">
                            <div class="d-flex mb-1">
                                <div>{{ $usage['label'] }}</div>
                                <div class="ms-auto">{{ $usage['value'] }}%</div>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-{{ $usage['color'] }}" style="width: {{ $usage['value'] }}%" role="progressbar" aria-valuenow="{{ $usage['value'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">评分队列健康</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">队列名称</div>
                        <div class="datagrid-content">{{ $queue_health['queue_name'] ?? 'default' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">队列积压</div>
                        <div class="datagrid-content">{{ number_format($queue_health['queue_backlog'] ?? 0) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">待评分题目</div>
                        <div class="datagrid-content">{{ number_format($queue_health['pending_evaluations'] ?? 0) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">失败任务数</div>
                        <div class="datagrid-content">
                            @php $failed = (int) ($queue_health['failed_jobs'] ?? 0); @endphp
                            <span class="{{ $failed > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($failed) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">JD 对齐质量</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">含 JD 面试数</div>
                        <div class="datagrid-content">{{ number_format($jd_quality['total_with_jd'] ?? 0) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">已量化场次</div>
                        <div class="datagrid-content">{{ number_format($jd_quality['evaluated_count'] ?? 0) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">平均匹配分</div>
                        <div class="datagrid-content">
                            @php $avgMatchScore = (float) ($jd_quality['avg_match_score'] ?? 0); @endphp
                            <span class="{{ $avgMatchScore >= 8 ? 'text-success' : ($avgMatchScore >= 5 ? 'text-warning' : 'text-danger') }}">
                                {{ number_format($avgMatchScore, 1) }}/10
                            </span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">高匹配场次 (8-10)</div>
                        <div class="datagrid-content text-success">{{ number_format($jd_quality['high_match_count'] ?? 0) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">低匹配场次 (0-4)</div>
                        <div class="datagrid-content text-danger">{{ number_format($jd_quality['low_match_count'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.interviews.index', ['jd_mode' => 'with_jd', 'jd_match_band' => 'low']) }}" class="btn btn-outline-danger btn-sm">
                        查看低匹配面试
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">AI 提前结束治理</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">累计 AI 终止场次</div>
                        <div class="datagrid-content text-danger">
                            {{ number_format((int) data_get($interview_governance ?? [], 'ai_early_terminated_count', 0)) }}
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">今日新增 AI 终止</div>
                        <div class="datagrid-content">
                            {{ number_format((int) data_get($interview_governance ?? [], 'ai_early_terminated_today', 0)) }}
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.interviews.index', ['ai_termination' => 'terminated']) }}" class="btn btn-outline-danger btn-sm">
                        查看 AI 提前结束列表
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 7日趋势图 --}}
@isset($trend_7d)
<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-chart-line me-2 text-primary"></i>近7日用户注册 & 面试趋势</h3></div>
            <div class="card-body">
                <svg width="100%" height="160" viewBox="0 0 350 160" preserveAspectRatio="xMidYMid meet">
                    @php
                        $labels = $trend_7d['labels'] ?? [];
                        $users = $trend_7d['users'] ?? [];
                        $interviews = $trend_7d['interviews'] ?? [];
                        $maxVal = max(1, max(array_merge($users, $interviews)));
                        $count = count($labels);
                    @endphp
                    {{-- 网格线 --}}
                    <line x1="30" y1="10" x2="340" y2="10" stroke="#f0f0f0" stroke-width="0.5"/>
                    <line x1="30" y1="50" x2="340" y2="50" stroke="#f0f0f0" stroke-width="0.5"/>
                    <line x1="30" y1="90" x2="340" y2="90" stroke="#f0f0f0" stroke-width="0.5"/>
                    <line x1="30" y1="130" x2="340" y2="130" stroke="#e0e0e0" stroke-width="0.5"/>
                    {{-- Y轴标签 --}}
                    <text x="26" y="14" text-anchor="end" font-size="9" fill="#999">{{ $maxVal }}</text>
                    <text x="26" y="54" text-anchor="end" font-size="9" fill="#999">{{ round($maxVal * 0.75) }}</text>
                    <text x="26" y="94" text-anchor="end" font-size="9" fill="#999">{{ round($maxVal * 0.5) }}</text>
                    <text x="26" y="134" text-anchor="end" font-size="9" fill="#999">0</text>
                    {{-- 用户注册线 --}}
                    @if($count > 1)
                    @php
                        $uPts = []; $iPts = [];
                        foreach ($users as $idx => $val) {
                            $x = 30 + ($idx / ($count - 1)) * 310;
                            $y = 130 - ($val / $maxVal) * 120;
                            $uPts[] = "{$x}," . round($y, 1);
                        }
                        foreach ($interviews as $idx => $val) {
                            $x = 30 + ($idx / ($count - 1)) * 310;
                            $y = 130 - ($val / $maxVal) * 120;
                            $iPts[] = "{$x}," . round($y, 1);
                        }
                    @endphp
                    <polyline points="{{ implode(' ', $uPts) }}" fill="none" stroke="#206bc4" stroke-width="2" stroke-linejoin="round"/>
                    <polyline points="{{ implode(' ', $iPts) }}" fill="none" stroke="#2fb344" stroke-width="2" stroke-linejoin="round"/>
                    @foreach ($users as $idx => $val)
                        @php $x = 30 + ($idx / ($count - 1)) * 310; $y = 130 - ($val / $maxVal) * 120; @endphp
                        <circle cx="{{ $x }}" cy="{{ round($y, 1) }}" r="2.5" fill="#206bc4"/>
                    @endforeach
                    @foreach ($interviews as $idx => $val)
                        @php $x = 30 + ($idx / ($count - 1)) * 310; $y = 130 - ($val / $maxVal) * 120; @endphp
                        <circle cx="{{ $x }}" cy="{{ round($y, 1) }}" r="2.5" fill="#2fb344"/>
                    @endforeach
                    @endif
                    {{-- X轴标签 --}}
                    @foreach ($labels as $idx => $label)
                        @php $x = $count > 1 ? 30 + ($idx / ($count - 1)) * 310 : 185; @endphp
                        <text x="{{ $x }}" y="150" text-anchor="middle" font-size="9" fill="#999">{{ $label }}</text>
                    @endforeach
                </svg>
                <div class="d-flex gap-3 mt-2 small">
                    <span><span style="display:inline-block;width:12px;height:3px;background:#206bc4;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>用户注册</span>
                    <span><span style="display:inline-block;width:12px;height:3px;background:#2fb344;border-radius:2px;vertical-align:middle;margin-right:4px;"></span>面试场次</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="ti ti-chart-bar me-2 text-warning"></i>近7日 ATS 评分均值趋势</h3></div>
            <div class="card-body">
                @php $atsAvg = $trend_7d['ats_avg'] ?? []; @endphp
                <div class="d-flex align-items-end gap-2" style="height:140px;">
                    @foreach ($atsAvg as $idx => $val)
                        <div class="flex-fill text-center">
                            <div class="small fw-semibold" style="font-size:.72rem;color:#4c6fff;">{{ $val > 0 ? $val : '-' }}</div>
                            <div style="height:{{ max(4, ($val > 0 ? $val / 100 : 0) * 100) }}px;background:linear-gradient(180deg,#4c6fff 0%,#a3b5ff 100%);border-radius:4px 4px 0 0;min-height:4px;transition:height .3s;"></div>
                            <div class="small text-secondary mt-1" style="font-size:.68rem;">{{ $labels[$idx] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endisset

@endsection
