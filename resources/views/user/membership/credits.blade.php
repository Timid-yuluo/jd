@extends('layouts.user')

@section('title', '我的次卡')
@section('page-pretitle', '会员中心')
@section('page-title', '我的次卡')

@section('content')
@php
    $resolveQuotaLabel = fn (?string $quotaKey): string => \App\Models\UserCredit::quotaLabel($quotaKey);
    $resolveUsageText = fn (?string $quotaKey): string => \App\Models\UserCredit::usageDescription($quotaKey);
    $resolveFeatureLabels = fn (?string $quotaKey): array => \App\Models\UserCredit::keyFeatureLabels($quotaKey);
@endphp
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">次卡和会员月配额如何配合？</div>
            <div class="small text-secondary">
                系统会优先使用当前套餐的月配额；当月配额用完后，如你仍有专用次卡或通用次卡，可在对应功能里选择次卡继续执行。
            </div>
        </div>

        @if(!empty($summary))
            <div class="row row-cards g-3 mb-4">
                @foreach($summary as $groupKey => $item)
                    @php
                        $isUniversalGroup = $groupKey === 'universal';
                    @endphp
                    <div class="col-sm-6 col-lg-4">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="avatar {{ $isUniversalGroup ? 'bg-azure-lt' : 'bg-primary-lt' }}">
                                            <i class="ti {{ $isUniversalGroup ? 'ti-infinity' : 'ti-ticket' }}"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $resolveQuotaLabel($groupKey === 'universal' ? null : $groupKey) }}</div>
                                        <div class="text-secondary small">剩余 {{ (int) ($item['total'] ?? 0) }} 次</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">次卡明细</h3>
                    <div class="card-actions">
                        <a href="{{ route('user.membership.credits.history') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-history me-1"></i>使用记录</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($summary as $groupKey => $group)
                            @php
                                $quotaKey = $groupKey === 'universal' ? null : $groupKey;
                                $groupLabel = $resolveQuotaLabel($quotaKey);
                            @endphp
                            @foreach($group['items'] as $credit)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <span class="badge {{ $quotaKey === null ? 'bg-azure-lt text-azure' : 'bg-primary-lt text-primary' }}">
                                                    {{ $groupLabel }}
                                                </span>
                                                @if($credit->packName())
                                                    <span class="fw-semibold">{{ $credit->packName() }}</span>
                                                @else
                                                    <span class="fw-semibold">{{ $quotaKey === null ? '通用次卡' : $groupLabel . '专用次卡' }}</span>
                                                @endif
                                            </div>
                                            <div class="text-secondary small mb-1">{{ $resolveUsageText($quotaKey) }}</div>
                                            <div class="small">
                                                @if($credit->expires_at)
                                                    <span class="text-secondary">到期时间：{{ $credit->expires_at->format('Y-m-d H:i') }}</span>
                                                @else
                                                    <span class="text-secondary">到期时间：永久有效</span>
                                                @endif
                                            </div>
                                            <div class="mt-2">
                                                @foreach($resolveFeatureLabels($quotaKey) as $featureLabel)
                                                    <span class="badge bg-secondary-lt text-secondary mb-1">{{ $featureLabel }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold text-primary">剩余 {{ $credit->remaining }} 次</div>
                                            @if($credit->isUniversal())
                                                <div class="small text-success">通用次卡：可在多种 AI 功能额度用尽后继续使用</div>
                                            @else
                                                <div class="small text-warning">专用次卡：仅在对应功能额度用尽后可继续使用</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <div class="fw-semibold mb-1">如何判断通用次卡和专用次卡？</div>
                <div class="small text-secondary">
                    通用次卡可用于 AI 岗位定向优化、ATS 评分、关键词提取、AI 面试场次、面试评估额度、岗位匹配等功能；
                    专用次卡只能用于对应功能，例如「AI 面试」专用次卡不能用于 ATS 评分。
                </div>
            </div>
        @else
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="ti ti-ticket-off text-secondary fs-1 mb-3"></i>
                    <h4>暂无次卡</h4>
                    <p class="text-secondary mb-3">购买次卡可以在月配额用完后继续使用</p>
                </div>
            </div>
        @endif

        <div class="text-center">
            <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-primary">
                <i class="ti ti-shopping-cart me-1"></i>购买次卡
            </a>
        </div>
    </div>
</div>
@endsection
