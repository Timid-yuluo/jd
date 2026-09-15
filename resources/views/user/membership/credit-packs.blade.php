@extends('layouts.user')

@section('title', '次卡商城')
@section('page-pretitle', '会员中心')
@section('page-title', '购买次卡')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="text-center mb-4">
            <h3>购买次卡</h3>
            <p class="text-secondary">月配额用完后，可使用次卡继续操作</p>
            <p class="text-secondary small mb-0">通用次卡适用于多种 AI 功能；专用次卡仅适用于对应功能，例如 AI 面试场次或面试评估额度。</p>
        </div>

        @if($packs->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-ticket-off text-secondary fs-1 mb-3"></i>
                    <p class="text-secondary">暂无可购买的次卡</p>
                </div>
            </div>
        @else
            <div class="row row-cards g-4">
                @foreach($packs as $pack)
                    @php
                        $price = $pack->price / 100;
                        $isUniversal = $pack->quota_key === null;
                        $scopeLabel = $isUniversal ? '通用' : \App\Models\UserCredit::quotaLabel($pack->quota_key);
                    @endphp
                    <div class="col-sm-6 col-lg-4">
                        <div class="card h-100 {{ $isUniversal ? 'border-azure' : '' }}">
                            @if($isUniversal)
                                <div class="ribbon ribbon-top bg-azure"><span>通用</span></div>
                            @endif
                            <div class="card-body text-center">
                                <div class="mb-2">
                                    <span class="badge {{ $isUniversal ? 'bg-azure-lt' : 'bg-primary-lt' }}">
                                        {{ $scopeLabel }}
                                    </span>
                                </div>
                                <h4 class="mb-1">{{ $pack->name }}</h4>
                                <div class="display-5 fw-bold text-primary mb-1">
                                    ¥{{ $price }}
                                </div>
                                <div class="text-secondary mb-3">
                                    {{ $pack->credits }} 次
                                    @if($pack->validity_days > 0)
                                        · 有效期 {{ $pack->validity_days }} 天
                                    @else
                                        · 永久有效
                                    @endif
                                </div>
                                <div class="text-secondary small mb-3">
                                    {{ \App\Models\UserCredit::usageDescription($pack->quota_key) }}
                                </div>
                                <div class="mb-3">
                                    @foreach(\App\Models\UserCredit::keyFeatureLabels($pack->quota_key) as $featureLabel)
                                        <span class="badge bg-secondary-lt text-secondary mb-1">{{ $featureLabel }}</span>
                                    @endforeach
                                </div>

                                <form method="POST" action="{{ route('user.membership.credit-packs.purchase') }}">
                                    @csrf
                                    <input type="hidden" name="credit_pack_id" value="{{ $pack->id }}">
                                    <button type="submit" class="btn {{ $isUniversal ? 'btn-azure' : 'btn-primary' }} w-100">
                                        <i class="ti ti-shopping-cart me-1"></i>购买
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('user.membership.credits') }}" class="text-secondary me-3">
                <i class="ti ti-ticket me-1"></i>我的次卡
            </a>
            <a href="{{ route('user.membership.pricing') }}" class="text-secondary">
                <i class="ti ti-crown me-1"></i>套餐对比
            </a>
        </div>
    </div>
</div>
@endsection
