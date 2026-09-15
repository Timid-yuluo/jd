@extends('layouts.user')

@section('title', $notification->title)
@section('page-pretitle', '消息详情')
@section('page-title', '通知详情')

@section('page-actions')
<div class="btn-list">
    <a href="{{ route('user.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>返回列表
    </a>
    @if($notification->hasFeedbackAction())
    <a href="{{ $notification->feedbackActionUrl() }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-arrow-up-right me-1"></i>跳转到对应反馈
    </a>
    @endif
    @if(!$notification->isRead())
    <form action="{{ route('user.notifications.mark-read', $notification) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="ti ti-check me-1"></i>标记为已读
        </button>
    </form>
    @endif
</div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <span class="badge bg-{{ $notification->type_color }}-lt text-{{ $notification->type_color }} me-2">
                        {{ $notification->type_label }}
                    </span>
                    @if($notification->isRead())
                    <span class="badge bg-secondary-lt">
                        <i class="ti ti-check me-1"></i>已读
                    </span>
                    @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-point-filled me-1"></i>未读
                    </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <h2 class="card-title mb-3">{{ $notification->title }}</h2>
                <div class="text-secondary small mb-4">
                    <i class="ti ti-clock me-1"></i>
                    发送时间：{{ $notification->created_at->format('Y-m-d H:i:s') }}
                    @if($notification->channel === 'email')
                    <span class="ms-3"><i class="ti ti-mail me-1"></i>通过邮件发送</span>
                    @elseif($notification->channel === 'both')
                    <span class="ms-3"><i class="ti ti-bell me-1"></i>站内 + 邮件</span>
                    @else
                    <span class="ms-3"><i class="ti ti-browser me-1"></i>站内通知</span>
                    @endif
                </div>
                <div class="notification-content">
                    {!! App\Support\HtmlPurifier::clean($notification->content) !!}
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary small">
                        @if($notification->read_at)
                        已读时间：{{ $notification->read_at->format('Y-m-d H:i:s') }}
                        @else
                        尚未阅读
                        @endif
                    </span>
                    <form action="{{ route('user.notifications.destroy', $notification) }}" method="POST" class="d-inline" data-app-confirm="确定要删除此通知吗？">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ti ti-trash me-1"></i>删除
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">通知信息</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-secondary small">通知类型</label>
                    <div class="fw-medium">
                        <span class="badge bg-{{ $notification->type_color }}-lt text-{{ $notification->type_color }}">
                            {{ $notification->type_label }}
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-secondary small">发送渠道</label>
                    <div class="fw-medium">
                        @if($notification->channel === 'site')
                        <i class="ti ti-browser me-1 text-blue"></i>站内通知
                        @elseif($notification->channel === 'email')
                        <i class="ti ti-mail me-1 text-green"></i>仅邮件
                        @else
                        <i class="ti ti-bell me-1 text-purple"></i>站内 + 邮件
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-secondary small">发送时间</label>
                    <div class="fw-medium">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                </div>
                <div class="mb-0">
                    <label class="text-secondary small">阅读状态</label>
                    <div class="fw-medium">
                        @if($notification->isRead())
                        <span class="text-success"><i class="ti ti-check me-1"></i>已读</span>
                        <div class="small text-secondary mt-1">{{ $notification->read_at->format('Y-m-d H:i') }}</div>
                        @else
                        <span class="text-danger"><i class="ti ti-point-filled me-1"></i>未读</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">快捷操作</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($notification->hasFeedbackAction())
                    <a href="{{ $notification->feedbackActionUrl() }}" class="btn btn-outline-primary">
                        <i class="ti ti-message-circle-2 me-1"></i>跳转到对应反馈
                    </a>
                    @endif
                    <a href="{{ route('user.notifications.index') }}" class="btn btn-outline-primary">
                        <i class="ti ti-list me-1"></i>查看全部通知
                    </a>
                    @if(!$notification->isRead())
                    <form action="{{ route('user.notifications.mark-read', $notification) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-check me-1"></i>标记为已读
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/user-notifications-show.css') }}">
@endpush
