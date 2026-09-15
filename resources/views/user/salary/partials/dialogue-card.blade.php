@php
    $dialogue = $session->ai_dialogue ?? [];
    $scenes = $dialogue['scenes'] ?? [];
@endphp
@if(!empty($scenes))
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-messages me-2"></i>模拟对话
        </h3>
    </div>
    <div class="card-body">
        @foreach($scenes as $scene)
        <div class="mb-3">
            <div class="d-flex">
                <span class="avatar avatar-sm avatar-rounded bg-warning-lt me-2">
                    <i class="ti ti-user"></i>
                </span>
                <div class="flex-fill">
                    <div class="alert alert-warning mb-1">
                        <strong>HR：</strong>{{ $scene['hr_says'] ?? '' }}
                    </div>
                </div>
            </div>
            <div class="d-flex mt-1">
                <span class="avatar avatar-sm avatar-rounded bg-success-lt me-2">
                    <i class="ti ti-user-check"></i>
                </span>
                <div class="flex-fill">
                    <div class="alert alert-success mb-1">
                        <strong>你：</strong>{{ $scene['you_reply'] ?? '' }}
                    </div>
                    @if(!empty($scene['purpose']))
                    <div class="text-secondary small">
                        <i class="ti ti-info-circle me-1"></i>{{ $scene['purpose'] }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
