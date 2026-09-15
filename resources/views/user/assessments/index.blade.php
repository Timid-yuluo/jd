@extends('layouts.user')

@section('title', '职业测评')
@section('page-pretitle', 'AI 测评中心')
@section('page-title', '职业测评中心')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
</div>
@endif

<div class="row row-cards">
    @foreach($types as $typeInfo)
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="mb-3">
                    @php
                        $icon = match($typeInfo['type']) {
                            'mbti' => 'ti-brain',
                            'holland' => 'ti-compass',
                            'disc' => 'ti-chart-radar',
                            default => 'ti-clipboard-check',
                        };
                        $color = match($typeInfo['type']) {
                            'mbti' => 'primary',
                            'holland' => 'success',
                            'disc' => 'warning',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="avatar bg-{{ $color }}-lt text-{{ $color }}-icon">
                        <i class="ti {{ $icon }} fs-2"></i>
                    </span>
                </div>
                <h3 class="card-title">{{ $typeInfo['title'] }}</h3>
                <p class="text-secondary flex-grow-1">{{ $typeInfo['description'] }}</p>
                <div class="d-flex align-items-center text-secondary small mb-3">
                    <i class="ti ti-clock me-1"></i>约 {{ $typeInfo['estimated_minutes'] }} 分钟
                </div>
                <a href="{{ route('user.assessments.start', $typeInfo['type']) }}" class="btn btn-{{ $color }} w-100">
                    开始测评
                    <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mt-4">
    <div class="card-body d-flex align-items-center justify-content-between">
        <div>
            <h3 class="card-title mb-1">查看历史测评</h3>
            <p class="text-secondary">回顾你过去的测评结果，对比成长变化</p>
        </div>
        <a href="{{ route('user.assessments.history') }}" class="btn btn-outline-secondary">
            <i class="ti ti-history me-1"></i>历史记录
        </a>
    </div>
</div>
@endsection
