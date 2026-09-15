@extends('layouts.admin')

@section('title', '新建赛道')
@section('page-pretitle', '校招赛道管理')
@section('page-title', '新建赛道')

@section('content')
<form action="{{ route('admin.career-tracks.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">基本信息</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">赛道名称</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">赛道标识</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" maxlength="50" required placeholder="如 backend">
                        <div class="form-hint">英文标识，用于关联Prompt策略，创建后不可轻易修改</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">分类</label>
                        <select name="category" class="form-select" required>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">图标</label>
                        <input type="text" name="icon" class="form-control" value="{{ old('icon') }}" maxlength="50" placeholder="如 ti-server">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">主题色</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color', '#206bc4') }}" maxlength="20" placeholder="如 #206bc4">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">关联Prompt策略</label>
                        <select name="prompt_strategy_key" class="form-select">
                            <option value="">不关联</option>
                            @foreach($strategyKeys as $key)
                                <option value="{{ $key }}" {{ old('prompt_strategy_key') === $key ? 'selected' : '' }}>{{ $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">状态</label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="is_active" value="1" class="form-selectgroup-input" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label">启用</span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="is_active" value="0" class="form-selectgroup-input" {{ !old('is_active', 1) ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label">禁用</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">优化侧重点</h3></div>
                <div class="card-body">
                    <div id="focus-container">
                        @foreach(old('optimization_focus', []) as $idx => $focus)
                            <div class="input-group mb-2">
                                <input type="text" name="optimization_focus[]" class="form-control" value="{{ $focus }}" maxlength="100">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="ti ti-x"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-item" data-target="#focus-container" data-name="optimization_focus[]">
                        <i class="ti ti-plus me-1"></i>添加
                    </button>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">推荐模板</h3></div>
                <div class="card-body">
                    <div id="templates-container">
                        @foreach(old('recommended_templates', []) as $idx => $tpl)
                            <div class="input-group mb-2">
                                <input type="text" name="recommended_templates[]" class="form-control" value="{{ $tpl }}" maxlength="30">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="ti ti-x"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-item" data-target="#templates-container" data-name="recommended_templates[]">
                        <i class="ti ti-plus me-1"></i>添加
                    </button>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">核心关键词</h3></div>
                <div class="card-body">
                    <div id="keywords-container">
                        @foreach(old('keywords', []) as $idx => $kw)
                            <div class="input-group mb-2">
                                <input type="text" name="keywords[]" class="form-control" value="{{ $kw }}" maxlength="50">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="ti ti-x"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-item" data-target="#keywords-container" data-name="keywords[]">
                        <i class="ti ti-plus me-1"></i>添加
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">避坑词汇</h3></div>
                <div class="card-body">
                    <div id="avoid-container">
                        @foreach(old('avoid_words', []) as $idx => $word)
                            <div class="input-group mb-2">
                                <input type="text" name="avoid_words[]" class="form-control" value="{{ $word }}" maxlength="50">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="ti ti-x"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-item" data-target="#avoid-container" data-name="avoid_words[]">
                        <i class="ti ti-plus me-1"></i>添加
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>创建赛道</button>
        <a href="{{ route('admin.career-tracks.index') }}" class="btn btn-secondary ms-2">取消</a>
    </div>
</form>

@push('js')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.querySelectorAll('.add-item').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.querySelector(btn.dataset.target);
        const name = btn.dataset.name;
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `<input type="text" name="${name}" class="form-control" maxlength="100"><button type="button" class="btn btn-outline-danger remove-item"><i class="ti ti-x"></i></button>`;
        target.appendChild(div);
    });
});
document.addEventListener('click', e => {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.input-group').remove();
    }
});
</script>
@endpush
@endsection
