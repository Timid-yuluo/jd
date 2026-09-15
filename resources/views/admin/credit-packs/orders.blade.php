@extends('layouts.admin')

@section('title', '次卡订单')
@section('page-pretitle', '会员管理')
@section('page-title', '次卡订单')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-receipt"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">总订单</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-success text-white avatar"><i class="ti ti-check"></i></span></div>
                    <div class="col"><div class="font-weight-medium text-success">{{ number_format($stats['paid']) }}</div><div class="text-secondary">已支付</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-warning text-white avatar"><i class="ti ti-clock"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['pending']) }}</div><div class="text-secondary">待支付</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-currency-yuan"></i></span></div>
                    <div class="col"><div class="font-weight-medium">&yen;{{ number_format($stats['revenue'] / 100, 2) }}</div><div class="text-secondary">累计营收</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">次卡订单列表</h3>
        <div class="card-actions">
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索订单号/用户" value="{{ request('search') }}" style="min-width: 160px;">
                <select name="status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">全部状态</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>待支付</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>已支付</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>已过期</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-search me-1"></i>筛选</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('admin.credit-packs.orders') }}" class="btn btn-sm btn-secondary">重置</a>
                @endif
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>订单号</th>
                    <th>用户</th>
                    <th>次卡</th>
                    <th>金额</th>
                    <th>状态</th>
                    <th>时间</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-monospace small">{{ $order->order_no }}</td>
                        <td>
                            @if($order->user)
                            <a href="{{ route('admin.users.show', $order->user) }}">{{ $order->user->name }}</a>
                            @else - @endif
                        </td>
                        <td>{{ $order->creditPack->name ?? '-' }}</td>
                        <td>&yen;{{ number_format($order->amount / 100, 2) }}</td>
                        <td>
                            @php
                                $s = match($order->status) {
                                    'pending' => ['warning', '待支付'],
                                    'paid' => ['success', '已支付'],
                                    'expired' => ['secondary', '已过期'],
                                    default => ['secondary', $order->status],
                                };
                            @endphp
                            <span class="badge bg-{{ $s[0] }}-lt">{{ $s[1] }}</span>
                        </td>
                        <td class="small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-secondary">暂无订单</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
