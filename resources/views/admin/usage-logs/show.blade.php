@extends('layouts.admin')

@section('title', '用量详情')
@section('page-pretitle', '系统监控')
@section('page-title', '用量详情')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">调用信息</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.usage-logs.index') }}" class="btn btn-link">返回</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">ID</div>
                        <div class="datagrid-content">{{ $usage_log->id }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">用户</div>
                        <div class="datagrid-content">{{ $usage_log->user?->name ?? '未知' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">场景</div>
                        <div class="datagrid-content"><span class="badge bg-blue-lt">{{ $usage_log->scenario }}</span></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">供应商</div>
                        <div class="datagrid-content">{{ $usage_log->provider ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">输入 Token</div>
                        <div class="datagrid-content">{{ number_format($usage_log->prompt_tokens) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">输出 Token</div>
                        <div class="datagrid-content">{{ number_format($usage_log->completion_tokens) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">延迟</div>
                        <div class="datagrid-content">{{ $usage_log->latency_ms }} ms</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">费用</div>
                        <div class="datagrid-content">{{ number_format($usage_log->cost_micros) }} 微单位</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">调用时间</div>
                        <div class="datagrid-content">{{ $usage_log->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($usage_log->meta)
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">元数据</h3>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; word-wrap: break-word;">{{ json_encode($usage_log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
