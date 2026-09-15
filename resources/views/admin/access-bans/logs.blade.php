@extends('layouts.admin')

@section('title', '访问日志')
@section('page-pretitle', '安全防护')
@section('page-title', '后台访问日志')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">后台访问日志</h3>
                <div class="card-actions">
                    <form method="GET" action="{{ route('admin.access-bans.logs') }}" class="d-flex gap-2">
                        <select name="action" class="form-select form-select-sm" style="min-width: 120px;">
                            <option value="">全部状态</option>
                            <option value="access" {{ request('action') === 'access' ? 'selected' : '' }}>正常访问</option>
                            <option value="denied" {{ request('action') === 'denied' ? 'selected' : '' }}>拒绝</option>
                            <option value="banned" {{ request('action') === 'banned' ? 'selected' : '' }}>封禁拦截</option>
                            <option value="suspicious" {{ request('action') === 'suspicious' ? 'selected' : '' }}>可疑</option>
                        </select>
                        <input type="text" name="ip" class="form-control form-control-sm" placeholder="IP地址" value="{{ request('ip') }}" style="min-width: 140px;">
                        <button type="submit" class="btn btn-sm btn-primary">筛选</button>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>状态</th>
                            <th>用户</th>
                            <th>IP</th>
                            <th>设备指纹</th>
                            <th>路径</th>
                            <th>详情</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                @switch($log->action)
                                    @case('access') <span class="badge bg-green-lt">正常</span> @break
                                    @case('denied') <span class="badge bg-red-lt">拒绝</span> @break
                                    @case('banned') <span class="badge bg-orange-lt">封禁</span> @break
                                    @case('suspicious') <span class="badge bg-purple-lt">可疑</span> @break
                                    @default <span class="badge bg-secondary-lt">{{ $log->action }}</span>
                                @endswitch
                            </td>
                            <td>
                                @if($log->user)
                                    {{ $log->user->name }}
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td><code class="small">{{ Str::limit($log->device_fingerprint, 16) }}</code></td>
                            <td class="text-break">{{ Str::limit($log->path, 40) }}</td>
                            <td class="text-secondary">{{ Str::limit($log->details, 50) }}</td>
                            <td class="text-secondary">{{ $log->accessed_at?->format('m-d H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">暂无访问日志</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                {{ $logs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
