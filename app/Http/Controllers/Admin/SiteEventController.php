<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SiteEventController extends Controller
{
    public function index(Request $request): View
    {
        $query = SiteEvent::query()->with('admin');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('keyword')) {
            $keyword = escapeLike($request->input('keyword'));
            $query->where(fn ($q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('summary', 'like', "%{$keyword}%"));
        }

        $stats = [
            'total' => SiteEvent::count(),
            'published' => SiteEvent::where('is_published', true)->count(),
            'pinned' => SiteEvent::where('is_pinned', true)->count(),
            'total_views' => SiteEvent::sum('view_count'),
        ];

        $events = $query->orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->paginate((int) config('ui.pagination.admin_table', 20))
            ->appends($request->only(['category', 'keyword']));

        return view('admin.events.index', compact('events', 'stats'));
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:site_events,slug',
            'category' => 'required|string|in:公告,活动,更新,洞察',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $validated['admin_id'] = $request->user()->id;
        $validated['is_pinned'] = $request->boolean('is_pinned');
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }

        SiteEvent::create($validated);

        return redirect()->route('admin.events.index')->with('success', '事件已创建。');
    }

    public function edit(SiteEvent $event): View
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, SiteEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|unique:site_events,slug,'.$event->id,
            'category' => 'required|string|in:公告,活动,更新,洞察',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $validated['is_pinned'] = $request->boolean('is_pinned');
        $wasPublished = $event->is_published;
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published'] && ! $wasPublished) {
            $validated['published_at'] = now();
        }

        if (($validated['slug'] ?? '') === '') {
            unset($validated['slug']);
        }

        $event->update($validated);

        return redirect()->route('admin.events.index')->with('success', '事件已更新。');
    }

    public function destroy(SiteEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', '事件已删除。');
    }

    public function togglePublish(SiteEvent $event): RedirectResponse
    {
        $event->update([
            'is_published' => ! $event->is_published,
            'published_at' => $event->is_published ? null : now(),
        ]);

        $status = $event->is_published ? '已发布' : '已下架';

        return redirect()->route('admin.events.index')->with('success', "事件{$status}。");
    }
}
