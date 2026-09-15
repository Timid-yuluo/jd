<?php

declare(strict_types=1);

namespace App\Services\Resume\Builders;

use Illuminate\Support\Facades\File;

final class DocxMediaService
{
    /**
     * @return array{filename:string,content:string,extension:string}|null
     */
    public function resolveAvatarAsset(string $avatar): ?array
    {
        $avatar = trim($avatar);
        if ($avatar === '') {
            return null;
        }

        $binary = null;
        $extension = null;

        if (preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,(.+)$/', $avatar, $matches) === 1) {
            $extension = strtolower((string) $matches[1]);
            $binary = base64_decode((string) $matches[2], true) ?: null;
        } else {
            $path = $this->resolveAvatarLocalPath($avatar);
            if ($path !== null && File::exists($path) && is_readable($path)) {
                $binary = File::get($path);
                $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            }
        }

        if (! is_string($binary) || $binary === '' || ! is_string($extension) || $extension === '') {
            return null;
        }

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if ($extension === 'webp') {
            $converted = $this->convertWebpToPngBinary($binary);
            if ($converted === null) {
                return null;
            }
            $binary = $converted;
            $extension = 'png';
        }

        if (! in_array($extension, ['jpg', 'png', 'gif'], true)) {
            return null;
        }

        return [
            'filename' => 'avatar.'.$extension,
            'content' => $binary,
            'extension' => $extension,
        ];
    }

    private function resolveAvatarLocalPath(string $avatar): ?string
    {
        if ($avatar === '') {
            return null;
        }

        $parsedPath = parse_url($avatar, PHP_URL_PATH);
        if (is_string($parsedPath) && $parsedPath !== '') {
            return public_path(ltrim($parsedPath, '/'));
        }

        if (str_starts_with($avatar, '/')) {
            return public_path(ltrim($avatar, '/'));
        }

        return null;
    }

    private function convertWebpToPngBinary(string $binary): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        ob_start();
        imagepng($image);
        $pngBinary = ob_get_clean();
        imagedestroy($image);

        return is_string($pngBinary) && $pngBinary !== '' ? $pngBinary : null;
    }
}
