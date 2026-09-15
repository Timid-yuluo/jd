<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use App\Infrastructure\AI\Contracts\AiProvider;
use Psr\Http\Message\StreamInterface;

abstract class OpenAiCompatibleProvider implements AiProvider
{
    private ?OpenAiClientManager $clientManager = null;

    private ?OpenAiCostEstimator $costEstimator = null;

    private ?OpenAiPromptBuilder $promptBuilder = null;

    private ?OpenAiPromptTemplates $promptTemplates = null;

    private ?InterviewPromptTemplates $interviewPromptTemplates = null;

    private string $streamBuffer = '';

    public function __construct(
        protected readonly string $baseUrl,
        protected readonly string $apiKey,
        protected readonly string $model
    ) {}

    private function clientManager(): OpenAiClientManager
    {
        return $this->clientManager ??= new OpenAiClientManager($this->baseUrl, $this->apiKey);
    }

    private function costEstimator(): OpenAiCostEstimator
    {
        return $this->costEstimator ??= new OpenAiCostEstimator;
    }

    private function promptBuilder(): OpenAiPromptBuilder
    {
        return $this->promptBuilder ??= new OpenAiPromptBuilder;
    }

    private function promptTemplates(): OpenAiPromptTemplates
    {
        return $this->promptTemplates ??= new OpenAiPromptTemplates;
    }

    private function interviewPromptTemplates(): InterviewPromptTemplates
    {
        return $this->interviewPromptTemplates ??= new InterviewPromptTemplates;
    }

    public function estimateCostMicros(int $promptTokens, int $completionTokens): int
    {
        return $this->costEstimator()->estimate($this->model, $promptTokens, $completionTokens);
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
        ], $options);

        $scene = ($options['_scene'] ?? '') === 'quick' ? 'quick' : 'heavy';
        unset($options['_scene']);

        $startMs = (int) (microtime(true) * 1000);
        $response = $this->clientManager()->client($scene)->post('/chat/completions', $payload);
        $latencyMs = (int) (microtime(true) * 1000) - $startMs;

        if ($response->failed()) {
            throw new \RuntimeException('AI request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new \RuntimeException('Invalid AI response format');
        }

        $content = $this->stripMarkdownFences($content);

        try {
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $parsed = $this->extractJsonFromContent($content);
        }

        $usage = $response->json('usage');
        if (is_array($usage)) {
            $parsed['__usage'] = [
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            ];
        }
        $parsed['__provider_model'] = (string) ($payload['model'] ?? $this->model);
        $parsed['__latency_ms'] = $latencyMs;

        return $parsed;
    }

    public function optimizeResume(string $content, string $targetJob, array $options = []): array
    {
        $goals = is_array($options['optimize_goals'] ?? null) ? $options['optimize_goals'] : [];
        $mode = is_string($options['optimize_mode'] ?? null) ? $options['optimize_mode'] : 'balanced';
        $templateKey = is_string($options['prompt_strategy_template'] ?? null) ? $options['prompt_strategy_template'] : '';
        $jobDescription = is_string($options['target_job_description'] ?? null) ? $options['target_job_description'] : '';

        $systemPrompt = $this->promptBuilder()->buildOptimizeSystemPrompt($goals, $mode, $templateKey, $content, $targetJob, $jobDescription);
        $userPrompt = $this->promptBuilder()->buildOptimizeUserPrompt($content, $targetJob, $options);

        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }

    public function scoreResume(string $content, string $targetJob = ''): array
    {
        $lang = OpenAiPromptTemplates::detectLanguage($content . ' ' . $targetJob);
        $systemPrompt = $this->promptTemplates()->scoreResumeSystemPrompt($lang);
        $userPrompt = $this->promptTemplates()->scoreResumeUserPrompt($content, $targetJob);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // P2-10: 评分 AI 调用自动重试（最多 1 次）
        try {
            return $this->chat($messages);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ATS scoreResume first attempt failed, retrying', [
                'error' => $e->getMessage(),
                'target_job' => $targetJob,
            ]);

            // 指数退避：等待 1 秒后重试
            sleep(1);

            try {
                return $this->chat($messages);
            } catch (\Throwable $retryError) {
                \Illuminate\Support\Facades\Log::error('ATS scoreResume retry also failed', [
                    'error' => $retryError->getMessage(),
                    'target_job' => $targetJob,
                ]);
                throw $retryError;
            }
        }
    }

    public function generateInterviewQuestion(string $position, int $round, array $context = []): array
    {
        $systemPrompt = $this->interviewPromptTemplates()->generateQuestionSystemPrompt();
        $userPrompt = $this->interviewPromptTemplates()->generateQuestionUserPrompt($position, $round, $context);

        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ], ['_scene' => 'quick']);
    }

    public function evaluateInterviewAnswer(string $question, string $answer): array
    {
        $systemPrompt = $this->interviewPromptTemplates()->evaluateAnswerSystemPrompt();
        $userPrompt = $this->interviewPromptTemplates()->evaluateAnswerUserPrompt($question, $answer);

        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ], ['_scene' => 'quick']);
    }

    public function optimizeResumeStream(string $content, string $targetJob, array $options = []): \Generator
    {
        $goals = is_array($options['optimize_goals'] ?? null) ? $options['optimize_goals'] : [];
        $mode = is_string($options['optimize_mode'] ?? null) ? $options['optimize_mode'] : 'balanced';
        $templateKey = is_string($options['prompt_strategy_template'] ?? null) ? $options['prompt_strategy_template'] : '';
        $jobDescription = is_string($options['target_job_description'] ?? null) ? $options['target_job_description'] : '';

        $systemPrompt = $this->promptBuilder()->buildOptimizeSystemPrompt($goals, $mode, $templateKey, $content, $targetJob, $jobDescription);
        $userPrompt = $this->promptBuilder()->buildOptimizeUserPrompt($content, $targetJob, $options);

        yield from $this->streamChat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }

    protected function streamChat(array $messages, array $options = []): \Generator
    {
        $this->streamBuffer = '';
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
        ], $options);

        $response = $this->clientManager()->client('stream')
            ->withBody(json_encode($payload), 'application/json')
            ->send('POST', '/chat/completions');

        if ($response->failed()) {
            throw new \RuntimeException('AI stream request failed: '.$response->body());
        }

        $body = $response->toPsrResponse()->getBody();

        while (! $body->eof()) {
            $line = $this->readStreamLine($body);
            if ($line === '' || ! str_starts_with($line, 'data: ')) {
                continue;
            }

            $data = trim(substr($line, 6));
            if ($data === '[DONE]') {
                break;
            }

            $json = json_decode($data, true);
            if (! is_array($json)) {
                continue;
            }

            $content = $json['choices'][0]['delta']['content'] ?? null;
            if ($content !== null && $content !== '') {
                yield $content;
            }
        }
    }

    /**
     * @return array{optimized_text:string,suggestions:array<int,string>,score_before:int,score_after:int}
     */
    public function optimizeSection(string $sectionType, string $content, string $targetJob = ''): array
    {
        $systemPrompt = $this->promptTemplates()->optimizeSectionSystemPrompt($sectionType);
        $userPrompt = $this->promptTemplates()->optimizeSectionUserPrompt($sectionType, $content, $targetJob);

        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }

    /**
     * @return array{generated_text:string,suggestions:array<int,string>}
     */
    public function generateSection(string $sectionType, string $brief, string $targetJob = ''): array
    {
        $systemPrompt = $this->promptTemplates()->generateSectionSystemPrompt($sectionType);
        $userPrompt = $this->promptTemplates()->generateSectionUserPrompt($sectionType, $brief, $targetJob);

        return $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
    }

    /**
     * @return array{
     *   modules:array<int,array{type:string,data:array<string,mixed>,sort_order?:int}>,
     *   target_job?:string,
     *   confidence?:float
     * }
     */
    public function extractResumeStructured(string $content): array
    {
        $systemPrompt = $this->promptTemplates()->extractResumeStructuredSystemPrompt();
        $userPrompt = $this->promptTemplates()->extractResumeStructuredUserPrompt($content);

        $response = $this->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        unset($response['__usage'], $response['__latency_ms']);

        $modules = [];
        $rawModules = is_array($response['modules'] ?? null) ? $response['modules'] : [];
        foreach ($rawModules as $idx => $module) {
            if (! is_array($module)) {
                continue;
            }
            $type = is_string($module['type'] ?? null) ? trim((string) $module['type']) : '';
            $data = is_array($module['data'] ?? null) ? $module['data'] : [];
            if ($type === '' || $data === []) {
                continue;
            }
            $modules[] = [
                'type' => $type,
                'data' => $data,
                'sort_order' => is_int($module['sort_order'] ?? null) ? (int) $module['sort_order'] : $idx,
            ];
        }

        $targetJob = is_string($response['target_job'] ?? null) ? trim((string) $response['target_job']) : '';
        $confidenceRaw = $response['confidence'] ?? null;
        $confidence = is_numeric($confidenceRaw) ? max(0.0, min(1.0, (float) $confidenceRaw)) : 0.0;

        return [
            'modules' => $modules,
            'target_job' => $targetJob,
            'confidence' => $confidence,
        ];
    }

    private function stripMarkdownFences(string $content): string
    {
        $content = trim($content);
        if (str_starts_with($content, '```json')) {
            $content = substr($content, 7);
        } elseif (str_starts_with($content, '```')) {
            $content = substr($content, 3);
        }

        if (str_ends_with($content, '```')) {
            $content = substr($content, 0, -3);
        }

        return trim($content);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonFromContent(string $content): array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $extracted = substr($content, $start, $end - $start + 1);

            return json_decode($extracted, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException('AI response does not contain valid JSON: '.mb_substr($content, 0, 200));
    }

    private function readStreamLine(StreamInterface $stream): string
    {
        while (! $stream->eof()) {
            $newlinePos = strpos($this->streamBuffer, "\n");
            if ($newlinePos !== false) {
                $line = substr($this->streamBuffer, 0, $newlinePos);
                $this->streamBuffer = substr($this->streamBuffer, $newlinePos + 1);

                return rtrim($line, "\r");
            }

            $chunk = $stream->read(1024);
            $this->streamBuffer .= $chunk;
        }

        $line = $this->streamBuffer;
        $this->streamBuffer = '';

        return rtrim($line, "\r");
    }
}
