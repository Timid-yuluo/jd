@extends('layouts.admin')

@section('title', '新建 Prompt 模板')
@section('page-pretitle', 'Prompt 管理')
@section('page-title', '新建 Prompt 模板')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">模板信息</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ai-prompts.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">模板标识</label>
                                <input type="text" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" placeholder="如: resume_optimize">
                                <div class="form-hint">唯一标识，只能包含字母、数字、下划线</div>
                                @error('key')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">模板名称</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="如: 简历优化">
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">模板描述</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="简要描述此 Prompt 的用途">
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">System Prompt</label>
                        <textarea name="system_prompt" class="form-control @error('system_prompt') is-invalid @enderror" rows="15" placeholder="输入系统 Prompt...">{{ old('system_prompt') }}</textarea>
                        <div class="form-hint">定义 AI 的角色、任务和输出格式要求</div>
                        @error('system_prompt')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">变量定义</label>
                                <input type="text" name="variables" class="form-control" value="{{ old('variables') }}" placeholder="如: resume_content, target_job">
                                <div class="form-hint">逗号或换行分隔的变量列表</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">指定模型</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="如: deepseek-chat">
                                <div class="form-hint">可选，留空则使用默认模型</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label">启用此模板</span>
                        </label>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">创建模板</button>
                        <a href="{{ route('admin.ai-prompts.index') }}" class="btn btn-secondary ms-2">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
