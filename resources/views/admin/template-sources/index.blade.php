@extends('layouts.admin')

@section('title', '模板源管理')
@section('page-pretitle', '模板中心')
@section('page-title', '第三方模板源')

@section('page-actions')
<a href="{{ route('admin.template-sources.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>添加模板源
</a>
<form method="POST" action="{{ route('admin.template-sources.sync-all') }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-outline-primary ms-2">
        <i class="ti ti-refresh me-1"></i>全部同步
    </button>
</form>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>名称</th>
                    <th>标识</th>
                    <th>驱动</th>
                    <th>API 地址</th>
                    <th>同步间隔</th>
                    <th>最近同步</th>
                    <th>同步状态</th>
                    <th>模板数</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sources as $source)
                <tr>
                    <td class="fw-semibold">{{ $source->name }}</td>
                    <td><span class="badge bg-primary-lt">{{ $source->slug }}</span></td>
                    <td><span class="badge bg-azure-lt">{{ $source->driver }}</span></td>
                    <td class="text-secondary small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $source->base_url ?: '-' }}</td>
                    <td>{{ $source->sync_interval_minutes }} 分钟</td>
                    <td>
                        @if($source->last_synced_at)
                            {{ $source->last_synced_at->format('m-d H:i') }}
                        @else
                            <span class="text-secondary">从未同步</span>
                        @endif
                    </td>
                    <td>
                        @if($source->last_sync_status === 'success')
                            <span class="badge bg-success-lt">成功</span>
                        @elseif($source->last_sync_status === 'running')
                            <span class="badge bg-warning-lt">同步中</span>
                        @elseif($source->last_sync_status === 'failed')
                            <span class="badge bg-danger-lt" title="{{ $source->last_sync_error }}">失败</span>
                        @else
                            <span class="badge bg-secondary-lt">待同步</span>
                        @endif
                    </td>
                    <td>{{ $source->templates()->count() }}</td>
                    <td>
                        @if($source->is_active)
                            <span class="badge bg-success-lt">启用</span>
                        @else
                            <span class="badge bg-secondary-lt">停用</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.template-sources.edit', $source) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.template-sources.sync', $source) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm" title="立即同步">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.template-sources.destroy', $source) }}" onsubmit="return confirm('确认删除？关联模板将保留但解除绑定。')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                @if($sources->isEmpty())
                <tr>
                    <td colspan="10" class="text-center text-secondary py-4">暂无模板源，点击"添加模板源"开始接入第三方模板</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
