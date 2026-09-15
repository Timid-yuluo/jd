@extends('layouts.admin')

@section('title', 'OAuth 诊断')
@section('page-pretitle', '系统运维')
@section('page-title', 'OAuth 诊断中心')

@section('content')
<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">诊断参数</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.system-ops.oauth-diagnostics') }}">
                    <div class="mb-3">
                        <label class="form-label">统计窗口（小时）</label>
                        <input type="number" min="1" max="720" name="hours" class="form-control" value="{{ $hours }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">最小失败次数</label>
                        <input type="number" min="1" max="10000" name="alert_min_failures" class="form-control" value="{{ $alertMinFailures }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">失败率阈值（%）</label>
                        <input type="number" min="1" max="100" step="0.01" name="alert_failure_rate" class="form-control" value="{{ $alertFailureRate }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">刷新报告</button>
                </form>
                <hr>
                <form method="POST" action="{{ route('admin.system-ops.oauth-diagnostics.auto-mitigate') }}">
                    @csrf
                    <input type="hidden" name="hours" value="{{ $hours }}">
                    <input type="hidden" name="alert_min_failures" value="{{ $alertMinFailures }}">
                    <input type="hidden" name="alert_failure_rate" value="{{ $alertFailureRate }}">
                    <button type="submit" class="btn btn-outline-danger w-100" data-confirm-submit="将按当前阈值自动关闭异常 OAuth Provider，确认执行？">执行自动处置（关闭异常 Provider）</button>
                </form>
            </div>
        </div>

        @if(!empty($mitigationOutput))
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">最近一次处置输出</h3>
                </div>
                <div class="card-body">
                    <pre class="mb-0 small">{{ $mitigationOutput }}</pre>
                </div>
            </div>
        @endif

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">最近自动处置记录</h3>
            </div>
            <div class="card-body p-0">
                @if(($recentMitigations ?? collect())->isEmpty())
                    <div class="p-3 text-secondary">暂无自动处置记录</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>设置项</th>
                                    <th>变更</th>
                                    <th>时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMitigations as $log)
                                    <tr>
                                        <td>{{ $log->setting_key }}</td>
                                        <td>{{ ($log->old_value ?? 'NULL') }} → {{ ($log->new_value ?? 'NULL') }}</td>
                                        <td class="small text-secondary">{{ $log->created_at?->format('m-d H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">诊断结果</h3>
            </div>
            <div class="card-body">
                <div class="mb-3 text-secondary small">
                    统计起点：{{ $report['since'] }}（最近 {{ $report['hours'] }} 小时）
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>启用</th>
                                <th>凭据</th>
                                <th>成功</th>
                                <th>失败</th>
                                <th>失败率</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['providers'] as $provider => $data)
                                @php
                                    $isAlert = $data['enabled'] && $data['credentials_ok'] && $data['failure_count'] >= $alertMinFailures && $data['failure_rate'] >= $alertFailureRate;
                                @endphp
                                <tr class="{{ $isAlert ? 'table-danger' : '' }}">
                                    <td class="text-uppercase">{{ $provider }}</td>
                                    <td>
                                        @if($data['enabled'])
                                            <span class="badge bg-success-lt">是</span>
                                        @else
                                            <span class="badge bg-secondary-lt">否</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($data['credentials_ok'])
                                            <span class="badge bg-success-lt">完整</span>
                                        @else
                                            <span class="badge bg-danger-lt">缺失</span>
                                        @endif
                                        @if(!empty($data['credentials_reason'] ?? ''))
                                            <div class="small text-secondary mt-1">{{ $data['credentials_reason'] }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $data['success_count'] }}</td>
                                    <td>{{ $data['failure_count'] }}</td>
                                    <td>
                                        <span class="badge {{ $isAlert ? 'bg-danger' : 'bg-azure-lt' }}">
                                            {{ $data['failure_rate'] }}%
                                        </span>
                                    </td>
                                </tr>
                                @if(!empty($data['top_failures']))
                                    <tr>
                                        <td colspan="6" class="small text-secondary">
                                            高频失败：
                                            @foreach($data['top_failures'] as $item)
                                                <span class="badge bg-secondary-lt me-1">{{ $item['action'] }} ({{ $item['count'] }})</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($report['providers'] as $provider => $data)
                    <hr class="my-4">
                    <h4 class="mb-3 text-uppercase">{{ $provider }} 趋势与失败明细</h4>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-vcenter">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>成功</th>
                                    <th>失败</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['timeline'] as $point)
                                    <tr>
                                        <td class="small text-secondary">{{ $point['time'] }}</td>
                                        <td>{{ $point['success'] }}</td>
                                        <td class="{{ $point['failure'] > 0 ? 'text-danger fw-bold' : '' }}">{{ $point['failure'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-secondary">无趋势数据</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>动作</th>
                                    <th>路由</th>
                                    <th>请求</th>
                                    <th>Payload</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['recent_failures'] as $failure)
                                    <tr>
                                        <td class="small text-secondary">{{ $failure['created_at'] }}</td>
                                        <td><span class="badge bg-danger-lt">{{ $failure['action'] }}</span></td>
                                        <td class="small">{{ $failure['route_name'] !== '' ? $failure['route_name'] : '-' }}</td>
                                        <td class="small">{{ $failure['method'] }} {{ $failure['path'] }}</td>
                                        <td class="small">
                                            <details>
                                                <summary>查看</summary>
                                                <pre class="mb-0 mt-1">{{ json_encode($failure['payload'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-secondary">无失败明细</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
