@extends('layouts.admin')

@section('title', '定时任务管理')
@section('page-pretitle', '系统管理')
@section('page-title', '定时任务管理')

@section('page-actions')
<a href="{{ route('admin.schedule.logs') }}" class="btn btn-outline-primary">
    <i class="ti ti-history me-1"></i> 执行历史
</a>
<button type="button" class="btn btn-primary ms-2" data-action="check-schedule-status">
    <i class="ti ti-activity me-1"></i> 检查状态
</button>
@endsection

@section('content')
<div data-route-admin-schedule-status="{{ route('admin.schedule.status') }}" data-url-admin-schedule="{{ url('admin/schedule') }}">
<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-azure-lt avatar">
                            <i class="ti ti-clock"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['total_tasks'] }} 个任务</div>
                        <div class="text-secondary">已配置的任务总数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-green-lt avatar">
                            <i class="ti ti-player-play"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['enabled_tasks'] }} 个启用</div>
                        <div class="text-secondary">当前启用的任务数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-yellow-lt avatar">
                            <i class="ti ti-calendar-event"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ $stats['last_run'] ? $stats['last_run']->diffForHumans() : '从未' }}
                        </div>
                        <div class="text-secondary">上次成功执行</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-purple-lt avatar">
                            <i class="ti ti-repeat"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $stats['today_runs'] }} 次</div>
                        <div class="text-secondary">今日执行次数</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">定时任务列表</h3>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>任务</th>
                    <th>执行计划</th>
                    <th>类型</th>
                    <th>状态</th>
                    <th class="w-1">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $key => $task)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm bg-azure-lt me-2">
                                <i class="ti ti-terminal"></i>
                            </span>
                            <div>
                                <div class="font-weight-medium">{{ $task['description'] }}</div>
                                <div class="text-secondary small font-monospace">{{ $key }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-blue-lt me-2" title="Cron: {{ $task['cron'] }}">
                                <i class="ti ti-clock me-1"></i>{{ $task['schedule'] }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary-lt">{{ $task['type'] === 'command' ? '命令' : '闭包' }}</span>
                    </td>
                    <td>
                        @if($task['enabled'])
                            <span class="badge bg-success">启用</span>
                        @else
                            <span class="badge bg-secondary">禁用</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            <a href="{{ route('admin.schedule.show', $key) }}" class="btn btn-white btn-sm">
                                <i class="ti ti-eye me-1"></i> 详情
                            </a>
                            @can('schedule.run')
                            <button type="button" class="btn btn-white btn-sm text-primary" 
                                    data-action="run-task" data-task="{{ $key }}" {{ !$task['enabled'] ? 'disabled' : '' }}>
                                <i class="ti ti-player-play me-1"></i> 立即执行
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                        <i class="ti ti-inbox fs-2 mb-2 d-block"></i>
                        暂无定时任务配置
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Cron 配置说明</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4 class="alert-title">配置 Cron 任务</h4>
            <p>要确保定时任务正常执行，需要在服务器上配置 Cron 任务：</p>
        </div>
        <div class="bg-dark text-light p-3 rounded font-monospace small">
            <div class="mb-1"># 编辑 crontab 文件</div>
            <div class="text-success">crontab -e</div>
            <div class="mt-2 mb-1"># 添加以下行（每分钟执行一次）</div>
            <div class="text-warning">* * * * * cd /www/wwwroot/49.232.223.126 && php artisan schedule:run >> /dev/null 2>&1</div>
        </div>
        <div class="mt-3">
            <h5>当前 Cron 配置状态</h5>
            <p class="text-secondary" id="cronStatus">点击"检查状态"按钮查看</p>
        </div>
    </div>
</div>

{{-- Run Task Modal --}}
<div class="modal fade" id="runTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">执行任务</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="runTaskContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="mb-0">正在执行任务...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-schedule-index.js') }}"></script>
@endpush

