@extends('layouts.admin')

@section('title', '编辑 Prompt 模板')
@section('page-pretitle', 'Prompt 管理')
@section('page-title', '编辑 Prompt 模板')

@section('content')
<div data-route-admin-ai-prompts-test-0="{{ route('admin.ai-prompts.test', $prompt) }}">
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">模板信息</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ai-prompts.update', $prompt) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">模板标识</label>
                        <input type="text" class="form-control" value="{{ $prompt->key }}" disabled>
                        <div class="form-hint">标识不可修改</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">模板名称</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $prompt->title) }}">
                        @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">模板描述</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description', $prompt->description) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">System Prompt</label>
                        <textarea name="system_prompt" class="form-control @error('system_prompt') is-invalid @enderror" rows="20">{{ old('system_prompt', $prompt->system_prompt) }}</textarea>
                        <div class="form-hint">修改 System Prompt 会自动增加版本号（当前: v{{ $prompt->version }}）</div>
                        @error('system_prompt')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">变量定义</label>
                                <input type="text" name="variables" class="form-control" value="{{ old('variables', is_array($prompt->variables) ? implode(', ', $prompt->variables) : $prompt->variables) }}">
                                <div class="form-hint">逗号或换行分隔的变量列表</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">指定模型</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model', $prompt->model) }}">
                                <div class="form-hint">可选，留空则使用默认模型</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $prompt->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">启用此模板</span>
                        </label>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">保存修改</button>
                        <a href="{{ route('admin.ai-prompts.index') }}" class="btn btn-secondary ms-2">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">在线测试</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">测试输入</label>
                    <textarea id="test-input" class="form-control" rows="8" placeholder="输入测试内容...">姓名：张三
学历：本科，计算机科学与技术
工作经验：2年Java开发
项目：电商后台管理系统</textarea>
                </div>
                <button type="button" class="btn btn-primary w-100" id="btn-test-prompt" data-action="run-prompt-test">
                    <i class="ti ti-play me-1"></i>运行测试
                </button>

                <div id="test-result" class="mt-3 d-none">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span id="test-status-icon" class="me-2"></span>
                                <strong id="test-status-text"></strong>
                                <span id="test-latency" class="ms-auto text-secondary small"></span>
                            </div>
                            <pre id="test-output" class="mb-0 small" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">模板信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">当前版本</div>
                        <div class="datagrid-content"><span class="badge">v{{ $prompt->version }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $prompt->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">最后更新</div>
                        <div class="datagrid-content">{{ $prompt->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">更新人</div>
                        <div class="datagrid-content">{{ $prompt->updater?->name ?? '系统' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-ai-prompts-edit.js') }}"></script>
@endpush

