@extends('layouts.user')

@section('title', '我的通知')
@section('page-pretitle', '消息中心')
@section('page-title', '我的通知')

@section('page-actions')
@if($stats['unread'] > 0)
<form action="{{ route('user.notifications.mark-all-read') }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-checks me-1"></i>全部标记为已读
    </button>
</form>
@endif
@endsection

@section('content')
<div class="row row-cards">
    {{-- 统计卡片 --}}
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary-lt text-primary me-3">
                                <i class="ti ti-bell fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">全部通知</div>
                                <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-danger-lt text-danger me-3">
                                <i class="ti ti-bell-ringing fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">未读通知</div>
                                <div class="h2 mb-0 text-danger">{{ number_format($stats['unread']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-success-lt text-success me-3">
                                <i class="ti ti-checks fs-2"></i>
                            </div>
                            <div>
                                <div class="text-secondary">已读通知</div>
                                <div class="h2 mb-0 text-success">{{ number_format($stats['read']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 筛选栏 --}}
    <div class="col-12">
        <div class="card card-sm mb-3">
            <div class="card-body">
                <form action="{{ route('user.notifications.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <a href="{{ route('user.notifications.index', ['type' => $type]) }}" class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                全部
                            </a>
                            <a href="{{ route('user.notifications.index', ['filter' => 'unread', 'type' => $type]) }}" class="btn btn-sm {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary' }}">
                                未读 @if($stats['unread'] > 0)<span class="badge bg-danger ms-1">{{ $stats['unread'] }}</span>@endif
                            </a>
                            <a href="{{ route('user.notifications.index', ['filter' => 'read', 'type' => $type]) }}" class="btn btn-sm {{ $filter === 'read' ? 'btn-primary' : 'btn-outline-primary' }}">
                                已读
                            </a>
                        </div>
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-select form-select-sm" data-auto-submit>
                            <option value="">所有类型</option>
                            @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($filter !== 'all' || $type)
                    <div class="col-auto">
                        <a href="{{ route('user.notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-x"></i> 清除筛选
                        </a>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- 通知列表 --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">通知列表</h3>
                <div class="card-actions">
                    <span class="text-secondary small">
                        @if($filter === 'unread' && $stats['unread'] > 0)
                        {{ $stats['unread'] }} 条未读
                        @elseif($filter === 'read' && $stats['read'] > 0)
                        {{ $stats['read'] }} 条已读
                        @elseif($stats['unread'] > 0)
                        共 {{ $stats['total'] }} 条，{{ $stats['unread'] }} 条未读
                        @else
                        共 {{ $stats['total'] }} 条通知
                        @endif
                    </span>
                </div>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                <div class="list-group-item {{ $notification->isRead() ? '' : 'bg-blue-lt' }}">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @if($notification->isRead())
                            <span class="badge bg-secondary-lt">
                                <i class="ti ti-check"></i>
                            </span>
                            @else
                            <span class="badge bg-danger-lt">
                                <i class="ti ti-point-filled"></i>
                            </span>
                            @endif
                        </div>
                        <div class="col">
                            <a href="{{ route('user.notifications.show', $notification) }}" class="text-reset d-block">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <h4 class="mb-0 {{ $notification->isRead() ? 'fw-normal' : 'fw-bold' }}">
                                        {{ $notification->title }}
                                    </h4>
                                    <small class="text-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 text-secondary text-truncate" style="max-width: 600px;">
                                    {{ strip_tags($notification->content) }}
                                </p>
                                <div class="mt-2">
                                    <span class="badge bg-{{ $notification->type_color }}-lt text-{{ $notification->type_color }} me-2">
                                        {{ $notification->type_label }}
                                    </span>
                                    @if($notification->hasFeedbackAction())
                                    <span class="badge bg-blue-lt text-blue me-2">
                                        <i class="ti ti-message-circle-2 me-1"></i>可查看反馈
                                    </span>
                                    @endif
                                    @if($notification->channel === 'email')
                                    <span class="badge bg-green-lt text-green">
                                        <i class="ti ti-mail me-1"></i>邮件
                                    </span>
                                    @elseif($notification->channel === 'both')
                                    <span class="badge bg-purple-lt text-purple">
                                        <i class="ti ti-bell me-1"></i>站内+邮件
                                    </span>
                                    @endif
                                </div>
                            </a>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-ghost-secondary" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('user.notifications.show', $notification) }}" class="dropdown-item">
                                        <i class="ti ti-eye me-2"></i>查看详情
                                    </a>
                                    @if($notification->hasFeedbackAction())
                                    <a href="{{ $notification->feedbackActionUrl() }}" class="dropdown-item">
                                        <i class="ti ti-arrow-up-right me-2"></i>查看反馈
                                    </a>
                                    @endif
                                    @if(!$notification->isRead())
                                    <form action="{{ route('user.notifications.mark-read', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="ti ti-check me-2"></i>标记为已读
                                        </button>
                                    </form>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('user.notifications.destroy', $notification) }}" method="POST" class="d-inline" data-app-confirm="确定要删除此通知吗？">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="ti ti-trash me-2"></i>删除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center py-5">
                    <div class="empty">
                        <div class="empty-icon">
                            <i class="ti ti-bell-off" style="font-size: 3rem;"></i>
                        </div>
                        <p class="empty-title h3">暂无通知</p>
                        <p class="empty-subtitle text-secondary">
                            您还没有收到任何通知
                        </p>
                    </div>
                </div>
                @endforelse
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
