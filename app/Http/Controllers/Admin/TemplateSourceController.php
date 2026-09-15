<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateSource;
use App\Services\Resume\Template\Source\TemplateSourceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TemplateSourceController extends Controller
{
    public function __construct(
        private readonly TemplateSourceSyncService $syncService,
    ) {}

    public function index(): View
    {
        $sources = TemplateSource::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.template-sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('admin.template-sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:80|unique:template_sources',
            'driver' => 'required|string|max:30|in:api',
            'base_url' => 'nullable|string|max:255|url',
            'credentials' => 'nullable|array',
            'credentials.api_key' => 'nullable|string|max:255',
            'sync_config' => 'nullable|array',
            'sync_config.templates_path' => 'nullable|string|max:255',
            'sync_config.detail_path' => 'nullable|string|max:255',
            'sync_config.category_filter' => 'nullable|string|max:60',
            'sync_interval_minutes' => 'integer|min:10|max:10080',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $credentials = array_filter($validated['credentials'] ?? [], fn ($v) => $v !== null && $v !== '');
        $syncConfig = array_filter($validated['sync_config'] ?? [], fn ($v) => $v !== null && $v !== '');

        TemplateSource::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'driver' => $validated['driver'],
            'base_url' => $validated['base_url'] ?? null,
            'credentials' => ! empty($credentials) ? $credentials : null,
            'sync_config' => ! empty($syncConfig) ? $syncConfig : null,
            'sync_interval_minutes' => $validated['sync_interval_minutes'] ?? 360,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 100,
        ]);

        return redirect()->route('admin.template-sources.index')->with('success', '模板源创建成功');
    }

    public function edit(TemplateSource $templateSource): View
    {
        return view('admin.template-sources.edit', compact('templateSource'));
    }

    public function update(Request $request, TemplateSource $templateSource): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => ['required', 'string', 'max:80', Rule::unique('template_sources')->ignore($templateSource->id)],
            'driver' => 'required|string|max:30|in:api',
            'base_url' => 'nullable|string|max:255|url',
            'credentials' => 'nullable|array',
            'credentials.api_key' => 'nullable|string|max:255',
            'sync_config' => 'nullable|array',
            'sync_config.templates_path' => 'nullable|string|max:255',
            'sync_config.detail_path' => 'nullable|string|max:255',
            'sync_config.category_filter' => 'nullable|string|max:60',
            'sync_interval_minutes' => 'integer|min:10|max:10080',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $credentials = array_filter($validated['credentials'] ?? [], fn ($v) => $v !== null && $v !== '');
        $syncConfig = array_filter($validated['sync_config'] ?? [], fn ($v) => $v !== null && $v !== '');

        $templateSource->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'driver' => $validated['driver'],
            'base_url' => $validated['base_url'] ?? null,
            'credentials' => ! empty($credentials) ? $credentials : null,
            'sync_config' => ! empty($syncConfig) ? $syncConfig : null,
            'sync_interval_minutes' => $validated['sync_interval_minutes'] ?? 360,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 100,
        ]);

        return redirect()->route('admin.template-sources.index')->with('success', '模板源更新成功');
    }

    public function sync(TemplateSource $templateSource): RedirectResponse
    {
        $result = $this->syncService->syncSource($templateSource);

        if ($result['error'] !== null) {
            return redirect()->route('admin.template-sources.index')
                ->with('error', "同步失败: {$result['error']}");
        }

        return redirect()->route('admin.template-sources.index')
            ->with('success', "同步完成：新增 {$result['created']} 个，更新 {$result['updated']} 个，跳过 {$result['skipped']} 个");
    }

    public function syncAll(): RedirectResponse
    {
        $results = $this->syncService->syncAllActiveSources();
        $totalSynced = array_sum(array_column($results, 'synced'));
        $errors = array_filter(array_column($results, 'error'));

        if (! empty($errors)) {
            return redirect()->route('admin.template-sources.index')
                ->with('warning', "同步完成，共同步 {$totalSynced} 个模板，" . count($errors) . ' 个源出现错误');
        }

        return redirect()->route('admin.template-sources.index')
            ->with('success', "全部同步完成，共同步 {$totalSynced} 个模板");
    }

    public function destroy(TemplateSource $templateSource): RedirectResponse
    {
        $templateSource->templates()->update([
            'source_id' => null,
            'source_driver' => null,
        ]);

        $templateSource->delete();

        return redirect()->route('admin.template-sources.index')->with('success', '模板源已删除，关联模板已保留');
    }
}
