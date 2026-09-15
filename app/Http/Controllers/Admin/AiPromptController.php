<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\AI\AiManager;
use App\Models\AiPrompt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AiPromptController extends Controller
{
    public function index(): View
    {
        $prompts = AiPrompt::query()
            ->with('updater')
            ->orderBy('key')
            ->paginate((int) config('ui.pagination.admin_table', 20));

        return view('admin.ai-prompts.index', compact('prompts'));
    }

    public function create(): View
    {
        return view('admin.ai-prompts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:ai_prompts,key',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'system_prompt' => 'required|string',
            'variables' => 'nullable|string',
            'model' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['variables'] = $this->parseVariables($validated['variables'] ?? '');
        $validated['updated_by'] = auth()->id();
        $validated['version'] = 1;
        $validated['is_active'] = $request->boolean('is_active', true);

        AiPrompt::create($validated);

        return redirect()->route('admin.ai-prompts.index')
            ->with('success', 'Prompt 模板已创建');
    }

    public function show(AiPrompt $aiPrompt): View
    {
        return view('admin.ai-prompts.show', ['prompt' => $aiPrompt]);
    }

    public function edit(AiPrompt $aiPrompt): View
    {
        return view('admin.ai-prompts.edit', ['prompt' => $aiPrompt]);
    }

    public function update(Request $request, AiPrompt $aiPrompt): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'system_prompt' => 'required|string',
            'variables' => 'nullable|string',
            'model' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['variables'] = $this->parseVariables($validated['variables'] ?? '');
        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);

        // 如果 system_prompt 发生变化，增加版本号
        if ($validated['system_prompt'] !== $aiPrompt->system_prompt) {
            $validated['version'] = $aiPrompt->version + 1;
        }

        $aiPrompt->update($validated);

        return redirect()->route('admin.ai-prompts.index')
            ->with('success', 'Prompt 模板已更新（版本: '.$aiPrompt->fresh()->version.'）');
    }

    public function destroy(AiPrompt $aiPrompt): RedirectResponse
    {
        // 检查是否有关联的使用记录
        if ($aiPrompt->version > 1) {
            // 软删除：禁用而不是删除
            $aiPrompt->update(['is_active' => false]);

            return redirect()->route('admin.ai-prompts.index')
                ->with('success', 'Prompt 模板已禁用');
        }

        $aiPrompt->delete();

        return redirect()->route('admin.ai-prompts.index')
            ->with('success', 'Prompt 模板已删除');
    }

    /**
     * 测试 Prompt
     */
    public function test(Request $request, AiPrompt $aiPrompt): JsonResponse
    {
        $request->validate([
            'test_input' => 'required|string',
        ]);

        try {
            $aiManager = app(AiManager::class);
            $provider = $aiManager->provider();

            // 构建测试消息
            $systemPrompt = $aiPrompt->system_prompt;
            $userMessage = $request->input('test_input');

            $startTime = microtime(true);

            // 根据 Prompt 类型调用相应方法
            $result = match ($aiPrompt->key) {
                'resume_optimize' => $provider->optimizeResume($userMessage, '测试职位'),
                'resume_score' => $provider->scoreResume($userMessage, '测试职位'),
                'interview_question' => $provider->generateInterviewQuestion('测试职位', 1),
                'interview_evaluate' => $provider->evaluateInterviewAnswer('测试问题', $userMessage),
                default => ['message' => '该 Prompt 类型暂不支持在线测试'],
            };

            $latency = round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success' => true,
                'latency_ms' => $latency,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * 复制 Prompt
     */
    public function duplicate(AiPrompt $aiPrompt): RedirectResponse
    {
        $newPrompt = $aiPrompt->replicate();
        $newPrompt->key = $aiPrompt->key.'_copy';
        $newPrompt->title = $aiPrompt->title.' (副本)';
        $newPrompt->version = 1;
        $newPrompt->updated_by = auth()->id();

        // 确保 key 唯一
        $count = 1;
        while (AiPrompt::where('key', $newPrompt->key)->exists()) {
            $newPrompt->key = $aiPrompt->key.'_copy'.$count;
            $count++;
        }

        $newPrompt->save();

        return redirect()->route('admin.ai-prompts.edit', $newPrompt)
            ->with('success', 'Prompt 模板已复制');
    }

    /**
     * 解析变量
     */
    private function parseVariables(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        // 支持逗号分隔或换行分隔
        $variables = preg_split('/[,\n]+/', $input);

        return array_map('trim', array_filter($variables));
    }
}
