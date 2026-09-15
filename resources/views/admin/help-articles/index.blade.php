@extends('layouts.admin')

@section('title', '帮助文章管理')
@section('page-pretitle', '帮助中心')
@section('page-title', '帮助文章管理')

@section('page-actions')
@can('help-center.manage')
<a href="{{ route('admin.help-articles.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建文章
</a>
@endcan
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end w-100">
            <div class="col-md-3">
                <label class="form-label">分类</label>
                <select name="category_id" class="form-select">
                    <option value="">全部分类</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">状态</label>
                <select name="status" class="form-select">
                    <option value="">全部</option>
                    <option value="published" @selected(request('status') === 'published')>已发布</option>
                    <option value="draft" @selected(request('status') === 'draft')>草稿</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">关键词</label>
                <input type="text" class="form-control" name="keyword" value="{{ request('keyword') }}" placeholder="搜索标题/摘要">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">筛选</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.help-articles.index') }}" class="btn btn-outline-secondary w-100">重置</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width: 60px;">排序</th>
                    <th>标题</th>
                    <th>分类</th>
                    <th style="width: 80px;">状态</th>
                    <th style="width: 80px;">浏览量</th>
                    <th style="width: 160px;">发布时间</th>
                    <th style="width: 200px;">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>{{ $article->sort_order }}</td>
                    <td>
                        <div>{{ $article->title }}</div>
                        @if($article->excerpt)
                        <div class="small text-secondary">{{ Str::limit($article->excerpt, 60) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($article->category)
                        <span class="badge bg-primary-lt text-primary">{{ $article->category->name }}</span>
                        @else
                        <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td>
                        @if($article->is_published)
                        <span class="badge bg-success-lt text-success">已发布</span>
                        @else
                        <span class="badge bg-secondary-lt text-secondary">草稿</span>
                        @endif
                    </td>
                    <td>{{ $article->view_count }}</td>
                    <td class="text-secondary small">
                        {{ $article->published_at?->format('Y-m-d H:i') ?? '-' }}
                    </td>
                    <td>
                        <div class="btn-list flex-nowrap">
                            @can('help-center.manage')
                            <button type="button" class="btn btn-sm btn-outline-{{ $article->is_published ? 'warning' : 'success' }}"
                                data-action="toggle-publish" data-article-id="{{ $article->id }}"
                                title="{{ $article->is_published ? '转为草稿' : '发布' }}">
                                <i class="ti ti-{{ $article->is_published ? 'eye-off' : 'eye' }}"></i>
                            </button>
                            <a href="{{ route('admin.help-articles.edit', $article) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan
                            <a href="{{ route('public.help.show', ['categorySlug' => $article->category?->slug, 'articleSlug' => $article->slug]) }}" class="btn btn-sm btn-outline-info" target="_blank" title="前台预览">
                                <i class="ti ti-external-link"></i>
                            </a>
                            @can('help-center.manage')
                            <form method="POST" action="{{ route('admin.help-articles.destroy', $article) }}" class="d-inline" data-app-confirm="确定要删除此文章吗？">
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
                    <td colspan="7" class="text-center text-secondary py-4">暂无文章</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($articles->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $articles->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
function togglePublish(id, btn) {
    fetch('{{ route('admin.help-articles.toggle-publish', ['helpArticle' => '__ID__']) }}'.replace('__ID__', id), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.message) {
            window.location.reload();
        }
    })
    .catch(() => alert('操作失败，请重试'));
}
</script>
@endpush
@endsection
