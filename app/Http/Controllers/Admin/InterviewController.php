<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EvaluateInterviewAnswerJob;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InterviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->string('status', ''),
            'type' => (string) $request->string('type', ''),
            'keyword' => trim((string) $request->string('keyword', '')),
            'scoring_pending' => $request->boolean('scoring_pending'),
            'jd_mode' => (string) $request->string('jd_mode', ''),
            'jd_match_band' => (string) $request->string('jd_match_band', ''),
            'ai_termination' => (string) $request->string('ai_termination', ''),
        ];

        $interviews = InterviewSession::with(['user', 'resume'])
            ->withCount([
                'questions as pending_scoring_count' => static function (Builder $query): void {
                    $query->whereNotNull('answer')->whereNull('score');
                },
            ])
            ->when($filters['status'] !== '', static function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })
            ->when($filters['type'] !== '', static function (Builder $query) use ($filters): void {
                $query->where('type', $filters['type']);
            })
            ->when($filters['keyword'] !== '', static function (Builder $query) use ($filters): void {
                $keyword = escapeLike($filters['keyword']);
                $query->where(static function (Builder $sub) use ($keyword): void {
                    $sub->where('position', 'like', "%{$keyword}%")
                        ->orWhere('company', 'like', "%{$keyword}%")
                        ->orWhereHas('user', static function (Builder $userQuery) use ($keyword): void {
                            $userQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($filters['scoring_pending'], static function (Builder $query): void {
                $query->whereHas('questions', static function (Builder $questionQuery): void {
                    $questionQuery->whereNotNull('answer')->whereNull('score');
                });
            })
            ->when($filters['jd_mode'] === 'with_jd', static function (Builder $query): void {
                $query->whereNotNull('job_description')->where('job_description', '!=', '');
            })
            ->when($filters['jd_mode'] === 'without_jd', static function (Builder $query): void {
                $query->where(static function (Builder $builder): void {
                    $builder->whereNull('job_description')->orWhere('job_description', '');
                });
            })
            ->when($filters['jd_match_band'] !== '', static function (Builder $query) use ($filters): void {
                $query->whereNotNull('job_description')->where('job_description', '!=', '');
                match ($filters['jd_match_band']) {
                    'high' => $query->where('report->jd_alignment->match_score', '>=', 8),
                    'medium' => $query->where('report->jd_alignment->match_score', '>=', 5)
                        ->where('report->jd_alignment->match_score', '<', 8),
                    'low' => $query->where('report->jd_alignment->match_score', '<', 5),
                    default => null,
                };
            })
            ->when($filters['ai_termination'] === 'terminated', static function (Builder $query): void {
                $query->where('report->early_termination->enabled_by_ai', true);
            })
            ->when($filters['ai_termination'] === 'normal', static function (Builder $query): void {
                $query->where(static function (Builder $builder): void {
                    $builder->whereNull('report->early_termination->enabled_by_ai')
                        ->orWhere('report->early_termination->enabled_by_ai', false);
                });
            })
            ->latest('id')
            ->paginate((int) config('ui.pagination.admin_list', 15))
            ->appends($request->query());

        $types = InterviewSession::query()
            ->distinct('type')
            ->pluck('type')
            ->filter()
            ->values();

        $stats = Cache::remember('admin:interviews:stats', (int) config('cache_ttl.ttl.admin_stats', 120), function () {
            $data = InterviewSession::selectRaw('
                count(*) as total,
                sum(case when date(created_at) = curdate() then 1 else 0 end) as today,
                sum(case when status = \'completed\' then 1 else 0 end) as completed
            ')->first()->toArray();
            $data['pending_score'] = InterviewQuestion::whereNotNull('answer')->whereNull('score')->count();

            return $data;
        });

        return view('admin.interviews.index', compact('interviews', 'types', 'filters', 'stats'));
    }

    public function show(InterviewSession $interview): View
    {
        $interview->load([
            'user',
            'questions' => static fn ($query) => $query->orderBy('round_no'),
        ]);

        return view('admin.interviews.show', compact('interview'));
    }

    public function retryQuestionEvaluation(InterviewSession $interview, InterviewQuestion $question): RedirectResponse
    {
        if ((int) $question->interview_session_id !== (int) $interview->id) {
            return redirect()->route('admin.interviews.show', $interview)
                ->with('error', '题目不属于当前面试会话。');
        }

        if ($question->answer === null) {
            return redirect()->route('admin.interviews.show', $interview)
                ->with('error', '该题尚未回答，无法重试评分。');
        }

        $question->forceFill([
            'score' => null,
            'feedback' => null,
        ])->save();

        EvaluateInterviewAnswerJob::dispatch((int) $question->id)
            ->onQueue((string) config('interview.evaluation_queue', 'default'));

        return redirect()->route('admin.interviews.show', $interview)
            ->with('success', "题目 #{$question->id} 已加入重评分队列。");
    }

    public function retryPendingEvaluation(InterviewSession $interview): RedirectResponse
    {
        $pendingQuestionIds = InterviewQuestion::query()
            ->where('interview_session_id', $interview->id)
            ->whereNotNull('answer')
            ->whereNull('score')
            ->pluck('id');

        foreach ($pendingQuestionIds as $questionId) {
            EvaluateInterviewAnswerJob::dispatch((int) $questionId)
                ->onQueue((string) config('interview.evaluation_queue', 'default'));
        }

        $count = $pendingQuestionIds->count();

        return redirect()->route('admin.interviews.show', $interview)
            ->with('success', $count > 0 ? "已重试 {$count} 条待评分题目。" : '当前没有待评分题目。');
    }

    public function destroy(InterviewSession $interview): RedirectResponse
    {
        $interview->delete();

        return redirect()->route('admin.interviews.index')->with('success', '面试记录已删除。');
    }

    /**
     * 导出面试列表为 CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $query = InterviewSession::query()->with(['user', 'resume']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($keyword = $request->input('keyword')) {
            $escaped = escapeLike($keyword);
            $query->where(function ($q) use ($escaped) {
                $q->where('position', 'like', "%{$escaped}%")
                    ->orWhere('company', 'like', "%{$escaped}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$escaped}%")->orWhere('email', 'like', "%{$escaped}%"));
            });
        }

        $filename = 'interviews_export_'.now()->format('Y_m_d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', '用户', '邮箱', '岗位', '公司', '类型', '状态', '题目数', '已回答', '综合评分', '创建时间']);

            $query->chunk(200, function ($interviews) use ($handle): void {
                foreach ($interviews as $interview) {
                    fputcsv($handle, [
                        $interview->id,
                        $interview->user?->name ?? '',
                        $interview->user?->email ?? '',
                        $interview->position ?? '',
                        $interview->company ?? '',
                        $interview->type ?? '',
                        $interview->status,
                        $interview->question_count ?? 0,
                        $interview->answered_count ?? 0,
                        $interview->overall_score ?? '-',
                        $interview->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
