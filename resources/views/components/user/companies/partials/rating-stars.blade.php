{{--
    星级评分组件
    @param float $rating 评分 (0-5)
    @param string $size 尺寸 sm/md/lg
--}}
@php
    $size = $size ?? 'sm';
    $sizeClass = match($size) {
        'sm' => 'fs-5',
        'md' => 'fs-4',
        'lg' => 'fs-3',
        default => 'fs-5'
    };
    $fullStars = (int) floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);
@endphp
<span class="rating-stars {{ $sizeClass }} text-warning">
    @for($i = 0; $i < $fullStars; $i++)
        <i class="ti ti-star-filled"></i>
    @endfor
    @if($hasHalf)
        <i class="ti ti-star-half-filled"></i>
    @endif
    @for($i = 0; $i < $emptyStars; $i++)
        <i class="ti ti-star"></i>
    @endfor
    @if(isset($showValue) && $showValue)
        <span class="text-secondary ms-1" style="font-size: 0.85em;">{{ number_format($rating, 1) }}</span>
    @endif
</span>
