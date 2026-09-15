@extends('layouts.user')

@section('title', '推荐详情')
@section('page-pretitle', '岗位推荐')
@section('page-title', '推荐详情')

@section('page-actions')
    <a href="{{ route('user.recommendations.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>返回列表
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        {{-- 岗位基本信息 --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="flex-fill">
                        <h2 class="mb-1">{{ $recommendation->job_title }}</h2>
                        <div class="text-secondary mt-2">
                            <span class="me-3"><i class="ti ti-building me-1"></i>{{ $recommendation->company }}</span>
                            @if($recommendation->city)
                                <span class="me-3"><i class="ti ti-map-pin me-1"></i>{{ $recommendation->city }}</span>
                            @endif
                            <span><i class="ti ti-calendar me-1"></i>{{ $recommendation->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-{{ $recommendation->match_score >= 80 ? 'success' : ($recommendation->match_score >= 60 ? 'warning' : 'secondary') }}-lt fs-2">
                            {{ $recommendation->match_score }}
                        </div>
                        <div class="text-secondary small">匹配分</div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    @if($safeUrl)
                        <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <i class="ti ti-external-link me-1"></i>查看原岗位
                        </a>
                    @endif
                    <button class="btn {{ $recommendation->favorited_at ? 'btn-warning' : 'btn-outline-warning' }} favorite-btn"
                            data-id="{{ $recommendation->id }}">
                        <i class="ti ti-star me-1"></i>{{ $recommendation->favorited_at ? '已收藏' : '收藏' }}
                    </button>
                    @if($recommendation->status !== \App\Models\JobRecommendation::STATUS_APPLIED)
                        <button class="btn btn-success apply-btn" data-id="{{ $recommendation->id }}">
                            <i class="ti ti-check me-1"></i>标记已投递
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- 匹配原因 --}}
        @if(!empty($recommendation->match_reasons))
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-bulb me-1 text-primary"></i>匹配原因</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($recommendation->match_reasons as $reason)
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i>{{ $reason }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- 技能缺口 --}}
        @if(!empty($recommendation->skill_gaps))
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-alert-triangle me-1 text-warning"></i>技能缺口</h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($recommendation->skill_gaps as $gap)
                        <li class="mb-2">
                            <i class="ti ti-arrow-right text-warning me-1"></i>{{ $gap }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- 右侧元信息 --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-info-circle me-1"></i>推荐信息</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="text-secondary">状态：</span>
                    @switch($recommendation->status)
                        @case('new') <span class="badge bg-primary">新推荐</span> @break
                        @case('applied') <span class="badge bg-success">已投递</span> @break
                        @case('dismissed') <span class="badge bg-secondary">已忽略</span> @break
                        @default <span class="badge bg-secondary">{{ $recommendation->status }}</span>
                    @endswitch
                </div>
                <div class="mb-2">
                    <span class="text-secondary">查看时间：</span>
                    {{ $recommendation->viewed_at ? $recommendation->viewed_at->format('Y-m-d H:i') : '尚未查看' }}
                </div>
                <div class="mb-2">
                    <span class="text-secondary">收藏时间：</span>
                    {{ $recommendation->favorited_at ? $recommendation->favorited_at->format('Y-m-d H:i') : '未收藏' }}
                </div>
                <div class="mb-2">
                    <span class="text-secondary">推荐时间：</span>
                    {{ $recommendation->created_at->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelector('.favorite-btn')?.addEventListener('click', function() {
        fetch('/user/recommendations/' + this.dataset.id + '/favorite', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });

    document.querySelector('.apply-btn')?.addEventListener('click', function() {
        fetch('/user/recommendations/' + this.dataset.id + '/apply', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
        });
    });
});
</script>
@endpush
