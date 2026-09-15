<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class DeviceFingerprintController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fp' => 'required|string|max:128',
        ]);

        $fp = $request->string('fp');
        $hashedFp = hash('sha256', $fp . config('app.key'));

        if ($request->user()) {
            Cache::put("user_device:{$request->user()->id}", $hashedFp, now()->addDays(30));
        }

        return response()->json(['status' => 'ok'], 204);
    }
}
