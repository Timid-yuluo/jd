@php
    $gaps = $recommendation->skill_gaps ?? [];
@endphp
@if(!empty($gaps))
<div class="alert alert-warning alert-dismissible mb-2" role="alert">
    <div class="d-flex">
        <i class="ti ti-alert-triangle me-2 mt-1"></i>
        <div>
            <strong>技能差距提示</strong>
            <div class="mt-1 d-flex flex-wrap gap-1">
                @foreach($gaps as $gap)
                    <span class="badge bg-warning-lt">{{ $gap }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
