<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CreditPackOrder;
use App\Models\Order;
use App\Services\Membership\AlipayF2FPaymentService;
use App\Services\Membership\CreditService;
use App\Services\Membership\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AlipayPayController extends Controller
{
    public function __construct(
        private readonly AlipayF2FPaymentService $alipayF2FPaymentService,
        private readonly SubscriptionService $subscriptionService,
        private readonly CreditService $creditService,
    ) {}

    public function notify(Request $request): Response
    {
        $payload = $request->all();

        if (! $this->alipayF2FPaymentService->verifySign($payload)) {
            Log::warning('Alipay notify sign verify failed', [
                'out_trade_no' => $request->input('out_trade_no'),
            ]);

            return response('fail', 400);
        }

        $tradeStatus = (string) $request->input('trade_status', '');
        if (! in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
            return response('success');
        }

        $outTradeNo = trim((string) $request->input('out_trade_no', ''));
        if ($outTradeNo === '') {
            return response('fail', 400);
        }

        $paymentNo = trim((string) $request->input('trade_no', ''));
        $totalAmountYuan = (string) $request->input('total_amount', '');
        $paidAmountFen = $this->yuanToFen($totalAmountYuan);

        try {
            $subscriptionOrder = Order::query()->where('order_no', $outTradeNo)->first();
            if ($subscriptionOrder instanceof Order) {
                $this->subscriptionService->fulfillOrder(
                    $subscriptionOrder,
                    $paymentNo !== '' ? $paymentNo : null,
                    $paidAmountFen,
                    'alipay'
                );

                return response('success');
            }

            $creditPackOrder = CreditPackOrder::query()->where('order_no', $outTradeNo)->first();
            if ($creditPackOrder instanceof CreditPackOrder) {
                $this->creditService->fulfillOrder(
                    $creditPackOrder,
                    $paymentNo !== '' ? $paymentNo : null,
                    $paidAmountFen,
                    'alipay'
                );

                return response('success');
            }

            Log::warning('Alipay notify order not found', ['out_trade_no' => $outTradeNo]);

            return response('fail', 404);
        } catch (Throwable $e) {
            Log::error('Alipay notify handle failed', [
                'out_trade_no' => $outTradeNo,
                'message' => $e->getMessage(),
            ]);

            return response('fail', 500);
        }
    }

    private function yuanToFen(string $amountYuan): ?int
    {
        $amountYuan = trim($amountYuan);
        if ($amountYuan === '') {
            return null;
        }

        return (int) bcmul($amountYuan, '100', 0);
    }
}
