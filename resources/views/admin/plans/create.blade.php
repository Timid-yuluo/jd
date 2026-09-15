@extends('layouts.admin')

@section('title', '新建套餐')
@section('page-pretitle', '会员管理')
@section('page-title', '新建套餐')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf
            <div class="card">
                <div class="card-header"><h3 class="card-title">基本信息</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">套餐标识 (slug)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required placeholder="如：basic">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">套餐名称</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="如：基础版">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">月价（分）</label>
                            <input type="number" name="price_monthly" class="form-control" value="{{ old('price_monthly', 0) }}" min="0" required>
                            <div class="form-hint">例如 2900 = ¥29.00</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">年价（分）</label>
                            <input type="number" name="price_yearly" class="form-control" value="{{ old('price_yearly', 0) }}" min="0" required>
                            <div class="form-hint">例如 26800 = ¥268.00/年</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">状态</label>
                            <label class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span class="form-check-label">启用</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">配额设置（次/月，-1=不限）</h3></div>
                <div class="card-body">
                    @php
                        $quotaFields = [
                            'optimize_full' => '简历优化',
                            'optimize_section' => '分段优化',
                            'ats_score' => 'ATS评分',
                            'keywords_extract' => '关键词提取',
                            'import_document' => 'AI导入简历',
                            'interview_sessions' => 'AI面试',
                            'interview_evaluation' => '面试评估',
                            'job_match' => '岗位匹配',
                            'match_analysis' => '匹配分析',
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach($quotaFields as $key => $label)
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-label">{{ $label }}</label>
                                <input type="number" name="quotas[{{ $key }}]" class="form-control"
                                    value="{{ old("quotas.$key", 0) }}" min="-1" required>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">特色权益（可选）</h3></div>
                <div class="card-body">
                    <div id="features-container">
                        @foreach(old('features', []) as $i => $feature)
                            <div class="input-group mb-2">
                                <input type="text" name="features[]" class="form-control" value="{{ $feature }}" placeholder="如：导出无水印">
                                <button type="button" class="btn btn-outline-danger" data-action="remove-group" data-remove-target=".input-group">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="add-feature">
                        <i class="ti ti-plus me-1"></i>添加权益
                    </button>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">创建套餐</button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

@section('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
function addFeature() {
    const container = document.getElementById('features-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = '<input type="text" name="features[]" class="form-control" placeholder="如：导出无水印">' +
        '<button type="button" class="btn btn-outline-danger" data-action="remove-group" data-remove-target=".input-group"><i class="ti ti-x"></i></button>';
    container.appendChild(div);
}
</script>
@endsection
@endsection
