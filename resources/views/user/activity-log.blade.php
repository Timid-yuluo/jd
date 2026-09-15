@extends('user.layouts.app')

@section('title', '操作日志')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">操作日志</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>操作</th>
                                    <th>描述</th>
                                    <th>IP</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td><code>{{ $log->action }}</code></td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">暂无操作日志</td></tr>
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
