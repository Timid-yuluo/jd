@extends('layouts.admin')

@section('title', '执行历史')
@section('page-pretitle', '定时任务管理')
@section('page-title', '执行历史')

@section('page-actions')
<a href="{{ route('admin.schedule.index') }}" class="btn btn-outline-primary">
    <i class="ti ti-arrow-left me-1"></i> 返回任务列表
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">筛选</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.schedule.logs') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="task" class="form-select">
                    <option value="">所有任务</option>
                    @foreach($tasks as $task)
                    <option value="{{ $task }}" {{ request('task') == $task ? 'selected' : '' }}>{{ $task }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">所有状态</option>
                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>成功</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>失败</option>
                    <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>运行中</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">筛选</button>
                <a href="{{ route('admin.schedule.logs') }}" class="btn btn-link">重置</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">历史记录</h3>
        <div class="card-actions">
            <form action="{{ route('admin.schedule.clear-logs') }}" method="POST" class="d-inline" data-app-confirm="确定要清理历史记录吗？">
                @csrf
                <input type="hidden" name="days" value="30">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="ti ti-trash me-1"></i> 清理30天前记录
                </button>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>任务</th>
                    <th>执行时间</th>
                    <th>状态</th>
                    <th>耗时</th>
                    <th>触发方式</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>
                        <div class="font-weight-medium">{{ $log->task_description }}</div>
                        <div class="text-secondary small">{{ $log->task_name }}</div>
                    </td>
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
                    <td colspan="5" class="text-center py-4 text-secondary">暂无执行记录</td>
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
