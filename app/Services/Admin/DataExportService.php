<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Resume;
use App\Models\UsageLog;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 数据导出服务 — 从 DataExportController 的 5 个 export 方法抽离
 */
final class DataExportService
{
    public function __construct(
        private readonly CsvExportService $csvExport,
    ) {}

    public function exportUsers(): StreamedResponse
    {
        $headers = ['ID', '姓名', '邮箱', '学校', '专业', '管理员', '简历数', '面试数', '投递数', '注册时间', '最后登录'];

        $rows = (function (): \Generator {
            User::withCount(['resumes', 'interviewSessions', 'jobApplications'])
                ->chunk(500, function ($users) {
                    foreach ($users as $user) {
                        yield [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->school ?? '',
                            $user->major ?? '',
                            $user->is_admin ? '是' : '否',
                            $user->resumes_count,
                            $user->interview_sessions_count,
                            $user->job_applications_count,
                            $user->created_at->format('Y-m-d H:i:s'),
                            $user->last_login_at?->format('Y-m-d H:i:s') ?? '',
                        ];
                    }
                });
        })();

        return $this->csvExport->stream(
            'users_'.now()->format('Ymd_His').'.csv',
            $headers,
            $rows
        );
    }

    public function exportResumes(): StreamedResponse
    {
        $headers = ['ID', '用户', '标题', 'ATS评分', '优化次数', '创建时间'];

        $rows = (function (): \Generator {
            Resume::with('user:id,name')->chunk(500, function ($resumes) {
                foreach ($resumes as $resume) {
                    yield [
                        $resume->id,
                        $resume->user?->name ?? '',
                        $resume->title ?? '',
                        $resume->ats_score ?? '',
                        $resume->optimization_count ?? 0,
                        $resume->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            });
        })();

        return $this->csvExport->stream(
            'resumes_'.now()->format('Ymd_His').'.csv',
            $headers,
            $rows
        );
    }

    public function exportInterviews(): StreamedResponse
    {
        $headers = ['ID', '用户', '类型', '状态', '问题数', '平均分', '创建时间'];

        $rows = (function (): \Generator {
            InterviewSession::with(['user:id,name', 'questions'])->chunk(500, function ($interviews) {
                foreach ($interviews as $interview) {
                    yield [
                        $interview->id,
                        $interview->user?->name ?? '',
                        $interview->type ?? '',
                        $interview->status ?? '',
                        $interview->questions->count(),
                        $interview->questions->avg('score') ? round($interview->questions->avg('score'), 1) : '',
                        $interview->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            });
        })();

        return $this->csvExport->stream(
            'interviews_'.now()->format('Ymd_His').'.csv',
            $headers,
            $rows
        );
    }

    public function exportApplications(): StreamedResponse
    {
        $headers = ['ID', '用户', '公司', '职位', '状态', '薪资', '来源', '创建时间'];

        $rows = (function (): \Generator {
            JobApplication::with('user:id,name')->chunk(500, function ($applications) {
                foreach ($applications as $app) {
                    yield [
                        $app->id,
                        $app->user?->name ?? '',
                        $app->company ?? '',
                        $app->position ?? '',
                        $app->status ?? '',
                        $app->salary ?? '',
                        $app->source ?? '',
                        $app->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            });
        })();

        return $this->csvExport->stream(
            'applications_'.now()->format('Ymd_His').'.csv',
            $headers,
            $rows
        );
    }

    public function exportAiUsage(): StreamedResponse
    {
        $headers = ['ID', '用户ID', '场景', '模型', 'Prompt Tokens', 'Completion Tokens', '总Tokens', '费用(微美元)', '创建时间'];

        $rows = (function (): \Generator {
            UsageLog::chunk(500, function ($logs) {
                foreach ($logs as $log) {
                    yield [
                        $log->id,
                        $log->user_id ?? '',
                        $log->scenario ?? '',
                        $log->model ?? '',
                        $log->prompt_tokens ?? 0,
                        $log->completion_tokens ?? 0,
                        ($log->prompt_tokens ?? 0) + ($log->completion_tokens ?? 0),
                        $log->cost_micros ?? 0,
                        $log->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            });
        })();

        return $this->csvExport->stream(
            'ai_usage_'.now()->format('Ymd_His').'.csv',
            $headers,
            $rows
        );
    }
}
