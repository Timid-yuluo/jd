@extends('layouts.user')

@section('title', '订单历史')
@section('page-pretitle', '会员中心')
@section('page-title', '订单历史')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        {{-- 订阅订单 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">订阅订单</h3>
            </div>
            <div class="card-body">
                @if($subscriptionOrders->isEmpty())
                    <div class="text-center py-4 text-secondary">暂无订阅订单</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>订单号</th>
                                    <th>套餐</th>
                                    <th>金额</th>
                                    <th>支付方式</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscriptionOrders as $order)
                                    <tr>
                                        <td class="font-monospace small">{{ $order->order_no }}</td>
                                        <td>{{ $order->plan->name ?? '-' }}</td>
                                        <td>¥{{ $order->amount / 100 }}</td>
                                        <td>
                                            @if($order->payment_method === 'wechat')
                                                微信支付
                                            @elseif($order->payment_method === 'alipay')
                                                支付宝
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'pending' => ['warning', '待支付'],
                                                    'paid' => ['success', '已支付'],
                                                    'cancelled' => ['secondary', '已取消'],
                                                    'expired' => ['secondary', '已过期'],
                                                    'refunded' => ['info', '已退款'],
                                                ];
                                                $s = $statusMap[$order->status] ?? ['secondary', $order->status];
                                            @endphp
                                            <span class="badge bg-{{ $s[0] }}-lt">{{ $s[1] }}</span>
                                        </td>
                                        <td class="small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($order->status === 'pending')
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('user.membership.payment', $order->order_no) }}" class="btn btn-sm btn-outline-primary">继续支付</a>
                                                    <form action="{{ route('user.membership.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('确定取消此订单？')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">取消</button>
                                                    </form>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $subscriptionOrders->withQueryString()->links() }}
                @endif
            </div>
        </div>

        {{-- 次卡订单 --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">次卡订单</h3>
            </div>
            <div class="card-body">
                @if($creditPackOrders->isEmpty())
                    <div class="text-center py-4 text-secondary">暂无次卡订单</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>订单号</th>
                                    <th>次卡</th>
                                    <th>金额</th>
                                    <th>支付方式</th>
                                    <th>状态</th>
                                    <th>时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($creditPackOrders as $order)
                                    <tr>
                                        <td class="font-monospace small">{{ $order->order_no }}</td>
                                        <td>{{ $order->creditPack->name ?? '-' }}</td>
                                        <td>¥{{ $order->amount / 100 }}</td>
                                        <td>
                                            @if($order->payment_method === 'wechat')
                                                微信支付
                                            @elseif($order->payment_method === 'alipay')
                                                支付宝
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'pending' => ['warning', '待支付'],
                                                    'paid' => ['success', '已支付'],
                                                    'cancelled' => ['secondary', '已取消'],
                                                    'expired' => ['secondary', '已过期'],
                                                ];
                                                $s = $statusMap[$order->status] ?? ['secondary', $order->status];
                                            @endphp
                                            <span class="badge bg-{{ $s[0] }}-lt">{{ $s[1] }}</span>
                                        </td>
                                        <td class="small">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($order->status === 'pending')
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('user.membership.credit-payment', $order->order_no) }}" class="btn btn-sm btn-outline-primary">继续支付</a>
                                                    <form action="{{ route('user.membership.credit-orders.cancel', $order) }}" method="POST" onsubmit="return confirm('确定取消此订单？')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">取消</button>
                                                    </form>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $creditPackOrders->withQueryString()->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
