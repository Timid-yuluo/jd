@extends('layouts.admin')

@section('title', '系统运维')
@section('page-pretitle', '系统管理')
@section('page-title', '系统运维')

@section('content')
<div data-route-admin-system-ops-clear-cache="{{ route('admin.system-ops.clear-cache') }}" data-route-admin-system-ops-optimize="{{ route('admin.system-ops.optimize') }}" data-route-admin-system-ops-queue-action="{{ route('admin.system-ops.queue-action') }}" data-route-admin-system-ops-migrate="{{ route('admin.system-ops.migrate') }}">
<div class="row row-cards">
    {{-- 系统信息 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-server me-1"></i>系统信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">PHP 版本</div>
                        <div class="datagrid-content">{{ $systemInfo['php_version'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Laravel 版本</div>
                        <div class="datagrid-content">{{ $systemInfo['laravel_version'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">操作系统</div>
                        <div class="datagrid-content">{{ $systemInfo['os'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Web 服务器</div>
                        <div class="datagrid-content">{{ $systemInfo['server_software'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">时区</div>
                        <div class="datagrid-content">{{ $systemInfo['timezone'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">运行环境</div>
                        <div class="datagrid-content">
                            <span class="badge {{ $systemInfo['environment'] === 'production' ? 'bg-success' : 'bg-warning' }}">
                                {{ $systemInfo['environment'] }}
                            </span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Debug 模式</div>
                        <div class="datagrid-content">{{ $systemInfo['debug'] }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">内存限制</div>
                        <div class="datagrid-content">{{ $systemInfo['memory_limit'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 快捷操作 --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-bolt me-1"></i>快捷操作</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-warning w-100" data-action="clear-cache" data-cache-type="all">
                            <i class="ti ti-refresh me-1"></i>清除所有缓存
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-success w-100" data-action="optimize-system">
                            <i class="ti ti-rocket me-1"></i>优化系统
                        </button>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-action="clear-cache" data-cache-type="config">
                            <i class="ti ti-settings me-1"></i>配置缓存
                        </button>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-action="clear-cache" data-cache-type="route">
                            <i class="ti ti-route me-1"></i>路由缓存
                        </button>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-action="clear-cache" data-cache-type="view">
                            <i class="ti ti-layout me-1"></i>视图缓存
                        </button>
                    </div>
                </div>

                <hr class="my-3">

                <h5 class="mb-2">队列操作</h5>
                <div class="row g-2">
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-info w-100 btn-sm" data-action="queue-action" data-queue-action="restart">
                            <i class="ti ti-reload me-1"></i>重启队列
                        </button>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-warning w-100 btn-sm" data-action="queue-action" data-queue-action="retry">
                            <i class="ti ti-refresh me-1"></i>重试失败
                        </button>
                    </div>
                    <div class="col-4">
                        <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-action="queue-action" data-queue-action="flush">
                            <i class="ti ti-trash me-1"></i>清空失败
                        </button>
                    </div>
                </div>

                <hr class="my-3">

                <h5 class="mb-2">数据库</h5>
                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm" data-action="run-migration">
                            <i class="ti ti-database me-1"></i>运行迁移
                        </button>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.system-ops.logs') }}" class="btn btn-outline-dark w-100 btn-sm">
                            <i class="ti ti-file-text me-1"></i>查看日志
                        </a>
                    </div>
                    <div class="col-12">
                        <a href="{{ route('admin.system-ops.oauth-diagnostics') }}" class="btn btn-outline-primary w-100 btn-sm">
                            <i class="ti ti-shield-search me-1"></i>OAuth 诊断中心
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 队列状态 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-queue me-1"></i>队列状态</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <div class="text-secondary small">待处理任务</div>
                        <div class="h2 mb-0">{{ number_format($queueStats['pending']) }}</div>
                    </div>
                    <div class="ms-auto">
                        <div class="text-secondary small">失败任务</div>
                        <div class="h2 mb-0 {{ $queueStats['failed'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($queueStats['failed']) }}
                        </div>
                    </div>
                </div>

                <div class="progress mb-3">
                    @php
                        $total = $queueStats['pending'] + $queueStats['failed'];
                        $pendingPercent = $total > 0 ? ($queueStats['pending'] / $total) * 100 : 0;
                    @endphp
                    <div class="progress-bar bg-warning" style="width: {{ $pendingPercent }}%"></div>
                    <div class="progress-bar bg-danger" style="width: {{ 100 - $pendingPercent }}%"></div>
                </div>

                <div class="text-secondary small">
                    <span class="badge bg-warning me-1">&nbsp;</span>待处理
                    <span class="badge bg-danger ms-2 me-1">&nbsp;</span>失败
                </div>
            </div>
        </div>
    </div>

    {{-- 缓存信息 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-database me-1"></i>缓存配置</h3>
            </div>
            <div class="card-body">
                <div class="datagrid mb-3">
                    <div class="datagrid-item">
                        <div class="datagrid-title">当前驱动</div>
                        <div class="datagrid-content">
                            <span class="badge bg-primary-lt">{{ $cacheStats['driver'] }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">缓存前缀</div>
                        <div class="datagrid-content">{{ $cacheStats['prefix'] }}</div>
                    </div>
                </div>

                <div class="text-secondary small mb-2">可用存储:</div>
                <div class="badge-list">
                    @foreach ($cacheStats['stores'] as $store)
                        <span class="badge bg-secondary-lt">{{ $store }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- 数据库统计 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-table me-1"></i>数据库统计</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <div class="text-secondary small">总数据量</div>
                        <div class="h2 mb-0">{{ $dbStats['total_size'] }}</div>
                    </div>
                    <div class="ms-auto">
                        <div class="text-secondary small">总记录数</div>
                        <div class="h2 mb-0">{{ number_format($dbStats['total_rows']) }}</div>
                    </div>
                </div>

                <div class="text-secondary small mb-2">大表 TOP 5:</div>
                <div class="list-group list-group-flush">
                    @foreach (array_slice($dbStats['tables'], 0, 5) as $table)
                        <div class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate" style="max-width: 150px;">{{ $table['name'] }}</span>
                            <span class="badge bg-azure-lt">{{ number_format($table['rows']) }} 行</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 失败任务列表 --}}
@if(!empty($failedJobs))
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-alert-triangle me-1 text-danger"></i>失败任务（最近 {{ count($failedJobs) }} 条）</h3>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-warning" data-action="queue-action" data-queue-action="retry">
                <i class="ti ti-refresh me-1"></i>全部重试
            </button>
            <button type="button" class="btn btn-sm btn-danger ms-1" data-action="queue-action" data-queue-action="flush">
                <i class="ti ti-trash me-1"></i>清空全部
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>任务</th>
                    <th>队列</th>
                    <th>失败时间</th>
                    <th>异常信息</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($failedJobs as $job)
                <tr>
                    <td>{{ $job['id'] }}</td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 250px;" title="{{ $job['display_name'] }}">
                            {{ class_basename($job['display_name'] ?? $job['command'] ?? 'Unknown') }}
                        </span>
                    </td>
                    <td><span class="badge bg-secondary-lt">{{ $job['queue'] }}</span></td>
                    <td class="text-secondary">{{ \Carbon\Carbon::parse($job['failed_at'])->format('m-d H:i') }}</td>
                    <td>
                        <span class="text-truncate d-inline-block text-danger" style="max-width: 300px;" title="{{ $job['exception'] }}">
                            {{ Str::limit($job['exception'], 80) }}
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.system-ops.failed-jobs.retry', $job['id']) }}" class="d-inline" data-confirm="确认重试此任务？">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="重试"><i class="ti ti-refresh"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.system-ops.failed-jobs.forget', $job['id']) }}" class="d-inline" data-confirm="确认删除此任务？">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="删除"><i class="ti ti-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-system-ops-index.js') }}"></script>
@endpush
