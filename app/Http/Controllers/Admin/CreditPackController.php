<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditPack;
use App\Models\CreditPackOrder;
use App\Models\User;
use App\Services\Membership\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CreditPackController extends Controller
{
    public function index(): View
    {
        $packs = CreditPack::orderBy('sort_order')->get();

        return view('admin.credit-packs.index', compact('packs'));
    }

    public function create(): View
    {
        return view('admin.credit-packs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:32|unique:credit_packs',
            'name' => 'required|string|max:64',
            'quota_key' => 'nullable|string|max:64',
            'credits' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'validity_days' => 'integer|min:0',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        CreditPack::create($validated);

        return redirect()->route('admin.credit-packs.index')->with('success', '次卡商品创建成功');
    }

    public function edit(CreditPack $creditPack): View
    {
        return view('admin.credit-packs.edit', compact('creditPack'));
    }

    public function update(Request $request, CreditPack $creditPack): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:32', Rule::unique('credit_packs')->ignore($creditPack->id)],
            'name' => 'required|string|max:64',
            'quota_key' => 'nullable|string|max:64',
            'credits' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'validity_days' => 'integer|min:0',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $creditPack->update($validated);

        return redirect()->route('admin.credit-packs.index')->with('success', '次卡商品更新成功');
    }

    public function destroy(CreditPack $creditPack): RedirectResponse
    {
        $creditPack->delete();

        return redirect()->route('admin.credit-packs.index')->with('success', '次卡商品删除成功');
    }

    /**
     * 次卡订单列表
     */
    public function orders(Request $request): View
    {
        $query = CreditPackOrder::with(['user', 'creditPack']);

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
            'total' => CreditPackOrder::count(),
            'paid' => CreditPackOrder::where('status', CreditPackOrder::STATUS_PAID)->count(),
            'pending' => CreditPackOrder::where('status', CreditPackOrder::STATUS_PENDING)->count(),
            'revenue' => CreditPackOrder::where('status', CreditPackOrder::STATUS_PAID)->sum('amount'),
        ];

        $orders = $query->orderByDesc('id')->paginate((int) config('ui.pagination.admin_table', 20));

        return view('admin.credit-packs.orders', compact('orders', 'stats'));
    }

    /**
     * 给用户手动充值次卡
     */
    public function grantForm(User $user): View
    {
        return view('admin.credit-packs.grant', compact('user'));
    }

    public function grant(Request $request, User $user, CreditService $creditService): RedirectResponse
    {
        $validated = $request->validate([
            'quota_key' => 'nullable|string|max:64',
            'credits' => 'required|integer|min:1',
            'validity_days' => 'nullable|integer|min:0',
        ]);

        $credits = (int) $validated['credits'];
        $validityDays = array_key_exists('validity_days', $validated) && $validated['validity_days'] !== null
            ? (int) $validated['validity_days']
            : null;

        $creditService->adminGrant(
            $user,
            $validated['quota_key'],
            $credits,
            $validityDays,
        );

        return redirect()->route('admin.users.show', $user)->with('success', "已为用户充值 {$credits} 次次卡");
    }
}
