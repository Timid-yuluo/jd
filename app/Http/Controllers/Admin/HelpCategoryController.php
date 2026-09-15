<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class HelpCategoryController extends Controller
{
    public function index(): View
    {
        $categories = HelpCategory::query()
            ->withCount(['articles', 'articles as published_articles_count' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate((int) config('ui.pagination.admin_table', 20));

        return view('admin.help-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.help-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'slug' => 'nullable|string|max:60|unique:help_categories,slug',
            'description' => 'nullable|string|max:200',
            'icon' => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'boolean',
        ]);

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        HelpCategory::create($validated);

        return redirect()->route('admin.help-categories.index')
            ->with('success', '分类已创建');
    }

    public function edit(HelpCategory $helpCategory): View
    {
        return view('admin.help-categories.create', ['category' => $helpCategory]);
    }

    public function update(Request $request, HelpCategory $helpCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'slug' => ['nullable', 'string', 'max:60', Rule::unique('help_categories', 'slug')->ignore($helpCategory->id)],
            'description' => 'nullable|string|max:200',
            'icon' => 'nullable|string|max:40',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'boolean',
        ]);

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $helpCategory->update($validated);

        return redirect()->route('admin.help-categories.index')
            ->with('success', '分类已更新');
    }

    public function destroy(HelpCategory $helpCategory): RedirectResponse
    {
        if ($helpCategory->articles()->exists()) {
            return back()->withErrors(['delete' => '该分类下还有文章，无法删除。请先移动或删除相关文章。']);
        }

        $helpCategory->delete();

        return redirect()->route('admin.help-categories.index')
            ->with('success', '分类已删除');
    }
}
