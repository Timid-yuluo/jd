<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class HelpArticleController extends Controller
{
    public function index(Request $request): View
    {
        $query = HelpArticle::query()->with(['category', 'author']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('is_published', $request->input('status') === 'published');
        }
        if ($request->filled('keyword')) {
            $keyword = escapeLike($request->input('keyword'));
            $query->where(fn ($q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('excerpt', 'like', "%{$keyword}%"));
        }

        $articles = $query->orderBy('sort_order')->orderByDesc('id')
            ->paginate((int) config('ui.pagination.admin_table', 20))
            ->appends($request->query());

        $categories = HelpCategory::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.help-articles.index', compact('articles', 'categories'));
    }

    public function create(): View
    {
        $categories = HelpCategory::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.help-articles.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:help_categories,id',
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:help_articles,slug',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);

        $validated['author_id'] = $request->user()->id;
        $validated['is_published'] = $request->boolean('is_published', false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }

        HelpArticle::create($validated);

        return redirect()->route('admin.help-articles.index')
            ->with('success', '文章已创建');
    }

    public function edit(HelpArticle $helpArticle): View
    {
        $categories = HelpCategory::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.help-articles.create', ['article' => $helpArticle, 'categories' => $categories]);
    }

    public function update(Request $request, HelpArticle $helpArticle): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:help_categories,id',
            'title' => 'required|string|max:200',
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('help_articles', 'slug')->ignore($helpArticle->id)],
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);

        $validated['is_published'] = $request->boolean('is_published', false);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }

        $helpArticle->update($validated);

        return redirect()->route('admin.help-articles.index')
            ->with('success', '文章已更新');
    }

    public function destroy(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->delete();

        return redirect()->route('admin.help-articles.index')
            ->with('success', '文章已删除');
    }

    public function togglePublish(HelpArticle $helpArticle): JsonResponse|RedirectResponse
    {
        $helpArticle->update([
            'is_published' => ! $helpArticle->is_published,
        ]);

        $status = $helpArticle->is_published ? '已发布' : '已转为草稿';

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "文章{$status}",
                'is_published' => $helpArticle->is_published,
            ]);
        }

        return back()->with('success', "文章{$status}");
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120',
        ]);

        $path = $request->file('file')->store('help-images', 'public');

        return response()->json([
            'location' => asset('storage/'.$path),
        ]);
    }
}
