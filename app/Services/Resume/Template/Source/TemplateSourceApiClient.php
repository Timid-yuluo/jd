<?php

declare(strict_types=1);

namespace App\Services\Resume\Template\Source;

use App\Models\TemplateSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TemplateSourceApiClient
{
    private int $timeout = 30;

    /**
     * @return array{success: bool, data: array<int, array<string, mixed>>, total: int, error: string|null}
     */
    public function fetchTemplates(TemplateSource $source, int $page = 1, int $perPage = 50): array
    {
        $baseUrl = trim((string) $source->base_url, '/');
        if ($baseUrl === '') {
            return ['success' => false, 'data' => [], 'total' => 0, 'error' => '未配置 API 地址'];
        }

        $credentials = is_array($source->credentials) ? $source->credentials : [];
        $syncConfig = is_array($source->sync_config) ? $source->sync_config : [];
        $apiPath = (string) ($syncConfig['templates_path'] ?? '/api/v1/templates');
        $apiKey = (string) ($credentials['api_key'] ?? '');

        try {
            $request = Http::timeout($this->timeout)
                ->withHeaders(array_filter([
                    'Authorization' => $apiKey !== '' ? "Bearer {$apiKey}" : '',
                    'Accept' => 'application/json',
                    'X-Template-Source' => $source->slug,
                ]));

            $response = $request->get("{$baseUrl}{$apiPath}", [
                'page' => $page,
                'per_page' => $perPage,
                'category' => (string) ($syncConfig['category_filter'] ?? ''),
            ]);

            if (! $response->successful()) {
                $errorMsg = "API 返回错误: HTTP {$response->status()}";
                Log::warning('template_source.api_error', [
                    'source_id' => $source->id,
                    'source_slug' => $source->slug,
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return ['success' => false, 'data' => [], 'total' => 0, 'error' => $errorMsg];
            }

            $json = $response->json();
            $items = is_array($json['data'] ?? null) ? $json['data'] : [];
            $total = (int) ($json['total'] ?? count($items));

            return ['success' => true, 'data' => $items, 'total' => $total, 'error' => null];
        } catch (ConnectionException $e) {
            Log::error('template_source.connection_error', [
                'source_id' => $source->id,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'data' => [], 'total' => 0, 'error' => '连接超时: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('template_source.fetch_error', [
                'source_id' => $source->id,
                'message' => $e->getMessage(),
            ]);

            return ['success' => false, 'data' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, data: array<string, mixed>|null, error: string|null}
     */
    public function fetchTemplateDetail(TemplateSource $source, string $externalId): array
    {
        $baseUrl = trim((string) $source->base_url, '/');
        if ($baseUrl === '') {
            return ['success' => false, 'data' => null, 'error' => '未配置 API 地址'];
        }

        $credentials = is_array($source->credentials) ? $source->credentials : [];
        $syncConfig = is_array($source->sync_config) ? $source->sync_config : [];
        $detailPath = (string) ($syncConfig['detail_path'] ?? '/api/v1/templates/{id}');
        $apiKey = (string) ($credentials['api_key'] ?? '');
        $url = "{$baseUrl}" . str_replace('{id}', $externalId, $detailPath);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(array_filter([
                    'Authorization' => $apiKey !== '' ? "Bearer {$apiKey}" : '',
                    'Accept' => 'application/json',
                ]))
                ->get($url);

            if (! $response->successful()) {
                return ['success' => false, 'data' => null, 'error' => "HTTP {$response->status()}"];
            }

            return ['success' => true, 'data' => $response->json('data'), 'error' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
