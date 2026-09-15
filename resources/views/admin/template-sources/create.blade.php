@extends('layouts.admin')

@section('title', '添加模板源')
@section('page-pretitle', '模板中心')
@section('page-title', '添加第三方模板源')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.template-sources.store') }}">
            @csrf
            <div class="card">
                <div class="card-header"><h3 class="card-title">基本信息</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">模板源名称</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="如：老鱼简历模板库">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">标识 (slug)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required placeholder="如：laoyujianli">
                        <div class="form-hint">仅限字母、数字、连字符，用于 URL 筛选参数</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">驱动类型</label>
                        <select name="driver" class="form-select">
                            <option value="api" selected>API 接口</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API 基础地址</label>
                        <input type="url" name="base_url" class="form-control" value="{{ old('base_url') }}" placeholder="https://api.example.com">
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">认证配置</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="credentials[api_key]" class="form-control" value="{{ old('credentials.api_key') }}" placeholder="留空则不发送认证头">
                        <div class="form-hint">将作为 Bearer Token 发送，加密存储</div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">同步配置</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">模板列表路径</label>
                        <input type="text" name="sync_config[templates_path]" class="form-control" value="{{ old('sync_config.templates_path', '/api/v1/templates') }}" placeholder="/api/v1/templates">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">模板详情路径</label>
                        <input type="text" name="sync_config[detail_path]" class="form-control" value="{{ old('sync_config.detail_path', '/api/v1/templates/{id}') }}" placeholder="/api/v1/templates/{id}">
                        <div class="form-hint">{id} 将替换为外部模板 ID</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分类筛选</label>
                        <input type="text" name="sync_config[category_filter]" class="form-control" value="{{ old('sync_config.category_filter') }}" placeholder="留空同步全部分类">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">同步间隔（分钟）</label>
                            <input type="number" name="sync_interval_minutes" class="form-control" value="{{ old('sync_interval_minutes', 360) }}" min="10" max="10080">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 100) }}" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <span class="form-check-label">启用</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">创建</button>
                <a href="{{ route('admin.template-sources.index') }}" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
</div>
@endsection
