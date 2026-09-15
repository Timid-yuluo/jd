@extends('layouts.user')

@section('title', '测评报告')
@section('page-pretitle', 'AI 测评')
@section('page-title', $assessment->result_label ?: '测评结果')

@section('page-actions')
    <a href="{{ route('user.assessments.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>返回
    </a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
</div>
@endif

<div class="card mb-3">
    <div class="card-body text-center py-4">
        @php
            $icon = match($assessment->test_type) {
                'mbti' => 'ti-brain',
                'holland' => 'ti-compass',
                'disc' => 'ti-chart-radar',
                default => 'ti-clipboard-check',
            };
            $color = match($assessment->test_type) {
                'mbti' => 'primary',
                'holland' => 'success',
                'disc' => 'warning',
                default => 'secondary',
            };
        @endphp
        <span class="avatar avatar-lg bg-{{ $color }}-lt text-{{ $color }} mb-3">
            <i class="ti {{ $icon }} fs-1"></i>
        </span>
        <h2 class="mb-1">{{ $assessment->result_code }}</h2>
        <p class="text-secondary mb-0">{{ $assessment->result_label }}</p>
        <p class="text-secondary small mt-2 mb-0">
            {{ $config['title'] ?? '' }} · {{ $assessment->created_at->format('Y-m-d H:i') }}
        </p>
    </div>
</div>

@php
    $dims = $assessment->dimensions ?? [];
    $analysis = $assessment->ai_analysis ?? [];
    $careers = $assessment->recommended_careers ?? [];
    $teamRoles = $assessment->team_roles ?? [];
@endphp

@if($assessment->test_type === 'mbti')
    @include('user.assessments.partials.mbti-chart', ['dims' => $dims])
@elseif($assessment->test_type === 'holland')
    @include('user.assessments.partials.holland-radar', ['dims' => $dims])
@elseif($assessment->test_type === 'disc')
    @include('user.assessments.partials.disc-wheel', ['dims' => $dims])
@endif

@if(!empty($analysis))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-user-circle me-2"></i>AI 个性化解读</h3>
    </div>
    <div class="card-body">
        @if(!empty($analysis['personality_description']))
        <p class="mb-3">{{ $analysis['personality_description'] }}</p>
        @endif

        <div class="row">
            @if(!empty($analysis['strengths']))
            <div class="col-12 col-md-6 mb-3">
                <h4 class="text-success"><i class="ti ti-arrow-up me-1"></i>优势</h4>
                <ul class="list-unstyled">
                    @foreach($analysis['strengths'] as $s)
                    <li class="mb-1"><i class="ti ti-check text-success me-2"></i>{{ $s }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(!empty($analysis['weaknesses']))
            <div class="col-12 col-md-6 mb-3">
                <h4 class="text-warning"><i class="ti ti-arrow-down me-1"></i>需注意</h4>
                <ul class="list-unstyled">
                    @foreach($analysis['weaknesses'] as $w)
                    <li class="mb-1"><i class="ti ti-alert text-warning me-2"></i>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        @if(!empty($analysis['development_suggestions']))
        <div class="alert alert-info">
            <strong><i class="ti ti-bulb me-1"></i>发展建议</strong>
            <ul class="mb-0 mt-1">
                @foreach($analysis['development_suggestions'] as $s)
                <li>{{ $s }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($analysis['career_path']))
        <div class="mt-3">
            <strong>职业发展路径：</strong>{{ $analysis['career_path'] }}
        </div>
        @endif
    </div>
</div>
@endif

@if(!empty($careers))
@include('user.assessments.partials.career-match', ['careers' => $careers])
@endif

@if(!empty($teamRoles))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-users me-2"></i>团队角色建议</h3>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($teamRoles as $role)
            <div class="col-12 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $role['role'] ?? '' }}</h5>
                        <p class="text-secondary mb-0">{{ $role['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('user.assessments.history') }}" class="btn btn-link">
        <i class="ti ti-history me-1"></i>查看历史记录
    </a>
</div>
@endsection
