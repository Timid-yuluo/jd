@extends('layouts.admin')

@section('title', '帮助分类管理')
@section('page-pretitle', '帮助中心')
@section('page-title', '帮助分类管理')

@section('page-actions')
@can('help-center.manage')
<a href="{{ route('admin.help-categories.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建分类
</a>
@endcan
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">分类列表</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">排序</th>
                                <th>分类名称</th>
                                <th>描述</th>
                                <th>Slug</th>
                                <th>图标</th>
                                <th style="width: 80px;">可见</th>
                                <th style="width: 80px;">文章数</th>
                                <th style="width: 80px;">已发布</th>
                                <th style="width: 160px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->sort_order }}</td>
                                <td>{{ $category->name }}</td>
                                <td><span class="text-secondary small">{{ $category->description ? Str::limit($category->description, 40) : '-' }}</span></td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>
                                    @if($category->icon)
                                    <i class="{{ $category->icon }}"></i>
                                    @else
                                    <span class="text-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($category->is_visible)
                                    <span class="badge bg-success-lt text-success">显示</span>
                                    @else
                                    <span class="badge bg-secondary-lt text-secondary">隐藏</span>
                                    @endif
                                </td>
                                <td>{{ $category->articles_count }}</td>
                                <td>{{ $category->published_articles_count }}</td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        @can('help-center.manage')
                                        <a href="{{ route('admin.help-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.help-categories.destroy', $category) }}" class="d-inline" data-app-confirm="确定要删除此分类吗？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-4">暂无分类</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($categories->hasPages())
                <div class="card-footer d-flex justify-content-center">
                    {{ $categories->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
