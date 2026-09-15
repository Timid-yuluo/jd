<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteEvent;
use App\Support\HtmlPurifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SiteEventController extends Controller
{
    public function index(Request $request): View
    {
        $query = SiteEvent::query()->published();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('keyword')) {
            $keyword = escapeLike($request->input('keyword'));
            $query->where(fn ($q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('summary', 'like', "%{$keyword}%"));
        }

        $events = $query->pinnedFirst()
            ->select(['id', 'title', 'slug', 'summary', 'cover_image', 'category', 'is_pinned', 'published_at', 'view_count'])
            ->paginate(12)
            ->appends($request->only(['category', 'keyword']));

        $categories = SiteEvent::categories();

        return view('public.events.index', compact('events', 'categories'));
    }

    public function show(SiteEvent $event): View
    {
        if (! $event->is_published) {
            abort(404);
        }

        $event->incrementViewCountAtomic();

        $latestEvents = SiteEvent::query()->published()
            ->where('id', '!=', $event->id)
            ->orderByDesc('published_at')
            ->limit(5)
            ->select(['id', 'title', 'slug', 'published_at'])
            ->get();

        return view('public.events.show', compact('event', 'latestEvents'));
    }

    public function content(SiteEvent $event): JsonResponse
    {
        if (! $event->is_published) {
            abort(404);
        }

        $event->incrementViewCountAtomic();

        return response()->json([
            'title' => $event->title,
            'category' => $event->category,
            'published_at' => $event->published_at?->format('Y-m-d H:i'),
            'view_count' => $event->view_count,
            'summary' => $event->summary,
            'content' => HtmlPurifier::clean($event->content),
            'cover_image' => $event->cover_image,
        ]);
    }
}
