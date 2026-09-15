<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiResponse
{
    /**
     * Build success response in unified structure.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(array $data = [], array $meta = [], string $message = 'ok', int $status = 200): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    /**
     * Build error response in unified structure.
     *
     * @param  array<int, array<string, string>>  $errors
     */
    public static function error(
        int $code,
        string $message,
        Request $request,
        array $errors = [],
        int $status = 422,
    ): JsonResponse {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'trace_id' => $request->attributes->get('request_id'),
        ], $status);
    }
}
