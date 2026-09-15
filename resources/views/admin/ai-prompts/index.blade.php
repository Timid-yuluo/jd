@extends('layouts.admin')

@section('title', 'Prompt 模板管理')
@section('page-pretitle', 'AI 中心')
@section('page-title', 'Prompt 模板管理')

@section('page-actions')
@can('ai-prompts.edit')
<a href="{{ route('admin.ai-prompts.create') }}" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>新建模板
</a>
@endcan
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Prompt 模板列表</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">状态</th>
                                <th>标识</th>
                                <th>名称</th>
                                <th style="width: 80px;">版本</th>
                                <th style="width: 160px;">最后更新</th>
                                <th style="width: 200px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prompts as $prompt)
                            <tr>
                                <td>
                                    @if($prompt->is_active)
                                    <span class="badge bg-green-lt text-green">启用</span>
                                    @else
                                    <span class="badge bg-secondary-lt text-secondary">禁用</span>
                                    @endif
                                </td>
                                <td><code>{{ $prompt->key }}</code></td>
                                <td>
                                    <div>{{ $prompt->title }}</div>
                                    <div class="small text-secondary">{{ Str::limit($prompt->description, 50) }}</div>
                                </td>
                                <td><span class="badge">v{{ $prompt->version }}</span></td>
                                <td class="text-secondary small">
                                    {{ $prompt->updated_at->format('Y-m-d H:i') }}<br>
                                    {{ $prompt->updater?->name ?? '系统' }}
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="{{ route('admin.ai-prompts.show', $prompt) }}" class="btn btn-sm btn-outline-info">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @can('ai-prompts.edit')
                                        <a href="{{ route('admin.ai-prompts.edit', $prompt) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.ai-prompts.duplicate', $prompt) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="复制">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.ai-prompts.destroy', $prompt) }}" class="d-inline" data-app-confirm="确定要{{ $prompt->is_active ? '禁用' : '删除' }}此模板吗？">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ $prompt->is_active ? '禁用' : '删除' }}">
                                                <i class="ti ti-{{ $prompt->is_active ? 'ban' : 'trash' }}"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    暂无 Prompt 模板
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($prompts->hasPages())
                <div class="card-footer d-flex justify-content-center">
                    {{ $prompts->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
