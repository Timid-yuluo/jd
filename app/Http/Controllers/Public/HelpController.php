<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(): View
    {
        $categories = HelpCategory::query()
            ->where('is_visible', true)
            ->withCount(['articles as articles_count' => fn ($q) => $q->where('is_published', true)])
            ->with(['articles' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')->orderByDesc('published_at')->limit(5)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $recentArticles = HelpArticle::query()
            ->where('is_published', true)
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return view('public.help.index', compact('categories', 'recentArticles'));
    }

    public function category(string $categorySlug): View
    {
        $category = HelpCategory::query()
            ->where('slug', $categorySlug)
            ->where('is_visible', true)
            ->firstOrFail();

        $articles = $category->articles()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate(20);

        $categories = HelpCategory::cachedVisible();

        return view('public.help.category', compact('category', 'articles', 'categories'));
    }

    public function show(string $categorySlug, string $articleSlug): View
    {
        $category = HelpCategory::query()
            ->where('slug', $categorySlug)
            ->where('is_visible', true)
            ->firstOrFail();

        $article = HelpArticle::query()
            ->where('slug', $articleSlug)
            ->where('category_id', $category->id)
            ->where('is_published', true)
            ->firstOrFail();

        $cacheKey = "help_views:{$article->id}";
        $views = (int) \Illuminate\Support\Facades\Cache::increment($cacheKey);
        if ($views >= 10) {
            $article->increment('view_count', $views);
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        } else {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $views, now()->addMinutes(30));
        }

        $relatedArticles = $category->articles()
            ->where('is_published', true)
            ->where('id', '!=', $article->id)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        $categories = HelpCategory::cachedVisible();

        return view('public.help.show', compact('category', 'article', 'relatedArticles', 'categories'));
    }

    public function search(Request $request): View
    {
        $keyword = trim((string) $request->input('q', ''));

        $articles = HelpArticle::query()
            ->where('is_published', true)
            ->with('category')
            ->when($keyword !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.escapeLike($keyword).'%')->orWhere('excerpt', 'like', '%'.escapeLike($keyword).'%')))
            ->orderByDesc('published_at')
            ->paginate(20)
            ->appends($request->query());

        $categories = HelpCategory::cachedVisible();

        return view('public.help.search', compact('articles', 'keyword', 'categories'));
    }
}
