@extends('layouts.admin')

@section('title', '任务详情')
@section('page-pretitle', '定时任务管理')
@section('page-title', $taskData['description'])

@section('page-actions')
<a href="{{ route('admin.schedule.index') }}" class="btn btn-outline-primary">
    <i class="ti ti-arrow-left me-1"></i> 返回列表
</a>
@can('schedule.run')
<button type="button" class="btn btn-primary ms-2" data-action="run-task" data-task="{{ $task }}">
    <i class="ti ti-player-play me-1"></i> 立即执行
</button>
@endcan
@endsection

@section('content')
<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="h1 mb-0 me-3">{{ $stats['total_runs'] }}</div>
                    <div>
                        <div class="font-weight-medium">总执行次数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="h1 mb-0 me-3 text-success">{{ $stats['success_runs'] }}</div>
                    <div>
                        <div class="font-weight-medium">成功次数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="h1 mb-0 me-3 text-danger">{{ $stats['failed_runs'] }}</div>
                    <div>
                        <div class="font-weight-medium">失败次数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="h1 mb-0 me-3">{{ $stats['avg_duration'] ? round($stats['avg_duration']) : 0 }}ms</div>
                    <div>
                        <div class="font-weight-medium">平均耗时</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">执行历史</h3>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>执行时间</th>
                    <th>状态</th>
                    <th>耗时</th>
                    <th>触发方式</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{!! $log->status_badge !!}</td>
                    <td>{{ $log->formatted_duration }}</td>
                    <td>
                        @if($log->triggered_by)
                            <span class="badge bg-azure-lt">手动</span>
                        @else
                            <span class="badge bg-secondary-lt">自动</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-secondary">暂无执行记录</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
