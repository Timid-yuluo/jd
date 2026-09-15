<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

class VolcanoAiProvider extends OpenAiCompatibleProvider
{
    protected function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
        ], $options);

        $startMs = (int) (microtime(true) * 1000);
        $response = $this->client()->post('/chat/completions', $payload);
        $latencyMs = (int) (microtime(true) * 1000) - $startMs;

        if ($response->failed()) {
            throw new \RuntimeException('Volcano Engine AI request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new \RuntimeException('Invalid AI response format');
        }

        $content = trim($content);
        if (str_starts_with($content, '```json')) {
            $content = substr($content, 7);
        } elseif (str_starts_with($content, '```')) {
            $content = substr($content, 3);
        }

        if (str_ends_with($content, '```')) {
            $content = substr($content, 0, -3);
        }
        $content = trim($content);

        $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $parsed['__provider_model'] = (string) ($payload['model'] ?? $this->model);
        $parsed['__latency_ms'] = $latencyMs;

        return $parsed;
    }
}
