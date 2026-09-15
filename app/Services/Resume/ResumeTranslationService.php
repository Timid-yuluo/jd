<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\AiCacheScenario;
use App\Infrastructure\AI\AiManager;
use App\Models\Resume;
use Illuminate\Support\Facades\Cache;

/**
 * 简历一键翻译服务
 */
final class ResumeTranslationService
{
    /**
     * 支持的翻译方向
     */
    private const DIRECTION_ZH_TO_EN = 'zh_to_en';
    private const DIRECTION_EN_TO_ZH = 'en_to_zh';

    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * 翻译简历内容
     *
     * @return array{translated_modules:array<int,array{type:string,data:array<string,mixed>}>}
     */
    public function translate(Resume $resume, string $direction): array
    {
        if (! in_array($direction, [self::DIRECTION_ZH_TO_EN, self::DIRECTION_EN_TO_ZH], true)) {
            return ['translated_modules' => []];
        }

        $resume->loadMissing('modules');
        $modulesData = $resume->modules->map(fn ($m) => [
            'type' => $m->type,
            'data' => $m->data,
        ])->values()->toJson(JSON_UNESCAPED_UNICODE);

        $cacheKey = 'ai:translate:'.hash('sha256', $direction.$modulesData);

        return $this->aiManager->withCacheFor($cacheKey, function ($provider) use ($direction, $modulesData) {
            $prompt = $this->buildPrompt($direction, $modulesData);
            $response = $provider->chat([
                ['role' => 'system', 'content' => '你是一位专业的简历翻译专家，擅长将简历在中英文之间互译，确保翻译后内容地道、专业、符合目标语言简历规范。请用JSON格式回复。'],
                ['role' => 'user', 'content' => $prompt],
            ], ['temperature' => 0.2]);

            return $this->parseResponse($response);
        }, AiCacheScenario::Medium);
    }

    /**
     * 构建翻译提示词
     */
    private function buildPrompt(string $direction, string $modulesData): string
    {
        $directionLabel = $direction === self::DIRECTION_ZH_TO_EN
            ? '中文翻译为英文'
            : '英文翻译为中文';

        return <<<PROMPT
请将以下简历模块数据从{$directionLabel}。

简历模块数据：
{$modulesData}

翻译要求：
1. 保持JSON结构不变，只翻译文本内容
2. 专业术语使用行业标准译法
3. 日期格式保持原样
4. 链接、数字、代码不翻译
5. 翻译后内容要自然、专业，符合目标语言简历习惯

请返回JSON格式：
{
  "translated_modules": [
    {"type": "模块type", "data": {翻译后的data}},
    ...
  ]
}
PROMPT;
    }

    /**
     * 解析AI响应
     */
    private function parseResponse(array $response): array
    {
        $content = $response['content'] ?? $response['choices'][0]['message']['content'] ?? '';

        if (preg_match('/\{[\s\S]*\}/', (string) $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded) && isset($decoded['translated_modules'])) {
                return $decoded;
            }
        }

        return ['translated_modules' => []];
    }
}
