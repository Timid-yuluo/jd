@extends('layouts.admin')

@section('title', '邮件详情')
@section('page-pretitle', '运维监控')
@section('page-title', '邮件详情 #' . $emailLog->id)

@section('content')
<div data-route-admin-email-logs-resend-0="{{ route('admin.email-logs.resend', $emailLog) }}" data-route-admin-email-logs-destroy-1="{{ route('admin.email-logs.destroy', $emailLog) }}" data-route-admin-email-logs-index="{{ route('admin.email-logs.index') }}">
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">邮件信息</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.email-logs.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>返回</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">收件人</div>
                        <div class="datagrid-content">{{ $emailLog->recipient_email }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">邮件主题</div>
                        <div class="datagrid-content">{{ $emailLog->subject }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">发送状态</div>
                        <div class="datagrid-content">
                            @if($emailLog->status == 'sent')
                                <span class="badge bg-success-lt">已发送</span>
                            @elseif($emailLog->status == 'failed')
                                <span class="badge bg-danger-lt">发送失败</span>
                            @else
                                <span class="badge bg-warning-lt">待发送</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">发送时间</div>
                        <div class="datagrid-content">{{ $emailLog->sent_at ? $emailLog->sent_at->format('Y-m-d H:i:s') : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $emailLog->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($emailLog->status == 'failed' && $emailLog->error_message)
        <div class="card alert-card border-danger">
            <div class="card-header">
                <h3 class="card-title text-danger"><i class="ti ti-alert-triangle me-1"></i>错误信息</h3>
            </div>
            <div class="card-body">
                <pre class="mb-0 text-danger small">{{ $emailLog->error_message }}</pre>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">邮件内容</h3>
            </div>
            <div class="card-body">
                @if($emailLog->content)
                <div class="border rounded p-3 bg-light">
                    {!! App\Support\HtmlPurifier::clean($emailLog->content) !!}
                </div>
                @else
                <div class="text-secondary text-center py-4">暂无内容记录</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @if($emailLog->user || $emailLog->adminNotification)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">关联信息</h3>
            </div>
            <div class="list-group list-group-flush">
                @if($emailLog->user)
                <div class="list-group-item">
                    <div class="text-secondary small">收件人用户</div>
                    <a href="{{ route('admin.users.show', $emailLog->user) }}">{{ $emailLog->user->name }}</a>
                    <div class="text-secondary small">{{ $emailLog->user->email }}</div>
                </div>
                @endif
                @if($emailLog->adminNotification)
                <div class="list-group-item">
                    <div class="text-secondary small">关联通知</div>
                    <a href="{{ route('admin.notifications.show', $emailLog->adminNotification) }}">{{ $emailLog->adminNotification->title }}</a>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">快捷操作</h3>
            </div>
            <div class="list-group list-group-flush">
                @if($emailLog->status != 'sent')
                <button class="list-group-item list-group-item-action text-warning" data-action="resend">
                    <i class="ti ti-refresh me-2"></i>重新发送
                </button>
                @endif
                <button class="list-group-item list-group-item-action text-danger" data-action="delete-log">
                    <i class="ti ti-trash me-2"></i>删除记录
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-email-logs-show.js') }}"></script>
@endpush
