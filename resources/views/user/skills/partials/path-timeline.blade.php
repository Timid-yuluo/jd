@php
    $phases = $phases ?? [];
@endphp
@if(!empty($phases))
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-route me-2"></i>学习路径时间线</h3>
    </div>
    <div class="card-body">
        <div class="timeline">
            @foreach($phases as $phase)
            <div class="timeline-item">
                <div class="timeline-badge bg-primary-lt">
                    <i class="ti ti-flag"></i>
                </div>
                <div class="timeline-content">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-primary-lt me-2">阶段 {{ $phase['phase'] ?? $loop->iteration }}</span>
                            <strong>{{ $phase['title'] ?? '' }}</strong>
                        </div>
                        <span class="badge bg-info-lt">
                            <i class="ti ti-clock me-1"></i>{{ $phase['duration_weeks'] ?? 0 }} 周
                        </span>
                    </div>
                    @if(!empty($phase['goal']))
                    <p class="text-secondary mb-2">{{ $phase['goal'] }}</p>
                    @endif
                    @if(!empty($phase['topics']))
                    <div class="mb-2">
                        <span class="text-secondary small">学习主题：</span>
                        @foreach($phase['topics'] as $topic)
                        <span class="badge bg-secondary-lt me-1">{{ $topic }}</span>
                        @endforeach
                    </div>
                    @endif
                    @if(!empty($phase['resources']))
                    <div class="mb-2">
                        <span class="text-secondary small">推荐资源：</span>
                        <ul class="small mb-1">
                            @foreach($phase['resources'] as $res)
                            <li>
                                <span class="badge bg-{{ match($res['type'] ?? '') {
                                    'book' => 'info',
                                    'course' => 'success',
                                    'project' => 'warning',
                                    'video' => 'danger',
                                    default => 'secondary',
                                } }}-lt me-1">{{ $res['type'] ?? '资源' }}</span>
                                {{ $res['name'] ?? '' }}
                                @if(!empty($res['url']))
                                <a href="{{ $res['url'] }}" target="_blank" class="text-primary ms-1">
                                    <i class="ti ti-external-link"></i>
                                </a>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @if(!empty($phase['milestone']))
                    <div class="alert alert-success mb-0 small">
                        <i class="ti ti-trophy me-1"></i><strong>里程碑：</strong>{{ $phase['milestone'] }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--tblr-border-color);
}
.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}
.timeline-badge {
    position: absolute;
    left: -33px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.timeline-content {
    background: var(--tblr-card-bg);
    padding: 0;
}
</style>
@endif
