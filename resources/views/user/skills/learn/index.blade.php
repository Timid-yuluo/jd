@extends('layouts.user')

@section('title', '学习路径')
@section('page-pretitle', '技能管理')
@section('page-title', 'AI 学习路径')

@section('page-actions')
    <a href="{{ route('user.skills.learn.history') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-history me-1"></i>历史路径
    </a>
@endsection

@section('content')
@if(session('error'))
<div class="alert alert-danger alert-dismissible">
    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
</div>
@endif

@if($activePath)
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="badge bg-success-lt mb-2">当前活跃</span>
                <h3 class="mb-1">{{ $activePath->target_job }}</h3>
                <p class="text-secondary mb-0">
                    共 {{ $activePath->phase_count }} 个阶段 · 预计 {{ $activePath->total_duration_weeks }} 周
                </p>
            </div>
            <a href="{{ route('user.skills.learn.show', $activePath) }}" class="btn btn-primary">
                查看路径<i class="ti ti-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-route me-2"></i>生成新的学习路径</h3>
    </div>
    <div class="card-body">
        @if($skillCount === 0)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            请先添加至少一项技能自评，才能进行差距分析
            <a href="{{ route('user.skills.index') }}" class="alert-link ms-2">去添加</a>
        </div>
        @else
        <p class="text-secondary mb-3">
            当前已自评 <strong>{{ $skillCount }}</strong> 项技能。输入目标岗位，AI 将分析你的技能差距并生成个性化学习路径。
        </p>
        <form action="{{ route('user.skills.learn.analyze') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">目标岗位 <span class="text-danger">*</span></label>
                <input type="text" name="target_job" class="form-control" required
                       value="{{ old('target_job') }}" placeholder="如：高级前端工程师">
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label">目标城市</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ old('city') }}" placeholder="如：北京">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label">经验要求</label>
                    <input type="text" name="experience_years" class="form-control"
                           value="{{ old('experience_years') }}" placeholder="如：3-5年">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-sparkles me-1"></i>开始 AI 分析
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
