@extends('layouts.admin')

@section('title', '通知中心')
@section('page-pretitle', '运营管理')
@section('page-title', '通知中心')

@section('page-actions')
<a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建通知
</a>
@endsection

@section('content')
<div class="row row-cards">
        {{-- 统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary me-3">
                                <i class="ti ti-bell fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">总通知数</div>
                                <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-success-lt text-success me-3">
                                <i class="ti ti-send fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">已发送</div>
                                <div class="h2 mb-0 text-success">{{ number_format($stats['sent']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-warning-lt text-warning me-3">
                                <i class="ti ti-pencil fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">草稿</div>
                                <div class="h2 mb-0 text-warning">{{ number_format($stats['draft']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-info-lt text-info me-3">
                                <i class="ti ti-eye fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">总阅读数</div>
                                <div class="h2 mb-0 text-info">{{ number_format($stats['total_read']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 通知列表 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">通知列表</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.notifications.email-templates') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-mail me-1"></i>邮件模板
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>通知标题</th>
                            <th>类型</th>
                            <th>渠道</th>
                            <th>目标用户</th>
                            <th>状态</th>
                            <th>发送时间</th>
                            <th class="w-1">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications as $notification)
                        <tr>
                            <td>
                                <div class="font-weight-medium">{{ $notification->title }}</div>
                                <div class="text-secondary text-truncate" style="max-width: 300px;">
                                    {{ Str::limit(strip_tags($notification->content), 60) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $notification->type_color }}-lt text-{{ $notification->type_color }}">
                                    {{ $notification->type_label }}
                                </span>
                            </td>
                            <td>
                                @if($notification->channel === 'site')
                                    <span class="badge bg-blue-lt text-blue"><i class="ti ti-browser me-1"></i>站内</span>
                                @elseif($notification->channel === 'email')
                                    <span class="badge bg-green-lt text-green"><i class="ti ti-mail me-1"></i>邮件</span>
                                @else
                                    <span class="badge bg-purple-lt text-purple"><i class="ti ti-bell me-1"></i>双渠道</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt">{{ $notification->target_type_label }}</span>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                @if ($notification->isSent())
                                    <span class="text-success">{{ $notification->sent_at->format('Y-m-d H:i') }}</span>
                                @elseif ($notification->scheduled_at)
                                    <span class="text-info"><i class="ti ti-clock me-1"></i>{{ $notification->scheduled_at->format('Y-m-d H:i') }}</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('admin.notifications.show', $notification) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @if (!$notification->isSent())
                                        <a href="{{ route('admin.notifications.edit', $notification) }}" class="btn btn-sm btn-outline-info">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.notifications.send', $notification) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-app-confirm='确定要发送此通知吗？'>
                                                <i class="ti ti-send"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="d-inline" data-app-confirm='确定要删除此通知吗？'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">暂无通知记录</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($notifications->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $notifications->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
