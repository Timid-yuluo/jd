@php
    $strategy = $session->ai_strategy ?? [];
    $assessment = $strategy['assessment'] ?? '';
    $assessmentClass = match($assessment) {
        '合理' => 'success',
        '偏高' => 'warning',
        '偏低' => 'danger',
        default => 'secondary',
    };
@endphp
@if(!empty($strategy))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-target me-2"></i>谈判策略
        </h3>
    </div>
    <div class="card-body">
        @if(!empty($strategy['assessment']))
        <div class="mb-3">
            <span class="badge bg-{{ $assessmentClass }}-lt me-2">{{ $strategy['assessment'] }}</span>
            <span class="text-secondary small">{{ $strategy['assessment_reason'] ?? '' }}</span>
        </div>
        @endif

        @if(!empty($strategy['negotiation_space']))
        <p class="mb-3">{{ $strategy['negotiation_space'] }}</p>
        @endif

        @if(!empty($strategy['strategies']))
        <div class="row row-cards">
            @foreach($strategy['strategies'] as $index => $strat)
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="avatar avatar-sm avatar-rounded bg-primary-lt me-2">{{ $loop->iteration }}</span>
                            <strong>{{ $strat['name'] ?? '策略' }}</strong>
                        </div>
                        @if(!empty($strat['steps']))
                        <ol class="small text-secondary mb-2">
                            @foreach($strat['steps'] as $step)
                            <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                        @endif
                        @if(!empty($strat['script']))
                        <div class="alert alert-info mb-0 small">
                            <i class="ti ti-quote me-1"></i>{{ $strat['script'] }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($strategy['risks']))
        <div class="alert alert-warning mt-3">
            <strong><i class="ti ti-alert-triangle me-1"></i>风险提示</strong>
            <ul class="mb-0 mt-1">
                @foreach($strategy['risks'] as $risk)
                <li>{{ $risk }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($strategy['tips']))
        <div class="alert alert-success mt-2">
            <strong><i class="ti ti-bulb me-1"></i>额外建议</strong>
            <ul class="mb-0 mt-1">
                @foreach($strategy['tips'] as $tip)
                <li>{{ $tip }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endif
