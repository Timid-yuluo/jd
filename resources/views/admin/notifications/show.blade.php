@extends('layouts.admin')

@section('title', '通知详情')
@section('page-pretitle', '通知中心')
@section('page-title', '通知详情')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $notification->title }}</h3>
                <div class="card-actions">
                    @if (!$notification->isSent())
                        <a href="{{ route('admin.notifications.edit', $notification) }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-edit me-1"></i>编辑
                        </a>
                        <form action="{{ route('admin.notifications.send', $notification) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" data-app-confirm='确定要发送此通知吗？'>
                                <i class="ti ti-send me-1"></i>发送
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid mb-4">
                    <div class="datagrid-item">
                        <div class="datagrid-title">通知类型</div>
                        <div class="datagrid-content">
                            <span class="badge bg-{{ $notification->type_color }}-lt text-{{ $notification->type_color }}">
                                {{ $notification->type_label }}
                            </span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">发送渠道</div>
                        <div class="datagrid-content">
                            @if($notification->channel === 'site')
                                <span class="badge bg-blue-lt text-blue"><i class="ti ti-browser me-1"></i>站内</span>
                            @elseif($notification->channel === 'email')
                                <span class="badge bg-green-lt text-green"><i class="ti ti-mail me-1"></i>邮件</span>
                            @else
                                <span class="badge bg-purple-lt text-purple"><i class="ti ti-bell me-1"></i>双渠道</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">发送状态</div>
                        <div class="datagrid-content">
                            @if ($notification->isSent())
                                <span class="badge bg-success-lt text-success">
                                    <i class="ti ti-check me-1"></i>已发送
                                </span>
                            @elseif ($notification->scheduled_at && $notification->scheduled_at > now())
                                <span class="badge bg-info-lt text-info">
                                    <i class="ti ti-clock me-1"></i>定时发送
                                </span>
                            @else
                                <span class="badge bg-warning-lt text-warning">
                                    <i class="ti ti-pencil me-1"></i>草稿
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">目标用户</div>
                        <div class="datagrid-content">{{ $notification->target_type_label }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">邮件模板</div>
                        <div class="datagrid-content">
                            @if($notification->emailTemplate)
                                <a href="{{ route('admin.notifications.email-templates.edit', $notification->emailTemplate) }}">
                                    {{ $notification->emailTemplate->name }}
                                </a>
                            @elseif($notification->channel !== 'site')
                                <span class="text-secondary">默认模板</span>
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">阅读数</div>
                        <div class="datagrid-content">{{ number_format($userNotificationCount) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">邮件发送</div>
                        <div class="datagrid-content">
                            @if($emailStats['total'] > 0)
                                <span class="text-success">{{ $emailStats['sent'] }} 成功</span>
                                @if($emailStats['failed'] > 0)
                                    <span class="text-danger ms-1">{{ $emailStats['failed'] }} 失败</span>
                                @endif
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $notification->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">发送时间</div>
                        <div class="datagrid-content">
                            @if($notification->scheduled_at && !$notification->isSent())
                                <span class="text-info">{{ $notification->scheduled_at->format('Y-m-d H:i:s') }} (定时)</span>
                            @else
                                {{ $notification->sent_at ? $notification->sent_at->format('Y-m-d H:i:s') : '-' }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">通知内容</h4>
                    </div>
                    <div class="card-body">
                        <div class="markdown-content">
                            {!! App\Support\HtmlPurifier::clean($notification->content) !!}
                        </div>
                    </div>
                </div>

                @if($emailStats['total'] > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title">邮件发送记录</h4>
                        <div class="card-actions">
                            <span class="text-secondary small">
                                共 {{ $emailStats['total'] }} 封邮件
                            </span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>收件人</th>
                                    <th>邮件主题</th>
                                    <th>状态</th>
                                    <th>发送时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emailLogs as $log)
                                <tr>
                                    <td>
                                        @if($log->user)
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-xs bg-primary-lt text-primary me-2">
                                                    {{ mb_substr($log->user->name, 0, 1) }}
                                                </span>
                                                <div>
                                                    <div class="font-weight-medium">{{ $log->user->name }}</div>
                                                    <div class="text-secondary small">{{ $log->recipient_email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            {{ $log->recipient_email }}
                                        @endif
                                    </td>
                                    <td class="text-truncate" style="max-width: 250px;">{{ $log->subject }}</td>
                                    <td>
                                        @if($log->status === 'sent')
                                            <span class="badge bg-success-lt text-success">
                                                <i class="ti ti-check me-1"></i>成功
                                            </span>
                                        @elseif($log->status === 'failed')
                                            <span class="badge bg-danger-lt text-danger" title="{{ $log->error_message }}">
                                                <i class="ti ti-x me-1"></i>失败
                                            </span>
                                        @else
                                            <span class="badge bg-warning-lt text-warning">
                                                <i class="ti ti-clock me-1"></i>发送中
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->sent_at)
                                            {{ $log->sent_at->format('Y-m-d H:i:s') }}
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">发送者信息</h3>
            </div>
            <div class="card-body">
                @if ($notification->sender)
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md bg-primary-lt text-primary me-3">
                            {{ mb_substr($notification->sender->name, 0, 1) }}
                        </span>
                        <div>
                            <div class="font-weight-medium">{{ $notification->sender->name }}</div>
                            <div class="text-secondary small">{{ $notification->sender->email }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-secondary">系统</div>
                @endif
            </div>
        </div>

        @if ($notification->target_type === 'roles' && !empty($notification->target_roles))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">目标角色</h3>
            </div>
            <div class="card-body">
                <div class="badge-list">
                    @foreach ($notification->target_roles as $role)
                        <span class="badge bg-secondary-lt">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if ($notification->target_type === 'users' && !empty($notification->target_users))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">目标用户 ({{ count($notification->target_users) }})</h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @php
                        $targetUsers = \App\Models\User::whereIn('id', $notification->target_users)->get();
                    @endphp
                    @foreach ($targetUsers as $user)
                        <div class="list-group-item d-flex align-items-center">
                            <span class="avatar avatar-sm bg-primary-lt text-primary me-2">
                                {{ mb_substr($user->name, 0, 1) }}
                            </span>
                            <div>
                                <div class="font-weight-medium">{{ $user->name }}</div>
                                <div class="text-secondary small">{{ $user->email }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
