@extends('layouts.admin')

@section('title', '邮件发送日志')
@section('page-pretitle', '运维监控')
@section('page-title', '邮件发送日志')

@section('page-actions')
<a href="{{ route('admin.mail-config.index') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex">
    <i class="ti ti-mail-cog me-1"></i>邮件配置
</a>
@endsection

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-mail"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">总发送</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-success text-white avatar"><i class="ti ti-check"></i></span></div>
                    <div class="col"><div class="font-weight-medium text-success">{{ number_format($stats['sent']) }}</div><div class="text-secondary">成功</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-danger text-white avatar"><i class="ti ti-x"></i></span></div>
                    <div class="col"><div class="font-weight-medium text-danger">{{ number_format($stats['failed']) }}</div><div class="text-secondary">失败</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-warning text-white avatar"><i class="ti ti-clock"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['pending']) }}</div><div class="text-secondary">待发送</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-calendar"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['today']) }}</div><div class="text-secondary">今日发送</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-chart-pie"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ $stats['total'] > 0 ? round(($stats['sent'] / $stats['total']) * 100, 1) : 0 }}%</div><div class="text-secondary">成功率</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">邮件记录</h3>
        <div class="card-actions">
            <form method="GET" action="{{ route('admin.email-logs.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索邮箱/主题" value="{{ request('search') }}" style="min-width: 180px;">
                <select name="status" class="form-select form-select-sm" style="min-width: 100px;">
                    <option value="">所有状态</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>已发送</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>失败</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>待发送</option>
                </select>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="width: 140px;">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="width: 140px;">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-search me-1"></i>筛选</button>
                @if(request()->hasAny(['search','status','date_from','date_to']))
                    <a href="{{ route('admin.email-logs.index') }}" class="btn btn-sm btn-secondary">重置</a>
                @endif
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>收件人</th>
                    <th>主题</th>
                    <th>状态</th>
                    <th>发送时间</th>
                    <th style="width: 120px;">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-secondary">{{ $log->id }}</td>
                    <td>{{ $log->recipient_email }}</td>
                    <td>{{ Str::limit($log->subject, 50) }}</td>
                    <td>
                        @if($log->status == 'sent')
                            <span class="badge bg-success-lt">已发送</span>
                        @elseif($log->status == 'failed')
                            <span class="badge bg-danger-lt">失败</span>
                        @else
                            <span class="badge bg-warning-lt">待发送</span>
                        @endif
                    </td>
                    <td class="small">{{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i') : '-' }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('admin.email-logs.show', $log) }}" class="btn btn-sm btn-outline-info" title="详情"><i class="ti ti-eye"></i></a>
                            @if($log->status != 'sent')
                            <form action="{{ route('admin.email-logs.resend', $log) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="重发"><i class="ti ti-refresh"></i></button>
                            </form>
                            @endif
                            <form action="{{ route('admin.email-logs.destroy', $log) }}" method="POST" class="d-inline" data-confirm-submit="确定删除此邮件记录？">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="删除"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-secondary">暂无邮件记录</td>
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

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">快捷操作</h3>
    </div>
    <div class="card-body">
        <div class="btn-list">
            <form action="{{ route('admin.email-logs.resend-failed') }}" method="POST" class="d-inline" data-confirm-submit="确认批量重发所有失败邮件？">
                @csrf
                <button type="submit" class="btn btn-outline-warning"><i class="ti ti-refresh me-1"></i>批量重发失败邮件</button>
            </form>
            <form action="{{ route('admin.email-logs.clear') }}" method="POST" class="d-inline" data-confirm-submit="确认清理30天前的旧日志？">
                @csrf
                <button type="submit" class="btn btn-outline-secondary"><i class="ti ti-trash me-1"></i>清理旧日志</button>
            </form>
            <button type="button" class="btn btn-outline-primary" data-action="load-statistics"><i class="ti ti-chart-bar me-1"></i>查看统计图表</button>
        </div>
    </div>
</div>

<!-- 统计模态框 -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">邮件统计</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <canvas id="dailyChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-email-logs-index.js') }}"></script>
@endpush
