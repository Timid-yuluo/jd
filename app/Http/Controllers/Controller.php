<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    protected function markQuotaConsumptionSuccess(Request $request): void
    {
        $requests = [$request];

        if (app()->bound('request')) {
            $containerRequest = app('request');
            if ($containerRequest instanceof Request && $containerRequest !== $request) {
                $requests[] = $containerRequest;
            }
        }

        $hasQuotaContext = false;

        foreach ($requests as $candidate) {
            if (! $candidate->attributes->has('quota_key') && $request->attributes->has('quota_key')) {
                foreach (['quota_key', 'quota_source', 'credit_id'] as $attributeKey) {
                    if ($request->attributes->has($attributeKey)) {
                        $candidate->attributes->set($attributeKey, $request->attributes->get($attributeKey));
                    }
                }
            }

            if (! $candidate->attributes->has('quota_key')) {
                continue;
            }

            $candidate->attributes->set('quota_consume', true);
            $hasQuotaContext = true;
        }

        if (! $hasQuotaContext) {
            return;
        }
    }

    /**
     * @return array{source:string,quota_key:string,credit_id?:int}|null
     */
    protected function resolveQuotaConsumptionContext(Request $request): ?array
    {
        $source = trim((string) $request->attributes->get('quota_source', ''));
        $quotaKey = trim((string) $request->attributes->get('quota_key', ''));

        if ($source === '' || $quotaKey === '') {
            return null;
        }

        $context = [
            'source' => $source,
            'quota_key' => $quotaKey,
        ];

        if ($source === 'credit') {
            $creditId = (int) $request->attributes->get('credit_id', 0);
            if ($creditId < 1) {
                return null;
            }

            $context['credit_id'] = $creditId;
        }

        return $context;
    }
}
