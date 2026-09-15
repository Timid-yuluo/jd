@extends('layouts.user')

@section('title', '谈判结果')
@section('page-pretitle', '薪资助手')
@section('page-title', '谈判策略报告')

@section('page-actions')
    <a href="{{ route('user.salary.negotiate') }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-plus me-1"></i>新谈判
    </a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">岗位</div>
                <div class="fw-bold">{{ $session->job_title }}</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">公司</div>
                <div class="fw-bold">{{ $session->company ?? '-' }}</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">城市</div>
                <div class="fw-bold">{{ $session->city ?? '-' }}</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">经验</div>
                <div class="fw-bold">{{ $session->experience_years ?? '-' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">当前薪资</div>
                <div class="fw-bold text-warning">{{ number_format($session->current_salary) }} 元/月</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">期望薪资</div>
                <div class="fw-bold text-success">{{ number_format($session->target_salary) }} 元/月</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-secondary small">涨幅</div>
                @php
                    $increase = $session->current_salary > 0
                        ? round(($session->target_salary - $session->current_salary) / $session->current_salary * 100, 1)
                        : 0;
                @endphp
                <div class="fw-bold {{ $increase > 30 ? 'text-danger' : 'text-primary' }}">+{{ $increase }}%</div>
            </div>
        </div>
    </div>
</div>

@if($session->ai_strategy)
    @include('user.salary.partials.strategy-card')
@else
<div class="card">
    <div class="card-body text-center py-4">
        <i class="ti ti-alert-circle fs-1 text-warning"></i>
        <p class="text-secondary mt-2">AI 分析结果为空，请重新生成</p>
        <a href="{{ route('user.salary.negotiate') }}" class="btn btn-outline-primary mt-2">重新生成</a>
    </div>
</div>
@endif

@if($session->ai_dialogue)
    @include('user.salary.partials.dialogue-card')
@endif

<div class="mt-3">
    <a href="{{ route('user.salary.negotiate.history') }}" class="btn btn-link">
        <i class="ti ti-history me-1"></i>查看历史记录
    </a>
</div>
@endsection
