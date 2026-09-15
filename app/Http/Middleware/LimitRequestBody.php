<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LimitRequestBody
{
    private const DEFAULT_MAX_SIZE = 10 * 1024 * 1024;

    private const UPLOAD_MAX_SIZE = 20 * 1024 * 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $maxSize = $this->resolveMaxSize($request);

        if ($maxSize <= 0) {
            return $next($request);
        }

        // 优先检查 Content-Length 头（不触发全量读取），仅在头缺失或可疑时读取实际内容
        $contentLength = (int) $request->header('Content-Length', '0');

        if ($contentLength > $maxSize) {
            return response()->json([
                'message' => '请求体过大，请减少上传内容。',
            ], 413);
        }

        // 仅对非上传路由检查实际内容大小（上传路由由 PHP 自身处理临时文件，不会全量驻留内存）
        if (! $this->isUploadRoute($request)) {
            $actualSize = strlen($request->getContent());
            if ($actualSize > $maxSize) {
                return response()->json([
                    'message' => '请求体过大，请减少上传内容。',
                ], 413);
            }
        }

        return $next($request);
    }

    private function resolveMaxSize(Request $request): int
    {
        if ($this->isUploadRoute($request)) {
            return (int) config('request.upload_max_size', self::UPLOAD_MAX_SIZE);
        }

        return (int) config('request.body_max_size', self::DEFAULT_MAX_SIZE);
    }

    private function isUploadRoute(Request $request): bool
    {
        $uploadPaths = [
            'resumes/upload',
            'resumes/import',
            'profile/avatar',
            'file-manager/upload',
            'admin/file-manager/upload',
        ];

        foreach ($uploadPaths as $path) {
            if ($request->is($path) || $request->is($path.'/*')) {
                return true;
            }
        }

        return false;
    }
}
