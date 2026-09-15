@extends('layouts.admin')

@section('title', 'AI 配置管理')
@section('page-pretitle', '系统设置')
@section('page-title', 'AI 配置管理')

@section('content')
@php
    $savedScope = session('saved_scope');
    $savedProvider = session('saved_provider');
@endphp
<div data-route-admin-ai-config-test="{{ route('admin.ai-config.test') }}" data-route-admin-ai-config-benchmark="{{ route('admin.ai-config.benchmark') }}">
<div class="row row-cards">
    <div class="col-12">
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">待评分题目</div>
                        <div class="h2 mb-0">{{ number_format($monitor['pending_evaluations'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">队列积压 ({{ $monitor['queue_name'] ?? 'default' }})</div>
                        <div class="h2 mb-0">{{ number_format($monitor['queue_backlog'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">AI 调用（24h）</div>
                        <div class="h2 mb-0">{{ number_format($monitor['calls_24h'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="text-secondary">评分类失败任务</div>
                        <div class="h2 mb-0">{{ number_format($monitor['failed_jobs'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card {{ $savedScope === 'global' ? 'border-success' : '' }}">
            <div class="card-header">
                <h3 class="card-title">AI 提供商配置</h3>
                @if($savedScope === 'global')
                    <div class="card-actions">
                        <span class="badge bg-success-lt text-success">刚保存</span>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ai-config.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="save_scope" value="global">

                    <div class="mb-4">
                        <label class="form-label required">默认 AI 提供商</label>
                        <div class="form-selectgroup">
                            @foreach($providers as $key => $provider)
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="default_provider" value="{{ $key }}" class="form-selectgroup-input" @if($provider['is_active']) checked @endif @if(!($provider['enabled'] ?? true)) disabled @endif>
                                    <span class="form-selectgroup-label">
                                        @if($provider['is_active'])
                                            <i class="ti ti-check text-green me-1"></i>
                                        @endif
                                        {{ $provider['name'] }}
                                        @if(!($provider['enabled'] ?? true))
                                            <span class="text-secondary ms-1">（已关闭）</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-hint">仅可选择已开启的 Provider 作为默认值；切换后新配置将在下次请求时生效。</div>
                        <div class="mt-2 small text-secondary">当前默认：{{ $providers[$currentProvider]['name'] ?? $currentProvider }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label required">简历优化主通道</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="resume_optimize_primary_channel" value="session" class="form-selectgroup-input" {{ $optimizePrimaryChannel === 'session' ? 'checked' : '' }}>
                                <span class="form-selectgroup-label d-flex align-items-start p-3">
                                    <span class="me-3">
                                        <span class="badge bg-success-lt text-success">推荐</span>
                                    </span>
                                    <span>
                                        <span class="d-block fw-semibold">异步优先</span>
                                        <span class="d-block text-secondary small">先创建后台优化任务，由队列执行并自动重试；成功率最高，适合作为默认生产方案。</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                        <div class="form-hint">当前版本已固定使用“异步优先”会话优化，前端不再暴露流式或混合通道切换。</div>
                        <div class="mt-2 small text-secondary">当前生效通道：SESSION</div>
                    </div>
                    <div class="form-footer pt-0">
                        <button type="submit" class="btn btn-primary">保存全局配置</button>
                    </div>
                </form>

                    <div class="card mb-3 {{ $savedScope === 'provider' && $savedProvider === 'deepseek' ? 'border-success' : '' }}">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="ti ti-brand-openai me-1"></i>DeepSeek
                                @if($providers['deepseek']['is_active'] ?? false)
                                    <span class="badge bg-green-lt text-green ms-2">当前默认</span>
                                @endif
                                @if($providers['deepseek']['enabled'] ?? true)
                                    <span class="badge bg-azure-lt text-azure ms-2">已开启</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary ms-2">已关闭</span>
                                @endif
                                @if($savedScope === 'provider' && $savedProvider === 'deepseek')
                                    <span class="badge bg-success-lt text-success ms-2">刚保存</span>
                                @endif
                            </h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.ai-config.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="save_scope" value="provider">
                                <input type="hidden" name="provider" value="deepseek">
                                <input type="hidden" name="deepseek_enabled" value="0">
                                <div class="mb-3">
                                    <label class="form-label">Provider 开关</label>
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="deepseek_enabled" value="1" {{ ($providers['deepseek']['enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">开启 DeepSeek</span>
                                    </label>
                                    <div class="form-hint">关闭后，DeepSeek 不再参与默认选择、回退链路和后台测试。</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <input type="password" name="deepseek_api_key" class="form-control" placeholder="留空则保持现有配置" autocomplete="off">
                                    @if($providers['deepseek']['has_api_key'] ?? false)
                                        <div class="form-hint text-green"><i class="ti ti-check"></i> 已配置 API Key</div>
                                    @else
                                        <div class="form-hint text-yellow"><i class="ti ti-alert-triangle"></i> 未配置 API Key</div>
                                    @endif
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">模型</label>
                                    <select name="deepseek_model" class="form-select">
                                        <option value="deepseek-v4-flash" {{ config('ai.providers.deepseek.model') == 'deepseek-v4-flash' ? 'selected' : '' }}>deepseek-v4-flash（推荐）</option>
                                        <option value="deepseek-v4-pro" {{ config('ai.providers.deepseek.model') == 'deepseek-v4-pro' ? 'selected' : '' }}>deepseek-v4-pro（高性能）</option>
                                        <option value="deepseek-chat" {{ config('ai.providers.deepseek.model') == 'deepseek-chat' ? 'selected' : '' }}>deepseek-chat（兼容别名）</option>
                                        <option value="deepseek-reasoner" {{ config('ai.providers.deepseek.model') == 'deepseek-reasoner' ? 'selected' : '' }}>deepseek-reasoner（兼容别名）</option>
                                    </select>
                                    <div class="form-hint">推荐优先使用 `deepseek-v4-flash` 或 `deepseek-v4-pro`；`deepseek-chat` 与 `deepseek-reasoner` 仍可用，并兼容映射到 `deepseek-v4-flash` 的非思考/思考模式。</div>
                                    <div class="mt-2 small text-secondary">当前生效模型：{{ $providers['deepseek']['model'] ?? '-' }}</div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-outline-primary">保存 DeepSeek 配置</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3 {{ $savedScope === 'provider' && $savedProvider === 'volcano' ? 'border-success' : '' }}">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="ti ti-flame me-1"></i>火山引擎（字节跳动方舟）
                                @if($providers['volcano']['is_active'] ?? false)
                                    <span class="badge bg-green-lt text-green ms-2">当前默认</span>
                                @endif
                                @if($providers['volcano']['enabled'] ?? true)
                                    <span class="badge bg-azure-lt text-azure ms-2">已开启</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary ms-2">已关闭</span>
                                @endif
                                @if($savedScope === 'provider' && $savedProvider === 'volcano')
                                    <span class="badge bg-success-lt text-success ms-2">刚保存</span>
                                @endif
                            </h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.ai-config.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="save_scope" value="provider">
                                <input type="hidden" name="provider" value="volcano">
                                <input type="hidden" name="volcano_enabled" value="0">
                                <div class="mb-3">
                                    <label class="form-label">Provider 开关</label>
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="volcano_enabled" value="1" {{ ($providers['volcano']['enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">开启火山引擎</span>
                                    </label>
                                    <div class="form-hint">关闭后，火山引擎不再参与默认选择、回退链路和后台测试。</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <input type="password" name="volcano_api_key" class="form-control" placeholder="留空则保持现有配置" autocomplete="off">
                                    @if($providers['volcano']['has_api_key'] ?? false)
                                        <div class="form-hint text-green"><i class="ti ti-check"></i> 已配置 API Key</div>
                                    @else
                                        <div class="form-hint text-yellow"><i class="ti ti-alert-triangle"></i> 未配置 API Key</div>
                                    @endif
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">推理接入点 Endpoint ID</label>
                                    <input type="text" name="volcano_model" class="form-control" value="{{ $providers['volcano']['model'] ?? '' }}" placeholder="ep-xxxxxxxxxx" list="volcano-endpoint-list">
                                    <datalist id="volcano-endpoint-list">
                                        @foreach($volcanoEndpointOptions ?? [] as $endpointId)
                                        <option value="{{ $endpointId }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="form-hint">火山引擎方舟平台需先创建推理接入点，此处填写 Endpoint ID；输入框支持最近使用记录的快捷选择。</div>
                                    <div class="mt-2 small text-secondary">当前生效 Endpoint：{{ $providers['volcano']['model'] ?? '-' }}</div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-outline-primary">保存火山引擎配置</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3 border-info {{ $savedScope === 'provider' && $savedProvider === 'zhipu' ? 'border-success' : '' }}">
                        <div class="card-header bg-info-lt">
                            <h4 class="card-title">
                                <i class="ti ti-brain me-1"></i>智谱 AI（GLM）
                                @if($providers['zhipu']['is_active'] ?? false)
                                    <span class="badge bg-green-lt text-green ms-2">当前默认</span>
                                @endif
                                @if($providers['zhipu']['enabled'] ?? true)
                                    <span class="badge bg-azure-lt text-azure ms-2">已开启</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary ms-2">已关闭</span>
                                @endif
                                @if($savedScope === 'provider' && $savedProvider === 'zhipu')
                                    <span class="badge bg-success-lt text-success ms-2">刚保存</span>
                                @endif
                            </h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.ai-config.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="save_scope" value="provider">
                                <input type="hidden" name="provider" value="zhipu">
                                <input type="hidden" name="zhipu_enabled" value="0">
                                <div class="mb-3">
                                    <label class="form-label">Provider 开关</label>
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="zhipu_enabled" value="1" {{ ($providers['zhipu']['enabled'] ?? true) ? 'checked' : '' }}>
                                        <span class="form-check-label">开启智谱 AI</span>
                                    </label>
                                    <div class="form-hint">关闭后，智谱 AI 不再参与默认选择、回退链路和后台测试。</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <input type="password" name="zhipu_api_key" class="form-control" placeholder="留空则保持现有配置" autocomplete="off">
                                    @if($providers['zhipu']['has_api_key'] ?? false)
                                        <div class="form-hint text-green"><i class="ti ti-check"></i> 已配置 API Key</div>
                                    @else
                                        <div class="form-hint text-yellow"><i class="ti ti-alert-triangle"></i> 未配置 API Key</div>
                                    @endif
                                    <div class="form-hint">请求地址使用 `https://open.bigmodel.cn/api/paas/v4`，鉴权头使用 `Authorization: Bearer API_KEY`。</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">模型</label>
                                    <select name="zhipu_model" class="form-select">
                                        <optgroup label="GLM-4.7 / 4.6 / 4.5 / 4 系列">
                                            <option value="glm-4.7" {{ config('ai.providers.zhipu.model') == 'glm-4.7' ? 'selected' : '' }}>glm-4.7（标准版）</option>
                                            <option value="glm-4.7-flash" {{ config('ai.providers.zhipu.model') == 'glm-4.7-flash' ? 'selected' : '' }}>glm-4.7-flash（免费）</option>
                                            <option value="glm-4.6v" {{ config('ai.providers.zhipu.model') == 'glm-4.6v' ? 'selected' : '' }}>glm-4.6v（视觉增强）</option>
                                            <option value="glm-4.5-flash" {{ config('ai.providers.zhipu.model') == 'glm-4.5-flash' ? 'selected' : '' }}>glm-4.5-flash（推荐）</option>
                                            <option value="glm-4.5-air" {{ config('ai.providers.zhipu.model') == 'glm-4.5-air' ? 'selected' : '' }}>glm-4.5-air（轻量高速）</option>
                                            <option value="glm-4.5" {{ config('ai.providers.zhipu.model') == 'glm-4.5' ? 'selected' : '' }}>glm-4.5（旗舰）</option>
                                            <option value="glm-4-flash-250414" {{ config('ai.providers.zhipu.model') == 'glm-4-flash-250414' ? 'selected' : '' }}>glm-4-flash-250414（低成本）</option>
                                            <option value="glm-4-air-250414" {{ config('ai.providers.zhipu.model') == 'glm-4-air-250414' ? 'selected' : '' }}>glm-4-air-250414（高性价比）</option>
                                        </optgroup>
                                    </select>
                                    <div class="form-hint">已写死禁用 `glm-5` 及以上模型；若所选智谱模型当前不可用，系统会仅在智谱内部自动切换到备用模型，不会直接影响其他 Provider。</div>
                                    <div class="mt-2 small text-secondary">当前生效模型：{{ $providers['zhipu']['model'] ?? '-' }}</div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-info">保存智谱配置</button>
                                </div>
                            </form>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">AI 实时测试</h3>
                <button type="button" class="btn btn-sm btn-outline-info" data-action="run-benchmark">
                    <i class="ti ti-chart-bar me-1"></i>Provider 性能对比
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">测试提供商</label>
                    <select id="test-provider" class="form-select">
                        @foreach($providers as $key => $provider)
                            <option value="{{ $key }}" @if($provider['is_active']) selected @endif @if(!($provider['enabled'] ?? true)) disabled @endif>{{ $provider['name'] }}@if(!($provider['enabled'] ?? true))（已关闭）@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">测试类型</label>
                    <select id="test-type" class="form-select">
                        <option value="chat">连接测试</option>
                        <option value="resume_optimize">简历优化</option>
                        <option value="resume_score">ATS 评分</option>
                        <option value="interview_question">面试题生成</option>
                        <option value="interview_evaluate">面试评估</option>
                    </select>
                </div>
                <button type="button" class="btn btn-primary w-100" id="btn-test-ai" data-action="run-ai-test">
                    <i class="ti ti-play me-1"></i>运行测试
                </button>

                <div id="test-result" class="mt-3 d-none">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <span id="test-status-icon" class="me-2"></span>
                                <strong id="test-status-text"></strong>
                                <span id="test-latency" class="ms-auto text-secondary small"></span>
                            </div>
                            <pre id="test-output" class="mb-0 small" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">当前配置信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    @foreach($providers as $key => $provider)
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ $provider['name'] }}</div>
                            <div class="datagrid-content">
                                <span class="badge {{ ($provider['enabled'] ?? true) ? 'bg-green' : 'bg-secondary' }}">
                                    {{ ($provider['enabled'] ?? true) ? '已开启' : '已关闭' }}
                                </span>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">默认状态</div>
                            <div class="datagrid-content">{{ $provider['is_active'] ? '当前默认' : '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">模型</div>
                            <div class="datagrid-content">{{ $provider['model'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">运行监控</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">评分队列</div>
                        <div class="datagrid-content">{{ $monitor['queue_name'] ?? 'default' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">24h 平均延迟</div>
                        <div class="datagrid-content">{{ $monitor['avg_latency_24h'] ?? 0 }} ms</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">最后一次 AI 调用</div>
                        <div class="datagrid-content">
                            {{ !empty($monitor['last_call_at']) ? \Illuminate\Support\Carbon::parse($monitor['last_call_at'])->format('Y-m-d H:i:s') : '-' }}
                        </div>
                    </div>
                    @if(isset($monitor['queue_rate']))
                    <div class="datagrid-item">
                        <div class="datagrid-title">队列消费速率</div>
                        <div class="datagrid-content">{{ $monitor['queue_rate'] }} 个/分钟</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">预计处理完成</div>
                        <div class="datagrid-content">{{ $monitor['eta'] ?? '-' }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if(isset($costEstimate))
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">成本估算（24h）</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    @foreach($costEstimate['by_provider'] as $provider => $cost)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ $cost['name'] }}</div>
                        <div class="datagrid-content">
                            {{ number_format($cost['calls']) }} 次
                            <span class="text-muted">/</span>
                            <span class="{{ $cost['amount'] > 10 ? 'text-warning' : '' }}">¥{{ number_format($cost['amount'], 2) }}</span>
                        </div>
                    </div>
                    @endforeach
                    <div class="datagrid-item">
                        <div class="datagrid-title">总计</div>
                        <div class="datagrid-content">
                            <span class="h4 {{ $costEstimate['total'] > 50 ? 'text-warning' : 'text-success' }}">¥{{ number_format($costEstimate['total'], 2) }}</span>
                            @if($costEstimate['total'] > 50)
                            <span class="badge bg-warning ms-1">已超限</span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($costEstimate['alert'])
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="ti ti-alert-triangle me-1"></i>{{ $costEstimate['alert_message'] }}
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">配置变更历史</h3>
                @can('ai-config.edit')
                <a href="{{ route('admin.ai-config.history') }}" class="btn btn-sm btn-outline-primary">查看全部</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($histories ?? [] as $history)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="mb-1">
                                    <span class="badge bg-azure-lt">{{ $history->user_name ?? '系统' }}</span>
                                    <small class="text-secondary ms-1">{{ $history->created_at->format('Y-m-d H:i') }}</small>
                                </div>
                                <div class="small text-secondary">
                                    @foreach($history->changes as $field => $change)
                                        @php
                                            $fieldName = match($field) {
                                                'default_provider' => '默认提供商',
                                                'deepseek_enabled' => 'DeepSeek 开关',
                                                'deepseek_api_key' => 'DeepSeek API Key',
                                                'deepseek_model' => 'DeepSeek 模型',
                                                'volcano_enabled' => '火山引擎 开关',
                                                'volcano_api_key' => '火山引擎 API Key',
                                                'volcano_model' => '火山引擎 模型',
                                                'zhipu_enabled' => '智谱AI 开关',
                                                'zhipu_api_key' => '智谱AI API Key',
                                                'zhipu_model' => '智谱AI 模型',
                                                default => $field,
                                            };
                                        @endphp
                                        <span class="d-inline-block me-2">{{ $fieldName }}: {{ $change['old'] }} → {{ $change['new'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @if(!empty($history->verify_results['errors']))
                            <span class="badge bg-red-lt text-red"><i class="ti ti-x"></i>验证失败</span>
                            @elseif(!empty($history->verify_results['warnings']))
                            <span class="badge bg-yellow-lt text-yellow"><i class="ti ti-alert-triangle"></i>有警告</span>
                            @else
                            <span class="badge bg-green-lt text-green"><i class="ti ti-check"></i>正常</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-secondary py-3">
                        暂无变更记录
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/admin-ai-config-index.js') }}"></script>
@endpush
