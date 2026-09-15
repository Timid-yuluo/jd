<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FileManagerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 文件管理器控制器 — 文件操作委托 FileManagerService
 */
final class FileManagerController extends Controller
{
    public function __construct(
        private readonly FileManagerService $fileService,
    ) {}

    public function index(Request $request): View
    {
        $currentPath = $this->fileService->sanitizePath($request->get('path', ''));
        $files = $this->fileService->listFiles($currentPath);
        $breadcrumbs = $this->fileService->getBreadcrumbs($currentPath);
        $stats = $this->fileService->getStats();

        return view('admin.file-manager.index', compact(
            'files',
            'currentPath',
            'breadcrumbs',
            'stats'
        ));
    }

    public function upload(Request $request): RedirectResponse
    {
        $forbidden = FileManagerService::forbiddenExtensions();
        $allowedMimes = FileManagerService::allowedMimeTypes();

        $request->validate([
            'files' => 'required|array',
            'files.*' => [
                'file',
                'max:'.config('ui.upload.document_max_kb', 10240),
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,svg,mp3,mp4,zip,rar',
                function (string $attribute, mixed $value, \Closure $fail) use ($forbidden, $allowedMimes): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $extension = mb_strtolower((string) $value->getClientOriginalExtension());

                    if (in_array($extension, $forbidden, true)) {
                        $fail("文件类型 .{$extension} 不被允许上传。");
                    }

                    $mimeType = $value->getMimeType();

                    if (! in_array($mimeType, $allowedMimes, true)) {
                        $fail("文件 MIME 类型 {$mimeType} 不被允许。");
                    }
                },
            ],
            'path' => 'nullable|string',
        ]);

        $path = $this->fileService->sanitizePath($request->get('path', ''));
        $result = $this->fileService->upload($request->file('files'), $path);

        $message = '';
        if (! empty($result['uploaded'])) {
            $message .= sprintf('成功上传 %d 个文件。', count($result['uploaded']));
        }
        if (! empty($result['failed'])) {
            $message .= sprintf(' %d 个文件上传失败。', count($result['failed']));
        }

        return redirect()
            ->route('admin.file-manager.index', ['path' => $path])
            ->with(empty($result['failed']) ? 'success' : 'warning', $message);
    }

    public function createFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'folder_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9_\-\/]+$/',
            'path' => 'nullable|string',
        ]);

        $path = $this->fileService->sanitizePath($validated['path'] ?? '');
        $folderName = trim($validated['folder_name'], '/');

        if (! $this->fileService->createFolder($path, $folderName)) {
            return redirect()->back()->with('error', '文件夹已存在。');
        }

        return redirect()
            ->route('admin.file-manager.index', ['path' => $path])
            ->with('success', '文件夹创建成功。');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $path = $this->fileService->sanitizePath($validated['path']);
        $parentPath = dirname($path);
        if ($parentPath === '.') {
            $parentPath = '';
        }

        if (! $this->fileService->delete($path)) {
            return redirect()->back()->with('error', '文件或文件夹不存在。');
        }

        return redirect()
            ->route('admin.file-manager.index', ['path' => $parentPath])
            ->with('success', '删除成功。');
    }

    public function download(string $path): StreamedResponse|RedirectResponse
    {
        $path = $this->fileService->sanitizePath($path);
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $disk->exists($path) || $disk->directoryExists($path)) {
            return redirect()->back()->with('error', '文件不存在。');
        }

        return $disk->download($path);
    }

    public function rename(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'old_path' => 'required|string',
            'new_name' => 'required|string|max:100|regex:/^[a-zA-Z0-9_\-\.]+$/',
        ]);

        $oldPath = $this->fileService->sanitizePath($validated['old_path']);
        $newName = $validated['new_name'];

        $dir = dirname($oldPath);
        if ($dir === '.') {
            $dir = '';
        }

        if (! $this->fileService->rename($oldPath, $newName)) {
            return redirect()->back()->with('error', '目标名称已存在。');
        }

        return redirect()
            ->route('admin.file-manager.index', ['path' => $dir])
            ->with('success', '重命名成功。');
    }
}
