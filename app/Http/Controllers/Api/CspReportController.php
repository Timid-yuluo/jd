<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class CspReportController
{
    public function __invoke(Request $request): JsonResponse
    {
        $report = $request->json('csp-report');

        if ($report) {
            Log::warning('CSP 违规报告', [
                'document_uri' => $report['document-uri'] ?? null,
                'violated_directive' => $report['violated-directive'] ?? null,
                'blocked_uri' => $report['blocked-uri'] ?? null,
                'source_file' => $report['source-file'] ?? null,
                'line_number' => $report['line-number'] ?? null,
                'original_policy' => $report['original-policy'] ?? null,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
