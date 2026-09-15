<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('sort_order')->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:32|unique:plans',
            'name' => 'required|string|max:64',
            'price_monthly' => 'required|integer|min:0',
            'price_yearly' => 'required|integer|min:0',
            'quotas' => 'required|array',
            'features' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        Plan::create($validated);
        Plan::clearCache();

        return redirect()->route('admin.plans.index')->with('success', '套餐创建成功');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:32', Rule::unique('plans')->ignore($plan->id)],
            'name' => 'required|string|max:64',
            'price_monthly' => 'required|integer|min:0',
            'price_yearly' => 'required|integer|min:0',
            'quotas' => 'required|array',
            'features' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);
        Plan::clearCache();

        return redirect()->route('admin.plans.index')->with('success', '套餐更新成功');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->slug === 'free') {
            return back()->with('error', '免费版套餐不可删除');
        }

        $plan->delete();
        Plan::clearCache();

        return redirect()->route('admin.plans.index')->with('success', '套餐删除成功');
    }
}
