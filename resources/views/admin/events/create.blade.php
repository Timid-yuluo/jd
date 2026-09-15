@extends('layouts.admin')

@section('title', '新建事件')
@section('page-pretitle', '内容运营')
@section('page-title', '新建事件')

@section('page-actions')
<a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="ti ti-arrow-left me-1"></i>返回列表
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.events.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">标题 <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="200" required>
                    @error('title')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">分类 <span class="text-danger">*</span></label>
                    <select name="category" class="form-select">
                        @foreach(App\Models\SiteEvent::categories() as $cat)
                        <option value="{{ $cat }}" {{ old('category', '更新') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">自定义 Slug（可选）</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" maxlength="200" placeholder="留空自动生成">
                    @error('slug')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">摘要</label>
                <textarea name="summary" class="form-control" rows="2" maxlength="500">{{ old('summary') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">封面图 URL</label>
                <input type="text" name="cover_image" class="form-control" value="{{ old('cover_image') }}" placeholder="https://...">
            </div>
            <div class="mb-3">
                <label class="form-label">正文内容</label>
                <textarea name="content" id="content-editor" class="form-control" rows="10" data-tinymce data-tinymce-height="400" data-tinymce-upload="true">{{ old('content') }}</textarea>
            </div>
            <div class="d-flex gap-3 mb-3">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                    <span class="form-check-label">置顶</span>
                </label>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                    <span class="form-check-label">立即发布</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">保存事件</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendor/tinymce/tinymce.min.js"></script>
<script src="{{ asset('js/pages/shared-tinymce-editor.js') }}"></script>
@endpush
