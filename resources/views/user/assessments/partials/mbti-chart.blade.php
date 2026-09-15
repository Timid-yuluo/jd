@php
    $dims = $dims ?? [];
@endphp
@if(!empty($dims))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-chart-bar me-2"></i>维度分析</h3>
    </div>
    <div class="card-body">
        @foreach(['EI', 'SN', 'TF', 'JP'] as $dimCode)
            @php
                $dim = $dims[$dimCode] ?? null;
                if (!$dim) continue;
                $scores = $dim['scores'] ?? [];
                $left = $scores[array_key_first($scores)] ?? 0;
                $right = $scores[array_key_last($scores)] ?? 0;
                $total = $left + $right;
                $leftPct = $total > 0 ? round($left / $total * 100) : 50;
                $rightPct = 100 - $leftPct;
                $leftKey = array_key_first($scores);
                $rightKey = array_key_last($scores);
            @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span><strong>{{ $leftKey }}</strong> {{ $dim['name'] ?? '' }} <strong>{{ $rightKey }}</strong></span>
                    <span class="text-secondary small">{{ $left }} : {{ $right }}</span>
                </div>
                <div class="progress" style="height: 24px;">
                    <div class="progress-bar bg-primary" style="width: {{ $leftPct }}%">{{ $leftKey }} {{ $leftPct }}%</div>
                    <div class="progress-bar bg-secondary" style="width: {{ $rightPct }}%">{{ $rightKey }} {{ $rightPct }}%</div>
                </div>
            </div>
        @endforeach
        @if(!empty($dims['description']))
        <p class="text-secondary mt-3 mb-0">{{ $dims['description'] }}</p>
        @endif
    </div>
</div>
@endif
