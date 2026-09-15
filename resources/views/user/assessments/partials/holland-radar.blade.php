@php
    $dims = $dims ?? [];
    $scores = $dims['scores'] ?? [];
    $topThree = $dims['top_three'] ?? [];
    $careers = $dims['recommended_careers'] ?? [];
    $labels = [
        'R' => '现实型',
        'I' => '研究型',
        'A' => '艺术型',
        'S' => '社会型',
        'E' => '企业型',
        'C' => '常规型',
    ];
    $maxScore = !empty($scores) ? max($scores) : 1;
@endphp
@if(!empty($scores))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-chart-radar me-2"></i>六维度得分</h3>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($scores as $code => $score)
            @php
                $pct = $maxScore > 0 ? round($score / $maxScore * 100) : 0;
                $isTop = in_array($code, $topThree);
            @endphp
            <div class="col-12 mb-2">
                <div class="d-flex justify-content-between mb-1">
                    <span>
                        @if($isTop)<i class="ti ti-star-filled text-warning me-1"></i>@endif
                        <strong>{{ $code }}</strong> {{ $labels[$code] ?? '' }}
                    </span>
                    <span class="text-secondary">{{ $score }} 分</span>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar {{ $isTop ? 'bg-success' : 'bg-secondary' }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
        @if(!empty($topThree))
        <div class="alert alert-success mt-3 mb-0">
            <strong>你的霍兰德代码：{{ implode('', $topThree) }}</strong>
            @if(!empty($careers) && is_array($careers))
            <div class="mt-2">
                <span class="text-secondary">推荐职业：</span>
                @foreach($careers as $career)
                <span class="badge bg-success-lt me-1">{{ $career }}</span>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
