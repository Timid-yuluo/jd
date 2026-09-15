@extends('layouts.admin')

@section('title', '访问封禁')
@section('page-pretitle', '安全防护')
@section('page-title', '访问封禁管理')

@push('styles')
<style>
    .stat-card { transition: transform 0.15s; }
    .stat-card:hover { transform: translateY(-2px); }
</style>
@endpush

@section('content')
<div class="row row-cards">
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-secondary fs-2">{{ $stats['total_active'] }}</div>
                <div class="text-secondary small">活跃封禁</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-danger fs-2">{{ $stats['ip_bans'] }}</div>
                <div class="text-secondary small">IP 封禁</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-orange fs-2">{{ $stats['ip_range_bans'] }}</div>
                <div class="text-secondary small">IP 段封禁</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-warning fs-2">{{ $stats['device_bans'] }}</div>
                <div class="text-secondary small">设备封禁</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-info fs-2">{{ $stats['auto_bans_today'] }}</div>
                <div class="text-secondary small">今日自动封禁</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="card stat-card">
            <div class="card-body p-3 text-center">
                <div class="text-secondary fs-2">{{ $stats['total_expired'] }}</div>
                <div class="text-secondary small">已过期</div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">7日访问趋势</h3>
            </div>
            <div class="card-body">
                <div id="access-chart" style="height: 200px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">封禁规则列表</h3>
                <div class="card-actions">
                    <form id="batch-form" method="POST" action="{{ route('admin.access-bans.batch-destroy') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger me-2" id="batch-delete-btn" style="display:none;" onclick="return confirm('确认批量移除选中的封禁规则？')">批量移除</button>
                    </form>
                    @if($stats['total_expired'] > 0)
                    <form method="POST" action="{{ route('admin.access-bans.clear-expired') }}" class="d-inline" onsubmit="return confirm('确认清理所有过期封禁规则？')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary me-2">清理过期</button>
                    </form>
                    @endif
                    <form method="GET" action="{{ route('admin.access-bans.index') }}" class="d-inline-flex gap-2">
                        <select name="ban_type" class="form-select form-select-sm" style="min-width: 100px;">
                            <option value="">全部类型</option>
                            <option value="ip" {{ request('ban_type') === 'ip' ? 'selected' : '' }}>IP</option>
                            <option value="ip_range" {{ request('ban_type') === 'ip_range' ? 'selected' : '' }}>IP段</option>
                            <option value="device" {{ request('ban_type') === 'device' ? 'selected' : '' }}>设备</option>
                            <option value="browser" {{ request('ban_type') === 'browser' ? 'selected' : '' }}>浏览器</option>
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索值..." value="{{ request('search') }}" style="min-width: 160px;">
                        <button type="submit" class="btn btn-sm btn-primary">筛选</button>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>类型</th>
                            <th>值</th>
                            <th>严重性</th>
                            <th>原因</th>
                            <th>过期时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bans as $ban)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $ban->id }}" form="batch-form" class="ban-checkbox"></td>
                            <td>
                                @switch($ban->ban_type)
                                    @case('ip') <span class="badge bg-danger-lt">IP</span> @break
                                    @case('ip_range') <span class="badge bg-orange-lt">IP段</span> @break
                                    @case('device') <span class="badge bg-warning-lt">设备</span> @break
                                    @case('browser') <span class="badge bg-info-lt">浏览器</span> @break
                                    @default <span class="badge bg-secondary-lt">{{ $ban->ban_type }}</span>
                                @endswitch
                            </td>
                            <td><code class="text-break">{{ Str::limit($ban->ban_value, 32) }}</code></td>
                            <td>
                                @switch($ban->severity)
                                    @case('block') <span class="badge bg-red">阻止</span> @break
                                    @case('captcha') <span class="badge bg-yellow">验证码</span> @break
                                    @case('log') <span class="badge bg-secondary">记录</span> @break
                                    @default <span class="badge bg-secondary">{{ $ban->severity }}</span>
                                @endswitch
                            </td>
                            <td class="text-secondary">{{ Str::limit($ban->reason, 40) }}</td>
                            <td>
                                @if($ban->expires_at)
                                    @if($ban->isExpired())
                                        <span class="badge bg-secondary-lt">已过期</span>
                                    @else
                                        <span class="text-secondary">{{ $ban->expires_at->diffForHumans() }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-red-lt">永久</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.access-bans.destroy', $ban) }}" class="d-inline" onsubmit="return confirm('确认移除此封禁规则？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">移除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">暂无封禁规则</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $bans->withQueryString()->links() }}
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">添加封禁规则</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.access-bans.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">封禁类型</label>
                        <select name="ban_type" class="form-select" required id="ban-type-select">
                            <option value="ip">IP 地址</option>
                            <option value="ip_range">IP 段 (CIDR/通配符)</option>
                            <option value="device">设备指纹</option>
                            <option value="browser">浏览器签名</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="ban-value-label">封禁值</label>
                        <input type="text" name="ban_value" class="form-control" placeholder="如: 192.168.1.1" required id="ban-value-input">
                        <small class="text-secondary" id="ban-value-hint">输入 IP 地址</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">严重性</label>
                        <select name="severity" class="form-select" required>
                            <option value="block">阻止访问</option>
                            <option value="captcha">要求验证码</option>
                            <option value="log">仅记录</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">原因</label>
                        <input type="text" name="reason" class="form-control" placeholder="封禁原因">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">过期时间</label>
                        <input type="datetime-local" name="expires_at" class="form-control">
                        <small class="text-secondary">留空则永久封禁</small>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">添加封禁</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">最近异常访问</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentDenied as $log)
                <div class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @switch($log->action)
                                @case('denied') <span class="badge bg-red-lt">拒绝</span> @break
                                @case('banned') <span class="badge bg-orange-lt">封禁</span> @break
                                @case('suspicious') <span class="badge bg-purple-lt">可疑</span> @break
                                @case('hijack') <span class="badge bg-dark-lt">劫持</span> @break
                                @case('throttled') <span class="badge bg-yellow-lt">限流</span> @break
                                @default <span class="badge bg-secondary-lt">{{ $log->action }}</span>
                            @endswitch
                        </div>
                        <div class="col text-truncate">
                            <code>{{ $log->ip_address }}</code>
                            <div class="text-secondary small">{{ Str::limit($log->details, 50) }}</div>
                        </div>
                        <div class="col-auto text-secondary small">
                            {{ $log->accessed_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-secondary">暂无记录</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.ban-checkbox');
    var btn = document.getElementById('batch-delete-btn');
    var checked = 0;
    checkboxes.forEach(function(cb) {
        cb.checked = this.checked;
        if (cb.checked) checked++;
    }.bind(this));
    btn.style.display = checked > 0 ? 'inline-block' : 'none';
});

document.querySelectorAll('.ban-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var checked = document.querySelectorAll('.ban-checkbox:checked').length;
        document.getElementById('batch-delete-btn').style.display = checked > 0 ? 'inline-block' : 'none';
    });
});

var typeSelect = document.getElementById('ban-type-select');
var valueInput = document.getElementById('ban-value-input');
var valueHint = document.getElementById('ban-value-hint');
var hints = {
    ip: { placeholder: '192.168.1.1', hint: '输入 IP 地址' },
    ip_range: { placeholder: '192.168.1.0/24', hint: 'CIDR 格式如 10.0.0.0/8 或通配符 192.168.*.*' },
    device: { placeholder: '设备指纹哈希', hint: '从访问日志中复制设备指纹' },
    browser: { placeholder: '浏览器签名哈希', hint: '从访问日志中复制浏览器签名' },
};
typeSelect.addEventListener('change', function() {
    var info = hints[this.value] || { placeholder: '', hint: '' };
    valueInput.placeholder = info.placeholder;
    valueHint.textContent = info.hint;
});
</script>

@if(isset($chartData))
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
var options = {
    chart: { type: 'area', height: 200, toolbar: { show: false }, fontFamily: 'inherit' },
    series: [
        { name: '异常访问', data: {{ \Illuminate\Support\Js::from($chartData['denied']) }} },
        { name: '正常访问', data: {{ \Illuminate\Support\Js::from($chartData['access']) }} },
    ],
    xaxis: { categories: {{ \Illuminate\Support\Js::from($chartData['labels']) }} },
    colors: ['#d63939', '#206bc4'],
    stroke: { width: 2, curve: 'smooth' },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.6, opacityTo: 0.1 } },
    dataLabels: { enabled: false },
    yaxis: { labels: { style: { fontSize: '11px' } } },
    legend: { position: 'top', fontSize: '12px' },
    grid: { borderColor: '#f0f0f0' },
};
var chart = new ApexCharts(document.querySelector("#access-chart"), options);
chart.render();
</script>
@endif
@endpush
