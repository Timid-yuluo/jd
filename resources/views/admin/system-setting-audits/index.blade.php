@extends('layouts.admin')

@section('title', '系统审计')
@section('page-pretitle', '系统监控')
@section('page-title', '网站设置审计日志')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">总审计记录</div>
                        <div class="h2 mb-0">{{ number_format($summary['total']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">今日变更</div>
                        <div class="h2 mb-0">{{ number_format($summary['today']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">涉及配置键</div>
                        <div class="h2 mb-0">{{ number_format($summary['unique_keys']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">审计明细</h3>
            </div>
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('admin.system-setting-audits.index') }}" class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">配置键</label>
                        <input type="text" name="setting_key" class="form-control" value="{{ $filters['setting_key'] ?? '' }}" placeholder="如 site_name">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">操作者 ID</label>
                        <input type="number" min="1" name="changed_by_user_id" class="form-control" value="{{ $filters['changed_by_user_id'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">开始日期</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">结束日期</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">筛选</button>
                        <a href="{{ route('admin.system-setting-audits.index') }}" class="btn btn-outline-secondary">重置</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>配置键</th>
                            <th>变更值</th>
                            <th>操作者</th>
                            <th>来源 IP</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td><span class="text-secondary">{{ $log->id }}</span></td>
                                <td><span class="badge bg-blue-lt">{{ $log->setting_key }}</span></td>
                                <td>
                                    <div class="small text-secondary">旧值</div>
                                    <div class="text-truncate" style="max-width: 320px;">{{ $log->old_value ?? 'NULL' }}</div>
                                    <div class="small text-secondary mt-1">新值</div>
                                    <div class="text-truncate" style="max-width: 320px;">{{ $log->new_value ?? 'NULL' }}</div>
                                </td>
                                <td>{{ $log->changedByUser?->name ?? '系统' }} <span class="text-secondary small">#{{ $log->changed_by_user_id ?? '-' }}</span></td>
                                <td class="text-secondary">{{ $log->ip_address ?? '-' }}</td>
                                <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary">暂无审计日志</td>
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
@endsection
