<?php

declare(strict_types=1);

namespace App\Services\Resume\Template\Source;

use App\Models\TemplateSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TemplateSourceSyncService
{
    public function __construct(
        private readonly TemplateSourceApiClient $apiClient,
        private readonly TemplateSourceNormalizer $normalizer,
    ) {}

    /**
     * @return array{synced: int, created: int, updated: int, skipped: int, error: string|null}
     */
    public function syncSource(TemplateSource $source): array
    {
        if (! $source->is_active) {
            return ['synced' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => '模板源未启用'];
        }

        $source->update([
            'last_sync_status' => 'running',
            'last_sync_error' => null,
        ]);

        try {
            $page = 1;
            $perPage = 50;
            $totalCreated = 0;
            $totalUpdated = 0;
            $totalSkipped = 0;
            $totalSynced = 0;
            $hasMore = true;

            while ($hasMore) {
                $result = $this->apiClient->fetchTemplates($source, $page, $perPage);

                if (! $result['success']) {
                    $source->update([
                        'last_sync_status' => 'failed',
                        'last_sync_error' => $result['error'],
                    ]);

                    return [
                        'synced' => $totalSynced,
                        'created' => $totalCreated,
                        'updated' => $totalUpdated,
                        'skipped' => $totalSkipped,
                        'error' => $result['error'],
                    ];
                }

                $items = $result['data'];
                $totalFromApi = $result['total'];

                if (empty($items)) {
                    break;
                }

                foreach ($items as $externalData) {
                    if (! is_array($externalData)) {
                        $totalSkipped++;
                        continue;
                    }

                    $normalized = $this->normalizer->normalize($source, $externalData);
                    $externalId = $normalized['external_id'];

                    if ($externalId === '') {
                        $totalSkipped++;
                        continue;
                    }

                    $existing = $source->templates()->where('external_id', $externalId)->first();

                    if ($existing) {
                        $existing->update($normalized);
                        $totalUpdated++;
                    } else {
                        $source->templates()->create($normalized);
                        $totalCreated++;
                    }

                    $totalSynced++;
                }

                $totalFetched = $page * $perPage;
                $hasMore = $totalFetched < $totalFromApi;
                $page++;
            }

            $source->update([
                'last_synced_at' => now(),
                'last_sync_status' => 'success',
                'last_sync_count' => $totalSynced,
                'last_sync_error' => null,
            ]);

            Log::info('template_source.sync_completed', [
                'source_id' => $source->id,
                'source_slug' => $source->slug,
                'synced' => $totalSynced,
                'created' => $totalCreated,
                'updated' => $totalUpdated,
                'skipped' => $totalSkipped,
            ]);

            return [
                'synced' => $totalSynced,
                'created' => $totalCreated,
                'updated' => $totalUpdated,
                'skipped' => $totalSkipped,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $source->update([
                'last_sync_status' => 'failed',
                'last_sync_error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::error('template_source.sync_error', [
                'source_id' => $source->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'synced' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<int, array{source_id: int, source_slug: string, synced: int, error: string|null}>
     */
    public function syncAllActiveSources(): array
    {
        $sources = TemplateSource::query()->active()->get();
        $results = [];

        foreach ($sources as $source) {
            if (! $source->needsSync()) {
                continue;
            }

            $syncResult = $this->syncSource($source);
            $results[] = [
                'source_id' => $source->id,
                'source_slug' => $source->slug,
                'synced' => $syncResult['synced'],
                'error' => $syncResult['error'],
            ];
        }

        return $results;
    }
}
