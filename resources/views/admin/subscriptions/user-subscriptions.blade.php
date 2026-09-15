@extends('layouts.admin')

@section('title', '用户订阅')
@section('page-pretitle', '会员管理')
@section('page-title', '用户订阅管理')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-auto">
                <img src="{{ \Illuminate\Support\Str::avatarSvg($user->email, 36) }}" class="avatar" alt="">
            </div>
            <div>
                <div class="fw-semibold">{{ $user->name }}</div>
                <div class="text-secondary small">{{ $user->email }}</div>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.users.activate-plan', $user) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-crown me-1"></i>赠送开通
                </a>
                <a href="{{ route('admin.users.grant-credits', $user) }}" class="btn btn-sm btn-outline-primary ms-1">
                    <i class="ti ti-ticket me-1"></i>赠送次卡
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">订阅记录</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>套餐</th>
                    <th>周期</th>
                    <th>状态</th>
                    <th>开始时间</th>
                    <th>到期时间</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $sub)
                    <tr>
                        <td class="fw-semibold">{{ $sub->plan->name ?? '-' }}</td>
                        <td>{{ $sub->billing_cycle === 'yearly' ? '年付' : '月付' }}</td>
                        <td>
                            @php
                                $s = match($sub->status) {
                                    'active' => ['success', '生效中'],
                                    'cancelled' => ['warning', '已取消'],
                                    'expired' => ['secondary', '已过期'],
                                    default => ['secondary', $sub->status],
                                };
                            @endphp
                            <span class="badge bg-{{ $s[0] }}-lt">{{ $s[1] }}</span>
                        </td>
                        <td class="small">{{ $sub->starts_at?->format('Y-m-d') ?? '-' }}</td>
                        <td class="small">{{ $sub->ends_at?->format('Y-m-d') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
