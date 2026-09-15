@extends('layouts.user')

@section('title', $event->title . ' - 平台动态')
@section('page-pretitle', $event->category)
@section('page-title', $event->title)

@section('meta')
<meta name="description" content="{{ $event->summary ?? Str::limit(strip_tags($event->content ?? ''), 160) }}">
@endsection

@php
$catIcon = ['公告' => 'speakerphone', '活动' => 'confetti', '更新' => 'stars', '洞察' => 'bulb'];
$catClass = ['公告' => 'announcement', '活动' => 'event', '更新' => 'update', '洞察' => 'insight'];
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-3 order-lg-2">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">事件信息</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-secondary d-block mb-1">分类</small>
                    <span class="timeline-tag tag-{{ $catClass[$event->category] ?? '' }}">{{ $event->category }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-secondary d-block mb-1">发布时间</small>
                    <div class="fw-medium small">{{ $event->published_at?->format('Y-m-d H:i') }}</div>
                </div>
                <div class="mb-0">
                    <small class="text-secondary d-block mb-1">浏览</small>
                    <div class="fw-medium small">{{ $event->view_count }} 次</div>
                </div>
            </div>
        </div>

        @if($latestEvents->isNotEmpty())
        <div class="card event-sidebar">
            <div class="card-header"><h3 class="card-title">最新动态</h3></div>
            <div class="list-group list-group-flush">
                @foreach($latestEvents as $latest)
                <a href="{{ route('public.events.show', $latest) }}" class="list-group-item list-group-item-action {{ $latest->id === $event->id ? 'active' : '' }}">
                    <div class="text-truncate small">{{ $latest->title }}</div>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-9 order-lg-1">
        <div class="event-detail">
            @if($event->cover_image)
            <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="event-detail-cover">
            @endif
            <div class="event-detail-body">
                <div class="event-detail-meta">
                    <span class="timeline-tag tag-{{ $catClass[$event->category] ?? '' }}">{{ $event->category }}</span>
                    @if($event->is_pinned)
                    <span class="badge bg-yellow-lt text-yellow"><i class="ti ti-pinned-filled me-1"></i>置顶</span>
                    @endif
                    <span class="text-secondary small ms-auto">
                        {{ $event->published_at?->format('Y-m-d H:i') }}
                        <span class="mx-2">·</span>
                        {{ $event->view_count }} 次浏览
                    </span>
                </div>
                <h1 class="event-detail-title">{{ $event->title }}</h1>
                @if($event->summary)
                <p class="event-detail-summary">{{ $event->summary }}</p>
                @endif
                <div class="event-detail-content">
                    {!! App\Support\HtmlPurifier::clean($event->content) !!}
                </div>
            </div>
            <div class="event-detail-footer">
                <a href="{{ route('public.events.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>返回动态列表
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/events-timeline.css') }}">
@endpush
