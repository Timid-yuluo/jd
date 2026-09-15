@php
    $dims = $dims ?? [];
    $scores = $dims['scores'] ?? [];
    $primary = $dims['primary'] ?? '';
    $secondary = $dims['secondary'] ?? '';
    $labels = [
        'D' => ['name' => '支配型', 'color' => 'danger'],
        'I' => ['name' => '影响型', 'color' => 'warning'],
        'S' => ['name' => '稳健型', 'color' => 'success'],
        'C' => ['name' => '谨慎型', 'color' => 'info'],
    ];
    $maxScore = !empty($scores) ? max($scores) : 1;
@endphp
@if(!empty($scores))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-chart-donut me-2"></i>DISC 四维度分析</h3>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($scores as $code => $score)
            @php
                $info = $labels[$code] ?? ['name' => $code, 'color' => 'secondary'];
                $pct = $maxScore > 0 ? round($score / $maxScore * 100) : 0;
                $isPrimary = $code === $primary;
                $isSecondary = $code === $secondary;
            @endphp
            <div class="col-12 col-md-6 mb-3">
                <div class="card {{ $isPrimary ? 'border-' . $info['color'] : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                @if($isPrimary)<span class="badge bg-{{ $info['color'] }} me-1">主导</span>@endif
                                @if($isSecondary && !$isPrimary)<span class="badge bg-secondary-lt me-1">次导</span>@endif
                                <strong>{{ $code }}</strong> {{ $info['name'] }}
                            </span>
                            <span class="text-secondary">{{ $score }} 分</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-{{ $info['color'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if(!empty($dims['description']))
        <p class="text-secondary mt-2 mb-0">{{ $dims['description'] }}</p>
        @endif
    </div>
</div>
@endif
