@php
    $stats = $stats ?? null;
    $jobTitle = $jobTitle ?? '';
@endphp
@if($stats && $stats['count'] > 0)
<div class="card">
    <div class="card-header">
        <h3 class="card-title">薪资分布</h3>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col">
                <div class="metric">
                    <div class="metric-value text-secondary">{{ number_format($stats['p25']) }}</div>
                    <div class="metric-label">P25</div>
                </div>
            </div>
            <div class="col">
                <div class="metric">
                    <div class="metric-value text-primary">{{ number_format($stats['p50']) }}</div>
                    <div class="metric-label">中位数 P50</div>
                </div>
            </div>
            <div class="col">
                <div class="metric">
                    <div class="metric-value text-success">{{ number_format($stats['p75']) }}</div>
                    <div class="metric-label">P75</div>
                </div>
            </div>
            <div class="col">
                <div class="metric">
                    <div class="metric-value">{{ number_format($stats['avg']) }}</div>
                    <div class="metric-label">平均</div>
                </div>
            </div>
        </div>
        <div class="text-secondary small mb-3">
            样本数：{{ $stats['count'] }} | 范围：{{ number_format($stats['min']) }} - {{ number_format($stats['max']) }}
        </div>
        @if(!empty($stats['samples']))
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>公司</th>
                        <th>城市</th>
                        <th>薪资范围</th>
                        <th>经验</th>
                        <th>日期</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['samples'] as $sample)
                    <tr>
                        <td>{{ $sample['company'] ?? '-' }}</td>
                        <td>{{ $sample['city'] ?? '-' }}</td>
                        <td>{{ number_format($sample['salary_min']) }} - {{ number_format($sample['salary_max']) }}</td>
                        <td>{{ $sample['experience_level'] ?? '-' }}</td>
                        <td>{{ $sample['reported_at'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@elseif($stats)
<div class="card">
    <div class="card-body text-center py-4">
        <i class="ti ti-chart-bar fs-1 text-secondary"></i>
        <p class="text-secondary mt-2">暂无「{{ $jobTitle }}」的薪资数据</p>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal">
            <i class="ti ti-plus me-1"></i>上报薪资数据
        </button>
    </div>
</div>
@endif
