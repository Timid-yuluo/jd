@extends('layouts.admin')

@section('title', 'Prompt 模板详情')
@section('page-pretitle', 'Prompt 管理')
@section('page-title', 'Prompt 模板详情')

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ $prompt->title }}</h3>
                <div>
                    @if($prompt->is_active)
                    <span class="badge bg-green-lt text-green">启用</span>
                    @else
                    <span class="badge bg-secondary-lt text-secondary">禁用</span>
                    @endif
                    <span class="badge ms-1">v{{ $prompt->version }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-muted">模板标识</label>
                    <div><code>{{ $prompt->key }}</code></div>
                </div>

                @if($prompt->description)
                <div class="mb-4">
                    <label class="form-label text-muted">描述</label>
                    <div>{{ $prompt->description }}</div>
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label text-muted">System Prompt</label>
                    <pre class="p-3 bg-light rounded"><code>{{ $prompt->system_prompt }}</code></pre>
                </div>

                @if(!empty($prompt->variables))
                <div class="mb-4">
                    <label class="form-label text-muted">变量</label>
                    <div>
                        @foreach($prompt->variables as $var)
                        <span class="badge bg-azure-lt text-azure me-1">{{ $var }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($prompt->model)
                <div class="mb-4">
                    <label class="form-label text-muted">指定模型</label>
                    <div><code>{{ $prompt->model }}</code></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">操作</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @can('ai-prompts.edit')
                    <a href="{{ route('admin.ai-prompts.edit', $prompt) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>编辑模板
                    </a>
                    <form method="POST" action="{{ route('admin.ai-prompts.duplicate', $prompt) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-copy me-1"></i>复制模板
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.ai-prompts.destroy', $prompt) }}" data-app-confirm="确定要{{ $prompt->is_active ? '禁用' : '删除' }}此模板吗？">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ti ti-{{ $prompt->is_active ? 'ban' : 'trash' }} me-1"></i>{{ $prompt->is_active ? '禁用模板' : '删除模板' }}
                        </button>
                    </form>
                    @endcan
                    <a href="{{ route('admin.ai-prompts.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>返回列表
                    </a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">模板信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">当前版本</div>
                        <div class="datagrid-content"><span class="badge">v{{ $prompt->version }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $prompt->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">最后更新</div>
                        <div class="datagrid-content">{{ $prompt->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">更新人</div>
                        <div class="datagrid-content">{{ $prompt->updater?->name ?? '系统' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
