<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CareerTrackController extends Controller
{
    public function index(): View
    {
        $tracks = CareerTrack::ordered()
            ->withCount('resumes')
            ->get();

        $categories = CareerTrack::categoryLabels();

        return view('admin.career-tracks.index', compact('tracks', 'categories'));
    }

    public function create(): View
    {
        $categories = CareerTrack::categoryLabels();
        $strategyKeys = array_keys(config('resume_prompt_strategies.templates', []));

        return view('admin.career-tracks.create', compact('categories', 'strategyKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:career_tracks',
            'category' => ['required', 'string', 'max:30', Rule::in(array_keys(CareerTrack::categoryLabels()))],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'optimization_focus' => 'nullable|array',
            'optimization_focus.*' => 'string|max:100',
            'recommended_templates' => 'nullable|array',
            'recommended_templates.*' => 'string|max:30',
            'prompt_strategy_key' => 'nullable|string|max:50',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
            'avoid_words' => 'nullable|array',
            'avoid_words.*' => 'string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        CareerTrack::create($validated);

        return redirect()->route('admin.career-tracks.index')->with('success', '赛道创建成功');
    }

    public function edit(CareerTrack $careerTrack): View
    {
        $categories = CareerTrack::categoryLabels();
        $strategyKeys = array_keys(config('resume_prompt_strategies.templates', []));

        return view('admin.career-tracks.edit', compact('careerTrack', 'categories', 'strategyKeys'));
    }

    public function update(Request $request, CareerTrack $careerTrack): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => ['required', 'string', 'max:50', Rule::unique('career_tracks')->ignore($careerTrack->id)],
            'category' => ['required', 'string', 'max:30', Rule::in(array_keys(CareerTrack::categoryLabels()))],
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'optimization_focus' => 'nullable|array',
            'optimization_focus.*' => 'string|max:100',
            'recommended_templates' => 'nullable|array',
            'recommended_templates.*' => 'string|max:30',
            'prompt_strategy_key' => 'nullable|string|max:50',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
            'avoid_words' => 'nullable|array',
            'avoid_words.*' => 'string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $careerTrack->update($validated);

        return redirect()->route('admin.career-tracks.index')->with('success', '赛道更新成功');
    }

    public function destroy(CareerTrack $careerTrack): RedirectResponse
    {
        if ($careerTrack->resumes()->exists()) {
            return back()->with('error', '该赛道下还有关联简历，无法删除。请先解除关联。');
        }

        $careerTrack->delete();

        return redirect()->route('admin.career-tracks.index')->with('success', '赛道删除成功');
    }

    /**
     * 切换启用/禁用状态
     */
    public function toggleActive(CareerTrack $careerTrack): RedirectResponse
    {
        $careerTrack->update(['is_active' => ! $careerTrack->is_active]);

        $status = $careerTrack->is_active ? '启用' : '禁用';

        return back()->with('success', "赛道已{$status}");
    }
}
