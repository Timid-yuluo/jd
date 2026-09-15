<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
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

    protected function notFound(string $message = '资源不存在'): JsonResponse
    {
        return $this->fail($message, 404);
    }

    protected function forbidden(string $message = '无权访问'): JsonResponse
    {
        return $this->fail($message, 403);
    }

    protected function unprocessable(string $message = '请求参数有误', mixed $errors = null): JsonResponse
    {
        return $this->fail($message, 422, $errors);
    }

    protected function serverError(string $message = '服务器内部错误，请稍后重试'): JsonResponse
    {
        return $this->fail($message, 500);
    }
}
