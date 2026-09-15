@extends('layouts.user')

@section('title', '技能评估')
@section('page-pretitle', '技能管理')
@section('page-title', '技能自评')

@section('page-actions')
    <a href="{{ route('user.skills.radar') }}" class="btn btn-outline-primary btn-sm">
        <i class="ti ti-chart-radar me-1"></i>技能雷达图
    </a>
    <a href="{{ route('user.skills.learn.index') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-route me-1"></i>生成学习路径
    </a>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible">
    <i class="ti ti-alert-circle me-2"></i>{{ session('error') }}
</div>
@endif

<div class="row">
    <div class="col-12 col-lg-8">
        @if($hardSkills->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-code me-2"></i>硬技能</h3>
            </div>
            <div class="card-body">
                @foreach($hardSkills as $skill)
                    @include('user.skills.partials.skill-item', ['skill' => $skill])
                @endforeach
            </div>
        </div>
        @endif

        @if($softSkills->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-heart me-2"></i>软技能</h3>
            </div>
            <div class="card-body">
                @foreach($softSkills as $skill)
                    @include('user.skills.partials.skill-item', ['skill' => $skill])
                @endforeach
            </div>
        </div>
        @endif

        @if($skills->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-skill fs-1 text-secondary"></i>
                <p class="text-secondary mt-2">还没有添加技能，开始你的第一次技能自评吧</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">添加/编辑技能</h3>
            </div>
            <div class="card-body">
                <form id="skillForm" action="{{ route('user.skills.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="skill_id" id="skillId">
                    <div class="mb-3">
                        <label class="form-label">技能名称 <span class="text-danger">*</span></label>
                        <input type="text" name="skill_name" id="skillName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">技能类别 <span class="text-danger">*</span></label>
                        <select name="skill_category" id="skillCategory" class="form-select" required>
                            <option value="hard">硬技能（编程/工具）</option>
                            <option value="soft">软技能（沟通/管理）</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">熟练度 <span class="text-danger">*</span></label>
                        <select name="proficiency" id="skillProficiency" class="form-select" required>
                            <option value="1">1 - 入门</option>
                            <option value="2">2 - 基础</option>
                            <option value="3">3 - 熟练</option>
                            <option value="4">4 - 精通</option>
                            <option value="5">5 - 专家</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">使用年限</label>
                            <input type="number" name="years_used" id="skillYears" class="form-control" min="0" max="50">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">最近使用</label>
                            <input type="date" name="last_used_at" id="skillLastUsed" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">证书/项目证明</label>
                        <textarea name="evidence" id="skillEvidence" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-plus me-1"></i>添加
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                            重置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
window.skillPageConfig = {
    storeUrl: '{{ route('user.skills.store') }}',
    updateUrlTemplate: '{{ route('user.skills.update', ':id') }}',
};
</script>
<script src="{{ asset('js/pages/skill-assessment.js') }}?v={{ @filemtime(public_path('js/pages/skill-assessment.js')) ?: time() }}"></script>
@endpush
