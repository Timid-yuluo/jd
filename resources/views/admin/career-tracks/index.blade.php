@extends('layouts.admin')

@section('title', '校招赛道管理')
@section('page-pretitle', '简历优化')
@section('page-title', '校招赛道管理')

@section('page-actions')
<a href="{{ route('admin.career-tracks.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建赛道
</a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>排序</th>
                    <th>名称</th>
                    <th>标识</th>
                    <th>分类</th>
                    <th>关联策略</th>
                    <th>关联简历数</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tracks as $track)
                    <tr>
                        <td>{{ $track->sort_order }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($track->icon)<i class="ti {{ $track->icon }}" style="color: {{ $track->color ?? '#206bc4' }}; font-size:1.2rem;"></i>@endif
                                <span class="fw-semibold">{{ $track->name }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-primary-lt">{{ $track->slug }}</span></td>
                        <td><span class="badge bg-secondary-lt">{{ $categories[$track->category] ?? $track->category }}</span></td>
                        <td>
                            @if($track->prompt_strategy_key)
                                <span class="badge bg-info-lt">{{ $track->prompt_strategy_key }}</span>
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </td>
                        <td>{{ $track->resumes_count }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.career-tracks.toggle-active', $track) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $track->is_active ? 'btn-success' : 'btn-secondary' }}" title="{{ $track->is_active ? '点击禁用' : '点击启用' }}">
                                    {{ $track->is_active ? '启用' : '禁用' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="{{ route('admin.career-tracks.edit', $track) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-edit"></i>
                            </a>
                            @if($track->resumes_count === 0)
                                <form method="POST" action="{{ route('admin.career-tracks.destroy', $track) }}" class="d-inline" data-confirm-submit="确定删除此赛道？">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
