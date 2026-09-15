<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\UsageLog;
use App\Models\User;
use App\Services\Admin\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 数据导出控制器 — 导出逻辑委托 DataExportService
 */
final class DataExportController extends Controller
{
    public function __construct(
        private readonly DataExportService $exportService,
    ) {}

    public function index(): View
    {
        $exportTypes = [
            'users' => [
                'label' => '用户数据',
                'description' => '导出所有用户的基本信息和统计数据',
                'icon' => 'ti-users',
                'count' => User::count(),
            ],
            'resumes' => [
                'label' => '简历数据',
                'description' => '导出所有简历的基本信息和评分数据',
                'icon' => 'ti-file-text',
                'count' => Resume::count(),
            ],
            'interviews' => [
                'label' => '面试数据',
                'description' => '导出所有面试会话的统计数据',
                'icon' => 'ti-message-chatbot',
                'count' => InterviewSession::count(),
            ],
            'applications' => [
                'label' => '投递数据',
                'description' => '导出所有求职申请的统计数据',
                'icon' => 'ti-layout-kanban',
                'count' => JobApplication::count(),
            ],
            'ai_usage' => [
                'label' => 'AI 用量数据',
                'description' => '导出AI调用记录和Token消耗统计',
                'icon' => 'ti-api',
                'count' => UsageLog::count(),
            ],
        ];

        return view('admin.data-export.index', compact('exportTypes'));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        return match ($type) {
            'users' => $this->exportService->exportUsers(),
            'resumes' => $this->exportService->exportResumes(),
            'interviews' => $this->exportService->exportInterviews(),
            'applications' => $this->exportService->exportApplications(),
            'ai_usage' => $this->exportService->exportAiUsage(),
            default => abort(404),
        };
    }

    public function preview(string $type): JsonResponse
    {
        $data = match ($type) {
            'users' => User::latest()->limit((int) config('ui.limit.data_preview', 5))->get(['id', 'name', 'email', 'is_admin', 'created_at']),
            'resumes' => Resume::with('user:id,name')->latest()->limit((int) config('ui.limit.data_preview', 5))->get(['id', 'user_id', 'title', 'created_at']),
            'interviews' => InterviewSession::with('user:id,name')->latest()->limit((int) config('ui.limit.data_preview', 5))->get(['id', 'user_id', 'type', 'status', 'created_at']),
            'applications' => JobApplication::with('user:id,name')->latest()->limit((int) config('ui.limit.data_preview', 5))->get(['id', 'user_id', 'company', 'position', 'status', 'created_at']),
            'ai_usage' => UsageLog::latest()->limit((int) config('ui.limit.data_preview', 5))->get(['id', 'user_id', 'scenario', 'model', 'prompt_tokens', 'completion_tokens', 'cost_micros', 'created_at']),
            default => [],
        };

        return response()->json([
            'columns' => count($data) > 0 ? array_keys($data->first()->toArray()) : [],
            'rows' => $data,
        ]);
    }
}
