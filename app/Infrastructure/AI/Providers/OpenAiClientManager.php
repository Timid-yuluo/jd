<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class OpenAiClientManager
{
    private ?PendingRequest $cachedClient = null;

    private ?PendingRequest $cachedStreamClient = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    public function client(string $scene = 'heavy'): PendingRequest
    {
        $useStreamClient = ($scene === 'stream');

        if ($useStreamClient && $this->cachedStreamClient !== null) {
            return $this->cachedStreamClient;
        }

        if (! $useStreamClient && $this->cachedClient !== null) {
            return $this->cachedClient;
        }

        $connectTimeout = max(1, (int) config('ai.http.connect_timeout', 3));
        $requestTimeout = $this->resolveTimeout($scene);

        $baseClient = Http::withToken($this->apiKey)
            ->baseUrl($this->baseUrl)
            ->connectTimeout($connectTimeout)
            ->timeout($requestTimeout)
            ->withOptions([
                'headers' => [
                    'Connection' => 'keep-alive',
                ],
            ])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        if (! $useStreamClient) {
            $baseClient = $baseClient->retry(
                [200, 500, 1500],
                fn ($exception) => $this->isRetriable($exception)
            );
        }

        if ($useStreamClient) {
            $this->cachedStreamClient = $baseClient;
        } else {
            $this->cachedClient = $baseClient;
        }

        return $baseClient;
    }

    public function resolveTimeout(string $scene): int
    {
        $timeouts = config('ai.http.timeouts', []);
        if (is_array($timeouts) && isset($timeouts[$scene])) {
            return max(5, (int) $timeouts[$scene]);
        }

        return match ($scene) {
            'quick' => 30,
            'heavy' => 90,
            'stream' => 120,
            default => 60,
        };
    }

    public function isRetriable(\Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response?->status();

            return in_array($status, [429, 500, 502, 503, 504], true);
        }

        return false;
    }
}
