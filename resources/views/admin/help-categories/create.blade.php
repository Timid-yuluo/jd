@extends('layouts.admin')

@section('title', isset($category) ? '编辑分类' : '新建分类')
@section('page-pretitle', '帮助中心')
@section('page-title', isset($category) ? '编辑分类' : '新建分类')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">分类信息</h3>
            </div>
            <div class="card-body">
                <form action="{{ isset($category) ? route('admin.help-categories.update', $category) : route('admin.help-categories.store') }}" method="POST">
                    @csrf
                    @if(isset($category))
                    @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">分类名称</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name ?? '') }}" placeholder="如：账号与安全">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug ?? '') }}" placeholder="留空则自动生成">
                                <div class="form-hint">URL 友好标识，留空则从名称自动生成</div>
                                @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">分类描述</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $category->description ?? '') }}" placeholder="简短描述分类内容，显示在帮助中心首页">
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">图标类名</label>
                                <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror" value="{{ old('icon', $category->icon ?? '') }}" placeholder="如：ti ti-help">
                                <div class="form-hint">Tabler 图标类名</div>
                                @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">排序权重</label>
                                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                                <div class="form-hint">越小越靠前</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3 mt-4">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_visible" value="1" {{ old('is_visible', isset($category) ? $category->is_visible : true) ? 'checked' : '' }}>
                                    <span class="form-check-label">前台可见</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">{{ isset($category) ? '保存修改' : '创建分类' }}</button>
                        <a href="{{ route('admin.help-categories.index') }}" class="btn btn-secondary ms-2">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
