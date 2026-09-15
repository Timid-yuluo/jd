@extends('layouts.user')

@section('title', '订单支付')
@section('page-pretitle', '会员中心')
@section('page-title', '订单支付')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">订阅订单</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-secondary small">订单号</div>
                            <div class="fw-semibold">{{ $order->order_no }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small">套餐</div>
                            <div class="fw-semibold">{{ $order->plan->name ?? '-' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small">计费周期</div>
                            <div>{{ $order->billing_cycle === 'yearly' ? '年付' : '月付' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary small">金额</div>
                            <div class="fw-bold text-primary">¥{{ $order->amount / 100 }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-secondary small">状态</div>
                            <span class="badge bg-warning-lt">待支付</span>
                        </div>
                    </div>
                </div>

                @php $methodLabels = ['alipay' => '支付宝当面付']; @endphp
                @if(!empty($enabledMethods))
                    <form method="POST" action="{{ route('user.membership.payment.method', $order->order_no) }}" class="mb-3">
                        @csrf
                        <label class="form-label">支付方式</label>
                        <div class="row g-2">
                            @foreach($enabledMethods as $method)
                                <div class="col-6">
                                    <label class="form-selectgroup-item">
                                        <input type="radio" name="payment_method" value="{{ $method }}" class="form-selectgroup-input" {{ ($selectedMethod ?? '') === $method ? 'checked' : '' }}>
                                        <span class="form-selectgroup-label d-flex align-items-center justify-content-between">
                                            <span>{{ $methodLabels[$method] ?? strtoupper($method) }}</span>
                                            @if(($selectedMethod ?? '') === $method)
                                                <i class="ti ti-check text-primary"></i>
                                            @endif
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm mt-3">保存支付方式</button>
                    </form>
                    @if(($selectedMethod ?? '') === 'alipay' && !empty($alipayQrcode['qr_code']))
                        <div class="alert alert-success">
                            <i class="ti ti-scan me-1"></i>请使用支付宝扫码支付，支付成功后页面可刷新查看最新状态。
                        </div>
                        <div class="border rounded-3 p-3 text-center">
                            <div id="alipay-qrcode" class="d-inline-block"></div>
                            <div class="text-secondary small mt-2">订单号：{{ $alipayQrcode['out_trade_no'] ?? $order->order_no }}</div>
                        </div>
                    @elseif(!empty($alipayError))
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-1"></i>付款码生成失败：{{ $alipayError }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        暂无可用支付方式，请联系管理员在后台开启支付宝支付。
                    </div>
                @endif

                <div class="text-center mt-3">
                    <a href="{{ route('user.membership.orders') }}" class="text-secondary small">查看订单历史</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(($selectedMethod ?? '') === 'alipay' && !empty($alipayQrcode['qr_code']))
    <script src="{{ asset('js/vendor/qrcode.min.js') }}"></script>
    <script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('alipay-qrcode');
            if (!container || typeof QRCode === 'undefined') {
                return;
            }
            new QRCode(container, {
                text: @json($alipayQrcode['qr_code']),
                width: 220,
                height: 220
            });
        });
    </script>
@endif
@endpush
