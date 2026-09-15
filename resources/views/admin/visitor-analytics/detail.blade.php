@extends('layouts.admin')

@section('title', '访问明细')
@section('page-pretitle', '数据分析')
@section('page-title', '访问明细记录')

@section('page-actions')
<div class="d-flex gap-2">
    <a href="{{ route('admin.visitor-analytics.export', request()->query()) }}" class="btn btn-sm btn-outline-success">
        <i class="ti ti-download me-1"></i>导出CSV
    </a>
    <a href="{{ route('admin.visitor-analytics.index') }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-chart-bar me-1"></i> 返回统计
    </a>
</div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">访问记录</h3>
                <div class="card-actions">
                    <form method="GET" action="{{ route('admin.visitor-analytics.detail') }}" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索路径/IP/地域/事件..." value="{{ request('search') }}" style="min-width: 180px;">
                        <select name="event_type" class="form-select form-select-sm" style="width: 130px;">
                            <option value="">全部类型</option>
                            @foreach($eventTypes as $type)
                                <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        <select name="device_type" class="form-select form-select-sm" style="width: 110px;">
                            <option value="">全部设备</option>
                            <option value="desktop" {{ request('device_type') === 'desktop' ? 'selected' : '' }}>桌面</option>
                            <option value="mobile" {{ request('device_type') === 'mobile' ? 'selected' : '' }}>手机</option>
                            <option value="tablet" {{ request('device_type') === 'tablet' ? 'selected' : '' }}>平板</option>
                            <option value="bot" {{ request('device_type') === 'bot' ? 'selected' : '' }}>机器人</option>
                        </select>
                        <input type="number" name="user_id" class="form-control form-control-sm" placeholder="用户ID" value="{{ request('user_id') }}" style="width: 90px;">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="width: 140px;">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="width: 140px;">
                        <button type="submit" class="btn btn-sm btn-primary">筛选</button>
                        @if(request()->hasAny(['search', 'event_type', 'device_type', 'user_id', 'date_from', 'date_to']))
                            <a href="{{ route('admin.visitor-analytics.detail') }}" class="btn btn-sm btn-secondary">重置</a>
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
                            <th>类型</th>
                            <th>页面</th>
                            <th>设备</th>
                            <th>浏览器</th>
                            <th>系统</th>
                            <th>真实 IP</th>
                            <th>地域</th>
                            <th>运营商</th>
                            <th>停留</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                        <tr>
                            <td><span class="text-secondary">{{ $visit->id }}</span></td>
                            <td>
                                @if($visit->user)
                                    <a href="{{ route('admin.users.show', $visit->user) }}">{{ $visit->user->name }}</a>
                                @elseif($visit->is_bot)
                                    <span class="badge bg-orange-lt"><i class="ti ti-robot me-1"></i>{{ $visit->bot_name ?? 'Bot' }}</span>
                                @else
                                    <span class="text-secondary">匿名</span>
                                @endif
                            </td>
                            <td>
                                @if($visit->event_type === 'pageview')
                                    <span class="badge bg-blue-lt">PV</span>
                                @elseif($visit->event_type === 'scroll_depth')
                                    <span class="badge bg-green-lt">滚动 {{ $visit->event_label }}</span>
                                @elseif($visit->event_type === 'click')
                                    <span class="badge bg-purple-lt">点击</span>
                                @else
                                    <span class="badge bg-secondary-lt">{{ $visit->event_type }}</span>
                                @endif
                            </td>
                            <td>
                                <code class="text-sm" title="{{ $visit->path }}">{{ Str::limit($visit->path, 35) }}</code>
                                @if($visit->event_label && $visit->event_type !== 'scroll_depth')
                                    <div class="text-secondary small">{{ Str::limit($visit->event_label, 25) }}</div>
                                @endif
                            </td>
                            <td>
                                <i class="{{ $visit->device_icon }} me-1"></i>
                                @if($visit->device_brand)
                                    <strong>{{ $visit->device_brand }}</strong>
                                @endif
                                <span class="text-secondary">{{ match($visit->device_type) { 'mobile' => '手机', 'tablet' => '平板', 'bot' => '机器人', default => '桌面' } }}</span>
                            </td>
                            <td>{{ $visit->browser_display }}</td>
                            <td>{{ $visit->os_display }}</td>
                            <td>
                                <code>{{ $visit->display_ip }}</code>
                                <a href="{{ route('admin.visitor-analytics.detail', ['search' => $visit->display_ip]) }}" class="ms-1" title="查看该 IP 所有记录">
                                    <i class="ti ti-search text-secondary" style="font-size: 0.7rem;"></i>
                                </a>
                            </td>
                            <td>
                                @if($visit->country || $visit->city)
                                    <span class="text-nowrap">
                                        @if($visit->country){{ $visit->country }}@endif
                                        @if($visit->city) {{ $visit->city }}@endif
                                    </span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $visit->isp ?? '-' }}</td>
                            <td>
                                @if($visit->duration_ms)
                                    @if($visit->duration_ms >= 60000)
                                        <span class="text-orange">{{ round($visit->duration_ms / 60000, 1) }}m</span>
                                    @elseif($visit->duration_ms >= 1000)
                                        {{ round($visit->duration_ms / 1000, 1) }}s
                                    @else
                                        {{ $visit->duration_ms }}ms
                                    @endif
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $visit->created_at?->format('m-d H:i:s') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center text-secondary">暂无记录</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($visits->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $visits->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
