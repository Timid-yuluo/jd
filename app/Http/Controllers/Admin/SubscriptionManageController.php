<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Membership\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SubscriptionManageController extends Controller
{
    /**
     * 订阅订单列表
     */
    public function orders(Request $request): View
    {
        $query = Order::with(['user', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = escapeLike($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $stats = [
            'total' => Order::count(),
            'paid' => Order::where('status', Order::STATUS_PAID)->count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'revenue' => Order::where('status', Order::STATUS_PAID)->sum('amount'),
        ];

        $orders = $query->orderByDesc('id')->paginate((int) config('ui.pagination.admin_table', 20));

        return view('admin.subscriptions.orders', compact('orders', 'stats'));
    }

    /**
     * 用户订阅管理
     */
    public function userSubscriptions(User $user): View
    {
        $subscriptions = $user->subscriptions()->with('plan')->orderByDesc('id')->get();

        return view('admin.subscriptions.user-subscriptions', compact('user', 'subscriptions'));
    }

    /**
     * 手动给用户激活套餐
     */
    public function activateForm(User $user): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.subscriptions.activate', compact('user', 'plans'));
    }

    public function activate(Request $request, User $user, SubscriptionService $subscriptionService): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::find($validated['plan_id']);
        $subscriptionService->activate($user, $plan, $validated['billing_cycle']);

        return redirect()->route('admin.users.show', $user)->with('success', "已为用户激活{$plan->name}");
    }

    /**
     * 导出订阅订单为 CSV
     */
    public function exportOrders(Request $request): StreamedResponse
    {
        $query = Order::query()->with(['user', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = escapeLike($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $filename = 'subscription_orders_export_'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['订单号', '用户', '邮箱', '套餐', '金额', '状态', '支付时间', '创建时间']);

            $query->chunk(200, function ($orders) use ($handle): void {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->order_no,
                        $order->user?->name ?? '',
                        $order->user?->email ?? '',
                        $order->plan?->name ?? '',
                        $order->amount,
                        $order->status,
                        $order->paid_at?->format('Y-m-d H:i') ?? '',
                        $order->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
