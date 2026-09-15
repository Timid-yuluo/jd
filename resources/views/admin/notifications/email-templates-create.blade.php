@extends('layouts.admin')

@section('title', '新建邮件模板')
@section('page-pretitle', '邮件模板')
@section('page-title', '新建邮件模板')

@section('content')
<div data-config-app-name="{{ config('app.name') }}" data-config-app-url="{{ config('app.url') }}">
<div class="row row-cards">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">模板内容</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.notifications.email-templates.store') }}" method="POST" id="template-form">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">模板名称</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="例如：欢迎邮件">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">标识符</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required placeholder="例如：welcome">
                            <div class="form-hint">唯一标识，用于系统识别模板</div>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">邮件主题</label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required placeholder="例如：欢迎注册 {{ '{' . '{ site_name }' . '}' }}">
                        <div class="form-hint">可以使用变量，如：{{ '{' . '{ site_name }' . '}' }}</div>
                        @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="简短描述此模板的用途">
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">邮件内容</label>
                        <textarea name="content" id="email-editor" class="form-control @error('content') is-invalid @enderror" rows="15">{{ old('content') }}</textarea>
                        @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <span class="form-check-label">启用此模板</span>
                        </label>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>保存模板
                        </button>
                        <a href="{{ route('admin.notifications.email-templates') }}" class="btn btn-link">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">可用变量</h3>
            </div>
            <div class="card-body">
                <div class="small text-secondary mb-2">点击变量可复制</div>
                <div class="list-group list-group-flush">
                    @foreach($defaultVariables as $key => $label)
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center variable-btn" data-variable="{{ $key }}">
                        <span><code>{{ '{' . '{ ' . $key . ' }' . '}' }}</code></span>
                        <small class="text-secondary">{{ $label }}</small>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">预览</h3>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-outline-primary w-100" data-action="preview-template">
                    <i class="ti ti-eye me-1"></i>预览效果
                </button>
                <div class="mt-2 text-secondary small">
                    点击预览可查看渲染后的邮件效果
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">使用提示</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><i class="ti ti-check text-success me-1"></i> 使用 <code>{{ '{' . '{ }' . '}' }}</code> 包裹变量名</li>
                    <li class="mb-2"><i class="ti ti-check text-success me-1"></i> 支持完整的 HTML 格式</li>
                    <li class="mb-2"><i class="ti ti-check text-success me-1"></i> 图片建议使用绝对路径</li>
                    <li><i class="ti ti-check text-success me-1"></i> 建议使用表格布局确保兼容性</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 预览模态框 -->
<div class="modal modal-blur fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">邮件预览</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">邮件主题</label>
                    <div class="form-control-plaintext border rounded p-2" id="preview-subject"></div>
                </div>
                <div>
                    <label class="form-label text-muted">邮件内容</label>
                    <div class="border rounded p-3" style="min-height: 200px; background: #f8f9fa;" id="preview-content"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/summernote/summernote-lite.min.css') }}" rel="stylesheet">
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/admin-notifications-email-templates-create.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-notifications-email-templates-create.js') }}"></script>
@endpush