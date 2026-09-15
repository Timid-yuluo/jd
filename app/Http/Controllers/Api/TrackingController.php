<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\VisitTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TrackingController
{
    public function __construct(
        private readonly VisitTrackingService $trackingService,
    ) {}

    public function duration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|size:64',
            'path' => 'required|string|max:500',
            'duration_ms' => 'required|integer|min:0|max:3600000',
        ]);

        $this->trackingService->updateDuration(
            $validated['session_id'],
            $validated['path'],
            (int) $validated['duration_ms'],
        );

        return response()->json(['ok' => true]);
    }

    public function event(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'path' => 'nullable|string|max:500',
        ]);

        $this->trackingService->recordEvent(
            $request,
            $validated['event'],
            $validated['label'] ?? null,
        );

        return response()->json(['ok' => true]);
    }
}
