<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 文件管理服务 — 从 FileManagerController 的文件操作方法抽离
 */
final class FileManagerService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/markdown',
        'audio/mpeg',
        'video/mp4',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-zip-compressed',
    ];

    private const FORBIDDEN_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8',
        'pht', 'phar', 'shtml', 'cgi', 'pl', 'py', 'rb', 'sh',
        'asp', 'aspx', 'jsp', 'jspx', 'exe', 'bat', 'cmd', 'dll',
        'htaccess', 'htpasswd',
    ];

    private string $disk = 'public';

    /**
     * 获取文件列表
     */
    public function listFiles(string $path): array
    {
        $disk = Storage::disk($this->disk);
        $files = [];

        $directories = $disk->directories($path);
        foreach ($directories as $dir) {
            $relativePath = $path ? Str::after($dir, $path.'/') : $dir;
            $files[] = [
                'name' => $relativePath,
                'path' => $dir,
                'type' => 'directory',
                'size' => '-',
                'modified' => $disk->lastModified($dir),
            ];
        }

        $fileList = $disk->files($path);
        foreach ($fileList as $file) {
            $relativePath = $path ? Str::after($file, $path.'/') : $file;
            $files[] = [
                'name' => $relativePath,
                'path' => $file,
                'type' => 'file',
                'size' => $disk->size($file),
                'modified' => $disk->lastModified($file),
                'url' => $disk->url($file),
                'extension' => strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)),
            ];
        }

        usort($files, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    /**
     * 获取面包屑导航
     */
    public function getBreadcrumbs(string $path): array
    {
        $parts = array_filter(explode('/', $path));
        $breadcrumbs = [
            ['name' => '根目录', 'path' => ''],
        ];

        $currentPath = '';
        foreach ($parts as $part) {
            $currentPath .= ($currentPath ? '/' : '').$part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $currentPath,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $disk = Storage::disk($this->disk);
        $allFiles = $disk->allFiles();

        $totalSize = 0;
        $imageCount = 0;
        $documentCount = 0;

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'md'];

        foreach ($allFiles as $file) {
            $totalSize += $disk->size($file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($ext, $imageExtensions)) {
                $imageCount++;
            } elseif (in_array($ext, $documentExtensions)) {
                $documentCount++;
            }
        }

        return [
            'total_files' => count($allFiles),
            'total_size' => formatBytes($totalSize),
            'image_count' => $imageCount,
            'document_count' => $documentCount,
        ];
    }

    /**
     * @return array{uploaded: array<string>, failed: array<string>}
     */
    public function upload(array $files, string $path): array
    {
        $uploaded = [];
        $failed = [];

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            try {
                $extension = strtolower((string) $file->getClientOriginalExtension());
                if (in_array($extension, self::FORBIDDEN_EXTENSIONS, true)) {
                    $failed[] = $file->getClientOriginalName();
                    continue;
                }

                $filename = $this->generateUniqueFilename($path, $file->getClientOriginalName());
                if ($file->getMimeType() === 'image/svg+xml') {
                    $this->sanitizeSvg($file);
                }
                $file->storeAs($path, $filename, $this->disk);
                $uploaded[] = $filename;
            } catch (\Throwable $e) {
                $failed[] = $file->getClientOriginalName();
            }
        }

        return ['uploaded' => $uploaded, 'failed' => $failed];
    }

    public function createFolder(string $path, string $folderName): bool
    {
        $fullPath = $path ? $path.'/'.$folderName : $folderName;

        if (Storage::disk($this->disk)->exists($fullPath)) {
            return false;
        }

        Storage::disk($this->disk)->makeDirectory($fullPath);

        return true;
    }

    public function delete(string $path): bool
    {
        if (! Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        Storage::disk($this->disk)->deleteDirectory($path);
        Storage::disk($this->disk)->delete($path);

        return true;
    }

    public function rename(string $oldPath, string $newName): bool
    {
        $dir = dirname($oldPath);
        if ($dir === '.') {
            $dir = '';
        }

        $newPath = $dir ? $dir.'/'.$newName : $newName;

        if (Storage::disk($this->disk)->exists($newPath)) {
            return false;
        }

        Storage::disk($this->disk)->move($oldPath, $newPath);

        return true;
    }

    public function sanitizePath(string $path): string
    {
        $path = trim($path, '/');
        $path = preg_replace('#[^a-zA-Z0-9_\-./]#', '', $path);
        $path = preg_replace('#\.{2,}#', '.', $path);
        $path = trim($path, '.');
        $basePath = Storage::disk($this->disk)->path('');
        $resolved = realpath($basePath.'/'.$path);
        if ($resolved === false || ! str_starts_with($resolved, $basePath)) {
            return '';
        }

        return $path;
    }

    public function generateUniqueFilename(string $path, string $originalName): string
    {
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $filename = (string) preg_replace('/[^a-zA-Z0-9_\-\x{4e00}-\x{9fff}]/u', '_', $filename);
        $filename = trim($filename, '_.-') ?: 'file';

        if ($extension === '' || mb_strlen($extension) > 10) {
            $extension = 'bin';
        }

        $safeName = Str::random(8).'_'.$filename.'.'.$extension;
        $counter = 1;

        while (Storage::disk($this->disk)->exists($path ? $path.'/'.$safeName : $safeName)) {
            $safeName = Str::random(8).'_'.$filename.'_'.$counter.'.'.$extension;
            $counter++;
        }

        return $safeName;
    }

    public function sanitizeSvg(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return;
        }

        $dangerousPatterns = [
            '/<script\b[^>]*>.*?<\/script>/is',
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/javascript\s*:/i',
            '/<iframe\b[^>]*>.*?<\/iframe>/is',
            '/<embed\b[^>]*>/i',
            '/<object\b[^>]*>.*?<\/object>/is',
        ];

        $cleaned = preg_replace($dangerousPatterns, '', $content);

        if ($cleaned !== null && $cleaned !== $content) {
            file_put_contents($file->getPathname(), $cleaned);
        }
    }

    /**
     * @return string[]
     */
    public static function forbiddenExtensions(): array
    {
        return self::FORBIDDEN_EXTENSIONS;
    }

    /**
     * @return string[]
     */
    public static function allowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }
}
