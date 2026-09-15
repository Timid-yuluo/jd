@php
    $reasons = $recommendation->match_reasons ?? [];
    $isNew = $recommendation->status === \App\Models\JobRecommendation::STATUS_NEW;
    $isApplied = $recommendation->status === \App\Models\JobRecommendation::STATUS_APPLIED;
    $isViewed = $recommendation->viewed_at !== null;

    // #5 source_url 加 URL 白名单校验，防止 javascript: 等危险协议
    $rawUrl = $recommendation->externalRecruitment?->source_url;
    $safeUrl = '';
    if ($rawUrl) {
        $parsed = parse_url((string) $rawUrl);
        if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            $safeUrl = $rawUrl;
        }
    }
@endphp
<div class="card mb-3 job-recommendation-card {{ $isViewed ? 'opacity-75' : '' }}" data-id="{{ $recommendation->id }}" data-viewed="{{ $isViewed ? '1' : '0' }}">
    <div class="card-body">
        <div class="d-flex align-items-start">
            {{-- #16 复选框 --}}
            <div class="me-3">
                <input type="checkbox" class="form-check-input job-cb" value="{{ $recommendation->id }}" />
            </div>
            <span class="avatar avatar-md avatar-rounded bg-primary-lt me-3">
                <i class="ti ti-briefcase"></i>
            </span>
            <div class="flex-fill">
                <div class="d-flex align-items-center mb-1">
                    <h3 class="card-title mb-0 me-2">
                        {{ $recommendation->job_title ?? '未知岗位' }}
                    </h3>
                    @include('user.job-recommendations.partials.match-badge')
                    @if($isNew)
                        <span class="badge bg-primary ms-1">新</span>
                    @endif
                    @if($isApplied)
                        <span class="badge bg-success-lt ms-1">已投递</span>
                    @endif
                    @if($isViewed)
                        <span class="badge bg-secondary-lt ms-1">已查看</span>
                    @endif
                </div>
                <div class="text-secondary small mb-2">
                    @if($recommendation->company)
                        <span class="me-3"><i class="ti ti-building me-1"></i>{{ $recommendation->company }}</span>
                    @endif
                    @if($recommendation->city)
                        <span class="me-3"><i class="ti ti-map-pin me-1"></i>{{ $recommendation->city }}</span>
                    @endif
                    <span><i class="ti ti-clock me-1"></i>{{ $recommendation->created_at->diffForHumans() }}</span>
                </div>

                {{-- #18 匹配原因展示 --}}
                @if(!empty($reasons))
                <div class="mb-2">
                    <details class="match-reasons-details">
                        <summary class="text-secondary small cursor-pointer">
                            <i class="ti ti-info-circle me-1"></i>匹配原因（{{ count($reasons) }} 条）
                        </summary>
                        <div class="mt-1 ms-3">
                            @foreach($reasons as $reason)
                                <div class="small text-secondary">
                                    <i class="ti ti-point-filled text-primary me-1"></i>{{ $reason }}
                                </div>
                            @endforeach
                        </div>
                    </details>
                </div>
                @endif

                @include('user.job-recommendations.partials.skill-gap-list')
            </div>
            <div class="ms-auto d-flex flex-column gap-1">
                @if($safeUrl)
                    <a href="{{ $safeUrl }}"
                       target="_blank" rel="noopener noreferrer"
                       class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-external-link me-1"></i>查看
                    </a>
                @endif
                @if(!$isApplied)
                    <button class="btn btn-sm btn-success apply-btn"
                            data-id="{{ $recommendation->id }}">
                        <i class="ti ti-check me-1"></i>已投递
                    </button>
                @endif
                {{-- #28 看相似岗位 --}}
                <a href="{{ route('user.recommendations.index', ['similar_to' => $recommendation->id, 'company' => $recommendation->company]) }}#similar"
                   class="btn btn-sm btn-outline-info similar-btn">
                    <i class="ti ti-clipboard-list me-1"></i>看相似
                </a>
                <button class="btn btn-sm btn-outline-secondary dismiss-btn"
                        data-id="{{ $recommendation->id }}">
                    <i class="ti ti-x me-1"></i>忽略
                </button>
            </div>
        </div>
    </div>
</div>

