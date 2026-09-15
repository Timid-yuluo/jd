@extends('layouts.admin')

@section('title', '编辑事件')
@section('page-pretitle', '内容运营')
@section('page-title', '编辑事件')

@section('page-actions')
<a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="ti ti-arrow-left me-1"></i>返回列表
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.events.update', $event) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">标题 <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $event->title) }}" maxlength="200" required>
                    @error('title')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">分类 <span class="text-danger">*</span></label>
                    <select name="category" class="form-select">
                        @foreach(App\Models\SiteEvent::categories() as $cat)
                        <option value="{{ $cat }}" {{ old('category', $event->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $event->slug) }}" maxlength="200">
                    @error('slug')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">摘要</label>
                <textarea name="summary" class="form-control" rows="2" maxlength="500">{{ old('summary', $event->summary) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">封面图 URL</label>
                <input type="text" name="cover_image" class="form-control" value="{{ old('cover_image', $event->cover_image) }}" placeholder="https://...">
                @if($event->cover_image)
                <small class="text-secondary">当前：{{ $event->cover_image }}</small>
                @endif
            </div>
            <div class="mb-3">
                <label class="form-label">正文内容</label>
                <textarea name="content" id="content-editor" class="form-control" rows="12" data-tinymce data-tinymce-height="400" data-tinymce-upload="true">{{ old('content', $event->content) }}</textarea>
            </div>
            <div class="row mb-3">
                <div class="col-auto">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $event->is_pinned) ? 'checked' : '' }}>
                        <span class="form-check-label">置顶</span>
                    </label>
                </div>
                <div class="col-auto">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }}>
                        <span class="form-check-label">已发布</span>
                    </label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">保存</button>
                <form action="{{ route('admin.events.destroy', $event) }}" method="POST" data-app-confirm="确定删除此事件吗？">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">删除</button>
                </form>
            </div>
        </form>
    </div>
    @if($event->is_published)
    <div class="card-footer text-secondary small">
        发布时间：{{ $event->published_at?->format('Y-m-d H:i') }} &middot; {{ $event->view_count }} 次浏览
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="/vendor/tinymce/tinymce.min.js"></script>
<script src="{{ asset('js/pages/shared-tinymce-editor.js') }}"></script>
@endpush
