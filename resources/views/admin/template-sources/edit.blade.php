@extends('layouts.admin')

@section('title', '编辑模板源')
@section('page-pretitle', '模板中心')
@section('page-title', '编辑模板源 - ' . $templateSource->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.template-sources.update', $templateSource) }}">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-header"><h3 class="card-title">基本信息</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">模板源名称</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $templateSource->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">标识 (slug)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $templateSource->slug) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">驱动类型</label>
                        <select name="driver" class="form-select">
                            <option value="api" {{ old('driver', $templateSource->driver) === 'api' ? 'selected' : '' }}>API 接口</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API 基础地址</label>
                        <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $templateSource->base_url) }}">
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">认证配置</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="credentials[api_key]" class="form-control" value="{{ old('credentials.api_key') }}" placeholder="留空保持原值不变">
                        <div class="form-hint">加密存储，编辑时不会回显原值</div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">同步配置</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">模板列表路径</label>
                        <input type="text" name="sync_config[templates_path]" class="form-control" value="{{ old('sync_config.templates_path', $templateSource->sync_config['templates_path'] ?? '/api/v1/templates') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">模板详情路径</label>
                        <input type="text" name="sync_config[detail_path]" class="form-control" value="{{ old('sync_config.detail_path', $templateSource->sync_config['detail_path'] ?? '/api/v1/templates/{id}') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分类筛选</label>
                        <input type="text" name="sync_config[category_filter]" class="form-control" value="{{ old('sync_config.category_filter', $templateSource->sync_config['category_filter'] ?? '') }}">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">同步间隔（分钟）</label>
                            <input type="number" name="sync_interval_minutes" class="form-control" value="{{ old('sync_interval_minutes', $templateSource->sync_interval_minutes) }}" min="10" max="10080">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">排序</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $templateSource->sort_order) }}" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $templateSource->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">启用</span>
                        </label>
                    </div>
                </div>
            </div>

            @if($templateSource->last_synced_at)
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">同步状态</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-secondary small">最近同步时间</div>
                            <div class="fw-semibold">{{ $templateSource->last_synced_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">同步状态</div>
                            <div>
                                @if($templateSource->last_sync_status === 'success')
                                    <span class="badge bg-success-lt">成功</span>
                                @elseif($templateSource->last_sync_status === 'failed')
                                    <span class="badge bg-danger-lt">失败</span>
                                @else
                                    <span class="badge bg-secondary-lt">{{ $templateSource->last_sync_status }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">同步模板数</div>
                            <div class="fw-semibold">{{ $templateSource->last_sync_count }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">关联模板总数</div>
                            <div class="fw-semibold">{{ $templateSource->templates()->count() }}</div>
                        </div>
                    </div>
                    @if($templateSource->last_sync_error)
                    <div class="mt-3 alert alert-warning small">
                        <strong>最近错误：</strong>{{ $templateSource->last_sync_error }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">保存</button>
                <a href="{{ route('admin.template-sources.index') }}" class="btn btn-outline-secondary">取消</a>
            </div>
        </form>
    </div>
</div>
@endsection
