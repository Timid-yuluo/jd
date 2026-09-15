@extends('layouts.admin')

@section('title', isset($article) ? '编辑文章' : '新建文章')
@section('page-pretitle', '帮助中心')
@section('page-title', isset($article) ? '编辑文章' : '新建文章')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">文章信息</h3>
            </div>
            <div class="card-body">
                <form action="{{ isset($article) ? route('admin.help-articles.update', $article) : route('admin.help-articles.store') }}" method="POST" data-help-article-form>
                    @csrf
                    @if(isset($article))
                    @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label required">文章标题</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $article->title ?? '') }}" placeholder="文章标题" id="article-title">
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $article->slug ?? '') }}" placeholder="留空则自动生成">
                                <div class="form-hint">URL 标识，留空则从标题生成</div>
                                @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label required">所属分类</label>
                                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $article->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">排序权重</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $article->sort_order ?? 0) }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3 mt-4">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_published" value="1" {{ old('is_published', isset($article) ? $article->is_published : false) ? 'checked' : '' }}>
                                    <span class="form-check-label">发布</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">摘要</label>
                        <textarea name="excerpt" class="form-control @error('excerpt') is-invalid @enderror" rows="2" placeholder="简短描述，用于列表页展示">{{ old('excerpt', $article->excerpt ?? '') }}</textarea>
                        @error('excerpt')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">正文内容</label>
                        <textarea name="content" id="help-content-editor" class="form-control @error('content') is-invalid @enderror" rows="15">{{ old('content', $article->content ?? '') }}</textarea>
                        @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">{{ isset($article) ? '保存修改' : '创建文章' }}</button>
                        <a href="{{ route('admin.help-articles.index') }}" class="btn btn-secondary ms-2">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendor/tinymce/tinymce.min.js"></script>
<script src="/js/pages/help-article-editor.js"></script>
@endpush
