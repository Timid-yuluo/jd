<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CreditPack;
use App\Models\CreditPackOrder;
use App\Models\CreditUsageLog;
use App\Models\Order;
use App\Models\Plan;
use App\Models\QuotaUsage;
use App\Models\UserCredit;
use App\Services\Admin\SystemSettingService;
use App\Services\Membership\AlipayF2FPaymentService;
use App\Services\Membership\CreditService;
use App\Services\Membership\QuotaService;
use App\Services\Membership\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class MembershipController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
    ) {}

    /**
     * 套餐对比页 /pricing
     */
    public function pricing(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        try {
            $plans = Plan::getActivePlans();
            $currentPlan = $user?->currentPlan();
            $activeSubscription = $user?->activeSubscription;
        } catch (\Throwable $e) {
            Log::error('Load membership pricing failed', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('user.dashboard')
                ->with('error', '套餐页面暂时不可用，请稍后重试。');
        }

        return view('user.membership.pricing', compact('plans', 'currentPlan', 'activeSubscription'));
    }

    /**
     * 我的订阅
     */
    public function mySubscription(): RedirectResponse
    {
        return redirect()
            ->route('user.profile', ['tab' => 'membership'])
            ->with('success', '已切换到个人中心「我的会员」');
    }

    /**
     * 我的次卡
     */
    public function myCredits(Request $request, CreditService $creditService): View
    {
        $user = $request->user();
        $summary = $creditService->getUserCreditsSummary($user);

        return view('user.membership.credits', compact('summary'));
    }

    public function creditUsageHistory(Request $request): View
    {
        $user = $request->user();
        $logs = CreditUsageLog::where('user_id', $user->id)
            ->with('credit')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('user.membership.credit-history', compact('logs'));
    }

    /**
     * 次卡商城
     */
    public function creditPacks(): View
    {
        $packs = CreditPack::getActivePacks();

        return view('user.membership.credit-packs', compact('packs'));
    }

    /**
     * 用量统计
     */
    public function usage(Request $request, QuotaService $quotaService): View
    {
        $user = $request->user();
        $plan = $user->currentPlan();
        $period = QuotaUsage::currentPeriod();

        $quotaKeys = [
            'optimize_full',
            'optimize_section',
            'ats_score',
            'keywords_extract',
            'export_pdf',
            'import_document',
            'interview_sessions',
            'interview_evaluation',
            'job_match',
            'match_analysis',
            'resume_job_compare',
        ];

        $bulkUsage = $quotaService->getBulkMonthlyUsage($user->id, $quotaKeys, $period);

        $usages = [];
        foreach ($quotaKeys as $key) {
            $limit = $plan->getQuotaLimit($key);
            $used = $bulkUsage[$key] ?? 0;
            $usages[] = [
                'key' => $key,
                'label' => UserCredit::quotaLabel($key),
                'limit' => $limit,
                'used' => $used,
                'unlimited' => $limit === -1,
            ];
        }

        return view('user.membership.usage', compact('usages', 'period'));
    }

    /**
     * 创建订阅订单
     */
    public function subscribe(Request $request, SubscriptionService $subscriptionService): RedirectResponse
    {
        $validated = $request->validate([
            'plan_slug' => 'required|exists:plans,slug',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'nullable|in:alipay',
        ]);

        $plan = Plan::findBySlug($validated['plan_slug']);

        if (! $plan || ! $plan->is_active) {
            return back()->with('error', '套餐不存在或已下架');
        }

        if ($plan->slug === 'free') {
            return back()->with('error', '免费版无需订阅');
        }

        $enabledMethods = $this->enabledPaymentMethods();
        if ($enabledMethods === []) {
            return back()->with('error', '暂未开通支付方式，请稍后再试');
        }

        $order = $subscriptionService->createOrder($request->user(), $plan, $validated['billing_cycle']);
        $preferredMethod = $this->resolvePreferredPaymentMethod(
            is_string($validated['payment_method'] ?? null) ? $validated['payment_method'] : null,
            $enabledMethods
        );
        $order->payment_method = $preferredMethod;
        $order->save();

        // TODO: 接入真实支付下单流程（微信/支付宝）
        return redirect()->route('user.membership.payment', $order->order_no);
    }

    /**
     * 支付页面
     */
    public function payment(Request $request, string $orderNo, AlipayF2FPaymentService $alipayF2FPaymentService): View
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->where('status', Order::STATUS_PENDING)
            ->firstOrFail();

        $enabledMethods = $this->enabledPaymentMethods();
        $selectedMethod = $this->resolvePreferredPaymentMethod($order->payment_method, $enabledMethods);
        if ($selectedMethod !== $order->payment_method) {
            $order->payment_method = $selectedMethod;
        $order->save();
        }

        $alipayQrcode = null;
        $alipayError = null;
        if ($selectedMethod === 'alipay') {
            try {
                $alipayQrcode = $alipayF2FPaymentService->precreateSubscription($order);
            } catch (\Throwable $e) {
                Log::warning('Create alipay subscription qrcode failed', [
                    'order_no' => $order->order_no,
                    'message' => $e->getMessage(),
                ]);
                $alipayError = $e->getMessage();
            }
        }

        return view('user.membership.payment', compact('order', 'enabledMethods', 'selectedMethod', 'alipayQrcode', 'alipayError'));
    }

    /**
     * 创建次卡订单
     */
    public function purchaseCreditPack(Request $request, CreditService $creditService): RedirectResponse
    {
        $validated = $request->validate([
            'credit_pack_id' => 'required|exists:credit_packs,id',
            'payment_method' => 'nullable|in:alipay',
        ]);

        $pack = CreditPack::findOrFail($validated['credit_pack_id']);

        if (! $pack->is_active) {
            return back()->with('error', '该次卡已下架');
        }

        $enabledMethods = $this->enabledPaymentMethods();
        if ($enabledMethods === []) {
            return back()->with('error', '暂未开通支付方式，请稍后再试');
        }

        $order = $creditService->createOrder($request->user(), $pack);
        $preferredMethod = $this->resolvePreferredPaymentMethod(
            is_string($validated['payment_method'] ?? null) ? $validated['payment_method'] : null,
            $enabledMethods
        );
        $order->payment_method = $preferredMethod;
        $order->save();

        // TODO: 接入真实支付下单流程（微信/支付宝）
        return redirect()->route('user.membership.credit-payment', $order->order_no);
    }

    /**
     * 次卡支付页面
     */
    public function creditPayment(Request $request, string $orderNo, AlipayF2FPaymentService $alipayF2FPaymentService): View
    {
        $order = CreditPackOrder::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->where('status', CreditPackOrder::STATUS_PENDING)
            ->firstOrFail();

        $enabledMethods = $this->enabledPaymentMethods();
        $selectedMethod = $this->resolvePreferredPaymentMethod($order->payment_method, $enabledMethods);
        if ($selectedMethod !== $order->payment_method) {
            $order->payment_method = $selectedMethod;
        $order->save();
        }

        $alipayQrcode = null;
        $alipayError = null;
        if ($selectedMethod === 'alipay') {
            try {
                $alipayQrcode = $alipayF2FPaymentService->precreateCreditPack($order);
            } catch (\Throwable $e) {
                Log::warning('Create alipay credit-pack qrcode failed', [
                    'order_no' => $order->order_no,
                    'message' => $e->getMessage(),
                ]);
                $alipayError = $e->getMessage();
            }
        }

        return view('user.membership.credit-payment', compact('order', 'enabledMethods', 'selectedMethod', 'alipayQrcode', 'alipayError'));
    }

    public function updateSubscriptionPaymentMethod(Request $request, string $orderNo): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:alipay',
        ]);

        $enabledMethods = $this->enabledPaymentMethods();
        if (! in_array($validated['payment_method'], $enabledMethods, true)) {
            return back()->with('error', '该支付方式暂未开通');
        }

        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->where('status', Order::STATUS_PENDING)
            ->firstOrFail();

        $order->payment_method = $validated['payment_method'];
        $order->save();

        return back()->with('success', '支付方式已更新');
    }

    public function updateCreditPaymentMethod(Request $request, string $orderNo): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:alipay',
        ]);

        $enabledMethods = $this->enabledPaymentMethods();
        if (! in_array($validated['payment_method'], $enabledMethods, true)) {
            return back()->with('error', '该支付方式暂未开通');
        }

        $order = CreditPackOrder::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->where('status', CreditPackOrder::STATUS_PENDING)
            ->firstOrFail();

        $order->payment_method = $validated['payment_method'];
        $order->save();

        return back()->with('success', '支付方式已更新');
    }

    /**
     * 订单历史
     */
    public function orders(Request $request): View
    {
        $user = $request->user();

        $subscriptionOrders = $user->orders()->with('plan')->orderByDesc('id')->paginate((int) config('ui.pagination.user_list', 10), ['*'], 'sub_page');
        $creditPackOrders = $user->creditPackOrders()->with('creditPack')->orderByDesc('id')->paginate((int) config('ui.pagination.user_list', 10), ['*'], 'credit_page');

        return view('user.membership.orders', compact('subscriptionOrders', 'creditPackOrders'));
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', '只能取消待支付的订单。');
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', '订单已取消。');
    }

    public function cancelCreditOrder(Request $request, CreditPackOrder $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', '只能取消待支付的订单。');
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', '订单已取消。');
    }

    /**
     * @return array<int, string>
     */
    private function enabledPaymentMethods(): array
    {
        $settings = $this->systemSettingService->all();
        $methods = [];

        if ((int) ($settings['payment_alipay_enabled'] ?? '0') === 1) {
            $methods[] = 'alipay';
        }

        return $methods;
    }

    /**
     * @param  array<int, string>  $enabledMethods
     */
    private function resolvePreferredPaymentMethod(?string $requested, array $enabledMethods): ?string
    {
        if ($enabledMethods === []) {
            return null;
        }

        if (is_string($requested) && in_array($requested, $enabledMethods, true)) {
            return $requested;
        }

        return $enabledMethods[0];
    }
}
