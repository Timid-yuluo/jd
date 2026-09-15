<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use Illuminate\Http\JsonResponse;

trait RespondsWithJsonSuccess
{
    /**
     * @param  array<string, mixed>  $payload
     */
    protected function respondSuccessPayload(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $payload['message'] ?? '',
        ], $payload), $status);
    }

    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        $payload = is_array($data) ? $data : [];

        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
        ], $payload), $code);
    }

    protected function fail(string $message = '操作失败', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
