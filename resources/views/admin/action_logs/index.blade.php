@extends('admin.layouts.app')

@section('title', '操作日志')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">操作日志</h5>
                    <form method="GET" action="{{ route('admin.action-logs.index') }}" class="d-flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="搜索操作..." style="width:200px;">
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-search"></i></button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户</th>
                                    <th>操作</th>
                                    <th>路径</th>
                                    <th>方法</th>
                                    <th>IP</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $log->user?->name ?? '-' }}</td>
                                    <td><code>{{ $log->action }}</code></td>
                                    <td>{{ $log->path ?? '-' }}</td>
                                    <td>{{ $log->method ?? '-' }}</td>
                                    <td>{{ $log->ip ?? '-' }}</td>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">暂无日志</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $logs->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
