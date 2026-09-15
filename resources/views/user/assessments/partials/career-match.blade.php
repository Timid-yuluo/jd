@php
    $careers = $careers ?? [];
@endphp
@if(!empty($careers))
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-target me-2"></i>推荐职业方向</h3>
    </div>
    <div class="card-body">
        <div class="row row-cards">
            @foreach($careers as $career)
            @php
                $score = $career['match_score'] ?? 0;
                $scoreColor = $score >= 80 ? 'success' : ($score >= 60 ? 'primary' : 'secondary');
            @endphp
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $career['title'] ?? '' }}</h5>
                            <span class="badge bg-{{ $scoreColor }}-lt">{{ $score }}%</span>
                        </div>
                        <p class="text-secondary small mb-0">{{ $career['reason'] ?? '' }}</p>
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar bg-{{ $scoreColor }}" style="width: {{ $score }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
