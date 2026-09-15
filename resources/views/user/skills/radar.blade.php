@extends('layouts.user')

@section('title', '技能雷达图')
@section('page-pretitle', '技能管理')
@section('page-title', '技能雷达图')

@section('page-actions')
    <a href="{{ route('user.skills.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>返回
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value text-primary">{{ $avg_hard }}</div>
                    <div class="metric-label">硬技能平均</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value text-success">{{ $avg_soft }}</div>
                    <div class="metric-label">软技能平均</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="metric">
                    <div class="metric-value">{{ $total }}</div>
                    <div class="metric-label">技能总数</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @if(!empty($hard_skills))
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-code me-2"></i>硬技能分布</h3>
            </div>
            <div class="card-body">
                @foreach($hard_skills as $skill)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $skill['name'] }}</span>
                        <span class="text-secondary small">{{ $skill['proficiency'] }}/5</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: {{ $skill['proficiency'] * 20 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if(!empty($soft_skills))
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-heart me-2"></i>软技能分布</h3>
            </div>
            <div class="card-body">
                @foreach($soft_skills as $skill)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $skill['name'] }}</span>
                        <span class="text-secondary small">{{ $skill['proficiency'] }}/5</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: {{ $skill['proficiency'] * 20 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if(empty($hard_skills) && empty($soft_skills))
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-chart-radar fs-1 text-secondary"></i>
                <p class="text-secondary mt-2">暂无技能数据</p>
                <a href="{{ route('user.skills.index') }}" class="btn btn-outline-primary mt-2">添加技能</a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
