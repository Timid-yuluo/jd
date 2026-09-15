@extends('layouts.admin')

@section('title', '编辑邮件模板')
@section('page-pretitle', '邮件模板')
@section('page-title', '编辑邮件模板')

@section('content')
<div data-config-app-name="{{ config('app.name') }}" data-config-app-url="{{ config('app.url') }}">
<div class="row row-cards">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">模板内容</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.notifications.email-templates.update', $template) }}" method="POST" id="template-form">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">模板名称</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $template->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">标识符</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $template->slug) }}" required>
                            <div class="form-hint">唯一标识，用于系统识别模板</div>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">邮件主题</label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $template->subject) }}" required>
                        <div class="form-hint">可以使用变量，如：{{ '{' . '{ site_name }' . '}' }}</div>
                        @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $template->description) }}">
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">邮件内容</label>
                        <textarea name="content" id="email-editor" class="form-control @error('content') is-invalid @enderror" rows="15">{{ old('content', $template->content) }}</textarea>
                        @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                            <span class="form-check-label">启用此模板</span>
                        </label>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>更新模板
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
                <h3 class="card-title">使用统计</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-secondary">创建时间：</span>
                    <span>{{ $template->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="mb-2">
                    <span class="text-secondary">更新时间：</span>
                    <span>{{ $template->updated_at->format('Y-m-d H:i') }}</span>
                </div>
                <div>
                    <span class="text-secondary">使用次数：</span>
                    <span>{{ $template->notifications()->count() }} 次</span>
                </div>
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
<link rel="stylesheet" href="{{ asset('css/pages/admin-notifications-email-templates-edit.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/pages/admin-notifications-email-templates-edit.js') }}"></script>
@endpush