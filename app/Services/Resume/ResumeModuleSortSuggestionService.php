<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\AiCacheScenario;
use App\Infrastructure\AI\AiManager;
use App\Models\Resume;
use Illuminate\Support\Facades\Cache;

/**
 * 简历模块排序建议服务
 */
final class ResumeModuleSortSuggestionService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 根据求职目标生成模块排序建议
     *
     * @return array{suggested_order:array<int,array{type:string,reason:string}>,summary:string}
     */
    public function suggestOrder(Resume $resume): array
    {
        $resume->loadMissing('modules');
        $targetJob = (string) ($resume->target_job ?? '');
        $currentModules = $resume->modules->map(fn ($m) => [
            'type' => $m->type,
            'sort_order' => $m->sort_order,
        ])->values()->toArray();

        $cacheKey = 'ai:module-sort:'.hash('sha256', json_encode([$targetJob, $currentModules]));

        return $this->aiManager->withCacheFor($cacheKey, function ($provider) use ($targetJob, $currentModules) {
            $prompt = $this->buildPrompt($targetJob, $currentModules);
            $response = $provider->chat([
                ['role' => 'system', 'content' => '你是一位资深简历顾问，擅长根据岗位特点优化简历模块排列顺序。请用JSON格式回复。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.3]);

            return $this->parseResponse($response);
        }, AiCacheScenario::Medium);
    }

    /**
     * 构建提示词
     */
    private function buildPrompt(string $targetJob, array $currentModules): string
    {
        $moduleLabels = [
            'personal_info' => '个人信息',
            'objective' => '求职意向',
            'education' => '教育经历',
            'experience' => '工作/实习经历',
            'project' => '项目经验',
            'skill' => '技能',
            'certificate' => '证书/获奖',
            'summary' => '自我评价',
            'custom' => '自定义模块',
        ];

        $moduleList = collect($currentModules)->map(function ($m) use ($moduleLabels) {
            $label = $moduleLabels[$m['type']] ?? $m['type'];

            return "- {$label}（type: {$m['type']}，当前排序: {$m['sort_order']}）";
        })->implode("\n");

        $jobContext = $targetJob !== '' ? "目标岗位：{$targetJob}" : '通用求职场景';

        return <<<PROMPT
请根据以下简历模块和目标岗位，给出最优的模块排列顺序建议。

{$jobContext}

当前模块列表：
{$moduleList}

请返回JSON格式：
{
  "suggested_order": [
    {"type": "模块type", "reason": "排在第N位的原因"},
    ...
  ],
  "summary": "整体排序策略说明（一句话）"
}

排序原则：
1. 个人信息始终排第一
2. 应届生/实习生：教育经历优先，项目经验次之
3. 有经验者：工作经历优先，教育经历后移
4. 技能和证书根据岗位相关性决定位置
5. 自我评价通常放最后
PROMPT;
    }

    /**
     * 解析AI响应
     */
    private function parseResponse(array $response): array
    {
        $content = $response['content'] ?? $response['choices'][0]['message']['content'] ?? '';

        // 提取JSON
        if (preg_match('/\{[\s\S]*\}/', (string) $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded) && isset($decoded['suggested_order'])) {
                return $decoded;
            }
        }

        return [
            'suggested_order' => [],
            'summary' => '无法生成排序建议，请稍后重试。',
        ];
    }
}
