@extends('layouts.user')

@section('title', '套餐定价')
@section('page-pretitle', '会员中心')
@section('page-title', '选择套餐')

@section('content')
@php
    $plansBySlug = collect($plans ?? [])->keyBy('slug');

    $planMeta = [
        'free' => [
            'name' => '免费版',
            'tagline' => '体验引流',
            'audience' => '初次体验',
            'badge' => null,
        ],
        'basic' => [
            'name' => '基础版',
            'tagline' => '求职主力',
            'audience' => '应届/轻度求职',
            'badge' => '推荐',
        ],
        'pro' => [
            'name' => '专业版',
            'tagline' => '高频深度',
            'audience' => '跳槽/高频优化',
            'badge' => '专业',
        ],
    ];

    $sections = [
        'AI 简历与定向优化' => [
            ['label' => '简历数量', 'values' => ['free' => '1 份', 'basic' => '3 份', 'pro' => '不限']],
            ['label' => 'AI 岗位定向优化（全文）', 'values' => ['free' => '3 次/月', 'basic' => '20 次/月', 'pro' => '不限']],
            ['label' => '分段优化', 'values' => ['free' => '5 次/月', 'basic' => '30 次/月', 'pro' => '不限']],
            ['label' => 'ATS 评分', 'values' => ['free' => '3 次/月', 'basic' => '20 次/月', 'pro' => '不限']],
            ['label' => '关键词提取', 'values' => ['free' => '5 次/月', 'basic' => '不限', 'pro' => '不限']],
            ['label' => '导出 PDF', 'values' => ['free' => '3 次/月', 'basic' => '不限', 'pro' => '不限']],
            ['label' => 'AI 导入简历', 'values' => ['free' => '1 次/月', 'basic' => '10 次/月', 'pro' => '不限']],
        ],
        'AI 面试' => [
            ['label' => '模拟面试场次', 'values' => ['free' => '2 场/月', 'basic' => '15 场/月', 'pro' => '不限']],
            ['label' => '单场最大题数', 'values' => ['free' => '5 题', 'basic' => '10 题', 'pro' => '15 题']],
            ['label' => '面试评估额度', 'values' => ['free' => '2 次/月', 'basic' => '15 次/月', 'pro' => '不限']],
            ['label' => 'JD 定制题目', 'values' => ['free' => '❌', 'basic' => '✅', 'pro' => '✅']],
            ['label' => '岗位专项练习', 'values' => ['free' => '❌', 'basic' => '3 个岗位', 'pro' => '不限']],
        ],
        '岗位匹配' => [
            ['label' => 'AI 匹配推荐', 'values' => ['free' => '5 次/月', 'basic' => '30 次/月', 'pro' => '不限']],
            ['label' => '匹配度分析', 'values' => ['free' => '5 次/月', 'basic' => '30 次/月', 'pro' => '不限']],
            ['label' => '简历-岗位对比', 'values' => ['free' => '3 次/月', 'basic' => '20 次/月', 'pro' => '不限']],
            ['label' => '投递跟踪', 'values' => ['free' => '5 条', 'basic' => '50 条', 'pro' => '不限']],
            ['label' => '投递提醒', 'values' => ['free' => '❌', 'basic' => '✅', 'pro' => '✅']],
        ],
        '通用权益' => [
            ['label' => 'AI 优先队列', 'values' => ['free' => '❌', 'basic' => '❌', 'pro' => '✅']],
            ['label' => '高级模型', 'values' => ['free' => '❌', 'basic' => '❌', 'pro' => '✅']],
            ['label' => '专属客服', 'values' => ['free' => '❌', 'basic' => '❌', 'pro' => '✅']],
            ['label' => '导出无水印', 'values' => ['free' => 'PDF3次无水印', 'basic' => '✅', 'pro' => '✅']],
        ],
    ];
@endphp

<div class="row">
    <div class="col-12">
        <div class="text-center mb-4">
            <h2>会员套餐对比</h2>
            <p class="text-secondary mb-2">免费版、基础版、专业版三档，按文档标准能力展示</p>
            <p class="text-secondary small mb-0">次卡可作为补充：月配额用完后可单独购买继续使用</p>
            <p class="text-secondary small mb-0">AI 面试会先消耗「模拟面试场次」，每次提交回答还会额外消耗 1 次「面试评估额度」。</p>
        </div>

        <div class="row row-cards g-4">
            @foreach (['free', 'basic', 'pro'] as $slug)
                @php
                    $meta = $planMeta[$slug];
                    $plan = $plansBySlug->get($slug);
                    $isCurrent = $currentPlan && $currentPlan->slug === $slug;
                    $isFree = $slug === 'free';
                    $hasPlanEntity = $plan !== null;
                    $displayName = $plan->name ?? $meta['name'];
                    $monthlyAmount = $plan ? (int) $plan->price_monthly : null;
                    $yearlyAmount = $plan ? (int) $plan->price_yearly : null;
                    $monthlyLabel = $isFree
                        ? '¥0'
                        : (($monthlyAmount !== null && $monthlyAmount > 0)
                            ? ('¥' . number_format($monthlyAmount / 100, 2) . '/月')
                            : '待配置');

                    $yearlyLabel = '-';
                    if (! $isFree && $yearlyAmount !== null && $yearlyAmount > 0) {
                        $yearlyLabel = '¥' . number_format($yearlyAmount / 100, 2) . '/年';

                        if ($monthlyAmount !== null && $monthlyAmount > 0) {
                            $yearlyOrigin = $monthlyAmount * 12;
                            if ($yearlyAmount < $yearlyOrigin) {
                                $savingRate = (int) round((1 - $yearlyAmount / $yearlyOrigin) * 100);
                                $yearlyLabel .= '（省' . $savingRate . '%）';
                            }
                        }
                    }
                @endphp
                <div class="col-sm-6 col-lg-4">
                    <div class="card h-100 {{ $slug === 'basic' ? 'border-primary' : '' }} {{ $isCurrent ? 'bg-primary-lt' : '' }}">
                        @if($meta['badge'])
                            <div class="ribbon ribbon-top bg-primary"><span>{{ $meta['badge'] }}</span></div>
                        @endif
                        @if($isCurrent)
                            <div class="ribbon ribbon-top bg-success"><span>当前</span></div>
                        @endif
                        <div class="card-body text-center">
                            <div class="mb-2 text-secondary">{{ $meta['tagline'] }}</div>
                            <h3 class="mb-2">{{ $displayName }}</h3>
                            <div class="h2 mb-1">{{ $monthlyLabel }}</div>
                            <div class="text-secondary small mb-1">{{ $yearlyLabel }}</div>
                            <div class="text-secondary small mb-4">适合人群：{{ $meta['audience'] }}</div>

                            @if ($isFree)
                                @if ($isCurrent)
                                    <button class="btn btn-outline-secondary w-100" disabled>当前套餐</button>
                                @else
                                    <button class="btn btn-outline-secondary w-100" disabled>免费版默认可用</button>
                                @endif
                            @else
                                @if (! $hasPlanEntity)
                                    <button class="btn btn-outline-secondary w-100" disabled>套餐暂未上架</button>
                                @elseif($isCurrent && $activeSubscription)
                                    <button class="btn btn-outline-secondary w-100" disabled>当前套餐（{{ $activeSubscription->billing_cycle === 'yearly' ? '年付' : '月付' }}）</button>
                                @else
                                    <form method="POST" action="{{ route('user.membership.subscribe') }}" class="d-grid gap-2">
                                        @csrf
                                        <input type="hidden" name="plan_slug" value="{{ $slug }}">
                                        <input type="hidden" name="billing_cycle" value="monthly">
                                        <button type="submit" class="btn {{ $slug === 'basic' ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                            月付开通
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('user.membership.subscribe') }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="plan_slug" value="{{ $slug }}">
                                        <input type="hidden" name="billing_cycle" value="yearly">
                                        <button type="submit" class="btn btn-outline-secondary w-100">年付开通</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title mb-0">套餐能力详细对比</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th style="min-width: 180px;">功能项</th>
                            <th>免费版</th>
                            <th>基础版</th>
                            <th>专业版</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sections as $sectionName => $rows)
                            <tr>
                                <td colspan="4" class="bg-light fw-bold text-secondary">{{ $sectionName }}</td>
                            </tr>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['values']['free'] }}</td>
                                    <td>{{ $row['values']['basic'] }}</td>
                                    <td>{{ $row['values']['pro'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-outline-primary">
                <i class="ti ti-ticket me-1"></i>月配额不够？去购买次卡
            </a>
        </div>
    </div>
</div>
@endsection
