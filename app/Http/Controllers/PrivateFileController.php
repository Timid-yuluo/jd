<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PrivateFileController extends Controller
{
    public function avatar(Request $request, string $filename): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, '签名已过期或无效。');
        }

        $path = 'avatars/'.$filename;

        if (! Storage::disk('private')->exists($path)) {
            abort(404, '文件不存在。');
        }

        $mimeType = Storage::disk('private')->mimeType($path);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (! in_array($mimeType, $allowedMimes, true)) {
            abort(403, '不支持的文件类型。');
        }

        return Storage::disk('private')->response($path, $filename, [
            'Cache-Control' => 'private, max-age=7200',
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
