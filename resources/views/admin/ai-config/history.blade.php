@extends('layouts.admin')

@section('title', 'AI 配置变更历史')
@section('page-pretitle', '系统设置')
@section('page-title', 'AI 配置变更历史')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">配置变更记录</h3>
                <a href="{{ route('admin.ai-config.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>返回配置页
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width: 160px;">时间</th>
                                <th style="width: 120px;">操作人</th>
                                <th>变更内容</th>
                                <th style="width: 120px;">验证状态</th>
                                <th style="width: 120px;">IP 地址</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                            <tr>
                                <td class="text-secondary">
                                    {{ $history->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td>
                                    <span class="badge bg-azure-lt">{{ $history->user_name ?? '系统' }}</span>
                                </td>
                                <td>
                                    <div class="small">
                                        @foreach($history->changes as $field => $change)
                                            @php
                                                $fieldName = match($field) {
                                                    'default_provider' => '默认提供商',
                                                    'deepseek_api_key' => 'DeepSeek API Key',
                                                    'deepseek_model' => 'DeepSeek 模型',
                                                    'volcano_api_key' => '火山引擎 API Key',
                                                    'volcano_model' => '火山引擎 模型',
                                                    'zhipu_api_key' => '智谱AI API Key',
                                                    'zhipu_model' => '智谱AI 模型',
                                                    default => $field,
                                                };
                                            @endphp
                                            <div class="mb-1">
                                                <span class="text-muted">{{ $fieldName }}:</span>
                                                <span class="text-danger">{{ $change['old'] }}</span>
                                                <i class="ti ti-arrow-right mx-1 text-secondary"></i>
                                                <span class="text-success">{{ $change['new'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($history->verify_results['errors']))
                                    <span class="badge bg-red-lt text-red">
                                        <i class="ti ti-x me-1"></i>验证失败
                                    </span>
                                    <div class="small text-danger mt-1">
                                        {{ implode('；', $history->verify_results['errors']) }}
                                    </div>
                                    @elseif(!empty($history->verify_results['warnings']))
                                    <span class="badge bg-yellow-lt text-yellow">
                                        <i class="ti ti-alert-triangle me-1"></i>有警告
                                    </span>
                                    <div class="small text-warning mt-1">
                                        {{ implode('；', $history->verify_results['warnings']) }}
                                    </div>
                                    @else
                                    <span class="badge bg-green-lt text-green">
                                        <i class="ti ti-check me-1"></i>正常
                                    </span>
                                    @endif
                                </td>
                                <td class="text-secondary small">
                                    {{ $history->ip_address ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    暂无变更记录
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($histories->hasPages())
                <div class="card-footer d-flex justify-content-center">
                    {{ $histories->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
