<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health_check_'.time();
            Cache::put($key, true, 10);
            $result = Cache::get($key);
            Cache::forget($key);

            return $result === true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.default', 'local'));
            $testKey = 'health_check_'.time();
            $disk->put($testKey, 'ok');

            if ($disk->get($testKey) !== 'ok') {
                return false;
            }

            $disk->delete($testKey);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
