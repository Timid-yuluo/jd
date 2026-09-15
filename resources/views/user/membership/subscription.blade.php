@extends('layouts.user')

@section('title', '我的订阅')
@section('page-pretitle', '会员中心')
@section('page-title', '我的订阅')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if($activeSubscription)
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">当前订阅</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-secondary small">套餐</div>
                            <div class="fw-semibold">{{ $currentPlan->name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">计费周期</div>
                            <div class="fw-semibold">{{ $activeSubscription->billing_cycle === 'yearly' ? '年付' : '月付' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">状态</div>
                            <span class="badge bg-{{ $activeSubscription->status === 'active' ? 'success' : ($activeSubscription->status === 'cancelled' ? 'warning' : 'secondary') }}">
                                {{ $activeSubscription->status === 'active' ? '生效中' : ($activeSubscription->status === 'cancelled' ? '已取消（到期后失效）' : $activeSubscription->status) }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">到期时间</div>
                            <div>{{ $activeSubscription->ends_at?->format('Y-m-d') ?? '永久' }}</div>
                        </div>
                        @if($activeSubscription->cancelled_at)
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    订阅已取消，将于 {{ $activeSubscription->ends_at?->format('Y-m-d') }} 到期。到期后自动降级为免费版。
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if($activeSubscription->status === 'active' && $currentPlan->slug !== 'free')
                    <div class="card-footer">
                        <div class="btn-list">
                            <a href="{{ route('user.membership.pricing') }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-arrow-up me-1"></i>升级套餐
                            </a>
                            <a href="{{ route('user.membership.usage') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-chart-bar me-1"></i>查看用量
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="ti ti-crown-off text-secondary fs-1 mb-3"></i>
                    <h4>暂无活跃订阅</h4>
                    <p class="text-secondary mb-3">你当前使用的是免费版套餐</p>
                    <a href="{{ route('user.membership.pricing') }}" class="btn btn-primary">
                        <i class="ti ti-crown me-1"></i>查看套餐
                    </a>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">当前套餐权益</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <span class="badge bg-primary-lt fs-5 px-3 py-2">{{ $currentPlan->name }}</span>
                </div>
                <div class="row g-2">
                    @foreach($currentPlan->quotas as $key => $limit)
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>{{ \App\Models\UserCredit::quotaLabel($key) }}</span>
                                <span class="fw-semibold">{{ $limit === -1 ? '不限' : $limit . '次/月' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
