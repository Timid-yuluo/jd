@php
    $score = $recommendation->match_score;
    $variant = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'secondary');
    $label = $score >= 80 ? '高匹配' : ($score >= 60 ? '一般' : '较低');
@endphp
<span class="badge bg-{{ $variant }}-lt">
    <i class="ti ti-target me-1"></i>{{ $label }} {{ $score }}%
</span>
