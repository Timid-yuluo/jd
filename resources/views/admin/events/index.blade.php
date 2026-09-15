@extends('layouts.admin')

@section('title', '事件管理')
@section('page-pretitle', '内容运营')
@section('page-title', '事件管理')

@section('page-actions')
<a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
    <i class="ti ti-plus me-1"></i>新建事件
</a>
@endsection

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-news"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">总事件</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-success text-white avatar"><i class="ti ti-eye"></i></span></div>
                    <div class="col"><div class="font-weight-medium text-success">{{ number_format($stats['published']) }}</div><div class="text-secondary">已发布</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-yellow text-white avatar"><i class="ti ti-pinned"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['pinned']) }}</div><div class="text-secondary">置顶</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-eye"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total_views']) }}</div><div class="text-secondary">总浏览</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="category" class="form-select">
                    <option value="">全部分类</option>
                    @foreach(App\Models\SiteEvent::categories() as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <input type="text" name="keyword" class="form-control" placeholder="搜索标题/摘要..." value="{{ request('keyword') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="ti ti-search me-1"></i>搜索
                </button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.events.index') }}" class="btn btn-ghost-secondary">重置</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>分类</th>
                    <th>置顶</th>
                    <th>状态</th>
                    <th>浏览</th>
                    <th>发布人</th>
                    <th>发布时间</th>
                    <th style="width:160px">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                <tr>
                    <td class="text-truncate" style="max-width:260px">
                        <a href="{{ route('admin.events.edit', $event) }}" class="text-reset">{{ $event->title }}</a>
                    </td>
                    <td>
                        <span class="badge {{ $event->getCategoryBadgeClass() }}">{{ $event->category }}</span>
                    </td>
                    <td>
                        @if($event->is_pinned)
                        <i class="ti ti-pinned-filled text-yellow" title="已置顶"></i>
                        @else
                        <i class="ti ti-pinned-off text-secondary" title="未置顶"></i>
                        @endif
                    </td>
                    <td>
                        @if($event->is_published)
                        <span class="badge bg-success-lt">已发布</span>
                        @else
                        <span class="badge bg-secondary-lt">未发布</span>
                        @endif
                    </td>
                    <td>{{ $event->view_count }}</td>
                    <td>{{ $event->admin?->name }}</td>
                    <td class="text-nowrap">{{ $event->published_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.events.toggle-publish', $event) }}" class="btn btn-sm btn-{{ $event->is_published ? 'warning' : 'success' }}" title="{{ $event->is_published ? '下架' : '发布' }}">
                                <i class="ti ti-{{ $event->is_published ? 'eye-off' : 'eye' }}"></i>
                            </a>
                            <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-outline-secondary" title="编辑">
                                <i class="ti ti-edit"></i>
                            </a>
                            <form action="{{ route('admin.events.destroy', $event) }}" method="POST" data-app-confirm="确定删除此事件吗？">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-secondary">暂无事件数据</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($events->hasPages())
    <div class="card-footer">
        {{ $events->links() }}
    </div>
    @endif
</div>
@endsection
