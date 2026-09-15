@extends('layouts.user')

@section('title', '学习路径详情')
@section('page-pretitle', '技能管理')
@section('page-title', $path->target_job)

@section('page-actions')
    <a href="{{ route('user.skills.learn.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-plus me-1"></i>新分析
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
                <div class="text-secondary small">目标岗位</div>
                <div class="fw-bold">{{ $path->target_job }}</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">技能差距</div>
                <div class="fw-bold text-warning">{{ $path->gap_count }} 项</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">学习阶段</div>
                <div class="fw-bold">{{ $path->phase_count }} 个</div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="text-secondary small">预计时长</div>
                <div class="fw-bold text-primary">{{ $path->total_duration_weeks }} 周</div>
            </div>
        </div>
    </div>
</div>

@php
    $required = $path->required_skills ?? [];
    $gaps = $path->gap_analysis ?? [];
    $phases = $path->ai_path ?? [];
@endphp

@if(!empty($gaps))
@include('user.skills.partials.gap-chart', ['gaps' => $gaps])
@endif

@if(!empty($required))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-list-check me-2"></i>目标岗位要求技能</h3>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($required as $req)
            @php
                $impColor = match($req['importance'] ?? '') {
                    '必须' => 'danger',
                    '建议' => 'warning',
                    '加分' => 'success',
                    default => 'secondary',
                };
            @endphp
            <div class="col-12 col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span>{{ $req['name'] ?? '' }}</span>
                    <div>
                        <span class="badge bg-{{ $impColor }}-lt me-1">{{ $req['importance'] ?? '' }}</span>
                        <span class="text-secondary small">目标 {{ $req['target_level'] ?? '?' }}/5</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(!empty($phases))
@include('user.skills.partials.path-timeline', ['phases' => $phases])
@endif

@if(empty($required) && empty($gaps) && empty($phases))
<div class="card">
    <div class="card-body text-center py-4">
        <i class="ti ti-alert-circle fs-1 text-warning"></i>
        <p class="text-secondary mt-2">AI 分析结果为空</p>
        <a href="{{ route('user.skills.learn.index') }}" class="btn btn-outline-primary mt-2">重新分析</a>
    </div>
</div>
@endif
@endsection
