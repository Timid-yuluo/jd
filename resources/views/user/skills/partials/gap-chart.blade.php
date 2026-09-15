@php
    $gaps = $gaps ?? [];
@endphp
@if(!empty($gaps))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-chart-arrows-vertical me-2"></i>技能差距分析</h3>
    </div>
    <div class="card-body">
        @foreach($gaps as $gap)
        @php
            $gapColor = match($gap['gap_level'] ?? '') {
                '无差距' => 'success',
                '小差距' => 'info',
                '中差距' => 'warning',
                '大差距', '缺失' => 'danger',
                default => 'secondary',
            };
            $current = $gap['current_level'] ?? 0;
            $target = $gap['target_level'] ?? 0;
            $impColor = match($gap['importance'] ?? '') {
                '必须' => 'danger',
                '建议' => 'warning',
                '加分' => 'success',
                default => 'secondary',
            };
        @endphp
        <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>{{ $gap['skill'] ?? '' }}</strong>
                    <span class="badge bg-{{ $impColor }}-lt ms-2">{{ $gap['importance'] ?? '' }}</span>
                    <span class="badge bg-{{ $gapColor }}-lt ms-1">{{ $gap['gap_level'] ?? '' }}</span>
                </div>
                <span class="text-secondary small">{{ $current }} → {{ $target }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="progress flex-grow-1" style="height: 8px;">
                    <div class="progress-bar bg-secondary" style="width: {{ $current * 20 }}%"></div>
                    <div class="progress-bar bg-{{ $gapColor }}" style="width: {{ ($target - $current) * 20 }}%"></div>
                </div>
                <span class="text-secondary small">{{ $target * 20 }}%</span>
            </div>
            @if(!empty($gap['suggestion']))
            <div class="text-secondary small">
                <i class="ti ti-info-circle me-1"></i>{{ $gap['suggestion'] }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
