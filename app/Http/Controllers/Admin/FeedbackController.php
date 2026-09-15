<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FeedbackReplyMail;
use App\Models\Feedback;
use App\Models\FeedbackAuditLog;
use App\Models\UserCredit;
use App\Services\Feedback\FeedbackAuditLogger;
use App\Services\Feedback\FeedbackRewardService;
use App\Services\Notification\UserNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use RuntimeException;

final class FeedbackController extends Controller
{
    private const STATUS_TRANSITIONS = [
        'pending' => ['processing', 'closed'],
        'processing' => ['replied', 'closed'],
        'replied' => ['processing', 'closed'],
        'closed' => [],
    ];

    public function __construct(
        private readonly FeedbackAuditLogger $feedbackAuditLogger,
        private readonly UserNotificationService $userNotificationService,
    ) {}

    private function validateStatusTransition(Feedback $feedback, string $newStatus): void
    {
        $allowed = self::STATUS_TRANSITIONS[$feedback->status] ?? [];

        if ($allowed === []) {
            throw new RuntimeException('已关闭的反馈不可变更状态');
        }

        if (! in_array($newStatus, $allowed, true)) {
            throw new RuntimeException("不允许从「{$feedback->getStatusLabel()}」变更为该状态");
        }
    }

    public function index(Request $request): View
    {
        $query = Feedback::with(['user'])->withCount('replies')->latest('id');

        if ($status = $request->input('status')) {
            if (in_array($status, Feedback::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        if ($category = $request->input('category')) {
            if (in_array($category, Feedback::CATEGORIES, true)) {
                $query->where('category', $category);
            }
        }

        if ($priority = $request->input('priority')) {
            if (in_array($priority, Feedback::PRIORITIES, true)) {
                $query->where('priority', $priority);
            }
        }

        if ($search = $request->input('search')) {
            $escaped = escapeLike($search);
            $query->where(function ($q) use ($escaped) {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('content', 'like', "%{$escaped}%");
            });
        }

        $feedbacks = $query->paginate((int) config('ui.pagination.admin_table', 20))->appends($request->only('status', 'category', 'priority', 'search'));

        // 单次聚合查询替代5次COUNT
        $statusCounts = Feedback::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $counts = [
            'total' => array_sum($statusCounts),
            'pending' => $statusCounts['pending'] ?? 0,
            'processing' => $statusCounts['processing'] ?? 0,
            'replied' => $statusCounts['replied'] ?? 0,
            'closed' => $statusCounts['closed'] ?? 0,
        ];

        return view('admin.feedbacks.index', compact('feedbacks', 'counts'));
    }

    public function show(Feedback $feedback): View
    {
        $feedback->load([
            'user',
            'replies.user',
            'reward.grantedBy',
            'reward.userCredit',
            'adoptedBy',
            'auditLogs.changedByUser',
        ]);

        return view('admin.feedbacks.show', compact('feedback'));
    }

    public function updateStatus(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Feedback::STATUSES)],
            'priority' => ['nullable', Rule::in(Feedback::PRIORITIES)],
        ]);

        $this->validateStatusTransition($feedback, $data['status']);

        $oldValues = [
            'status' => $feedback->status,
            'priority' => $feedback->priority,
        ];

        $feedback->forceFill([
            'status' => $data['status'],
            'priority' => $data['priority'] ?? $feedback->priority,
        ])->save();

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_STATUS_UPDATED,
            $request,
            $oldValues,
            [
                'status' => $feedback->status,
                'priority' => $feedback->priority,
            ],
        );

        // 状态变更站内通知
        if ($feedback->user_id && $oldValues['status'] !== $feedback->status) {
            $statusLabel = $feedback->getStatusLabel();
            $this->userNotificationService->createSiteNotification(
                $feedback->user_id,
                '反馈状态更新',
                "你的反馈「{$feedback->title}」状态已变更为：{$statusLabel}",
                'feedback',
            );
        }

        return response()->json(['success' => true, 'message' => '状态已更新']);
    }

    public function reply(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'notify_user' => 'nullable|boolean',
        ]);

        $reply = $feedback->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin' => true,
            'content' => $data['content'],
        ]);

        $feedback->forceFill(['status' => Feedback::STATUS_REPLIED])->save();

        $mailSent = false;

        if (! empty($data['notify_user'])) {
            $email = $feedback->user?->email ?? $feedback->visitor_email;
            if (! $email) {
                return response()->json([
                    'success' => false,
                    'message' => '已勾选邮件通知，但该反馈未关联可用邮箱。',
                ], 422);
            }

            try {
                Mail::to($email)->queue(new FeedbackReplyMail($feedback, $reply));
                $mailSent = true;
            } catch (\Throwable $e) {
                Log::error('反馈回复邮件发送失败', [
                    'feedback_id' => $feedback->id,
                    'target_email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '邮件发送失败，请稍后重试或联系技术支持',
                ], 500);
            }
        }

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_REPLY_SENT,
            $request,
            [],
            [],
            [
                'reply_id' => $reply->id,
                'notify_user' => ! empty($data['notify_user']),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => ! empty($data['notify_user'])
                ? ($mailSent ? '回复已发送，邮件通知成功' : '回复已发送')
                : '回复已发送',
            'reply' => [
                'content' => $reply->content,
                'is_admin' => true,
                'admin_name' => $request->user()->name,
                'created_at' => $reply->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function updateNote(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $oldValues = [
            'admin_note' => $feedback->admin_note,
        ];

        $feedback->forceFill([
            'admin_note' => $data['admin_note'],
        ])->save();

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_NOTE_UPDATED,
            $request,
            $oldValues,
            [
                'admin_note' => $feedback->admin_note,
            ],
        );

        return response()->json(['success' => true, 'message' => '备注已保存']);
    }

    public function updateAdoption(Request $request, Feedback $feedback): JsonResponse
    {
        $data = $request->validate([
            'adoption_status' => ['required', Rule::in(Feedback::ADOPTION_STATUSES)],
            'adoption_note' => 'nullable|string|max:2000',
        ]);

        if ($feedback->reward !== null && $data['adoption_status'] !== Feedback::ADOPTION_ADOPTED) {
            return response()->json([
                'success' => false,
                'message' => '该反馈已发放奖励，采纳结果不能改回未采纳。',
            ], 422);
        }

        $isAdopted = $data['adoption_status'] === Feedback::ADOPTION_ADOPTED;

        $oldValues = [
            'adoption_status' => $feedback->adoption_status,
            'adoption_note' => $feedback->adoption_note,
            'adopted_at' => optional($feedback->adopted_at)->toDateTimeString(),
            'adopted_by' => $feedback->adopted_by,
        ];

        $feedback->forceFill([
            'adoption_status' => $data['adoption_status'],
            'adoption_note' => $data['adoption_note'],
            'adopted_at' => $isAdopted ? now() : null,
            'adopted_by' => $isAdopted ? $request->user()->id : null,
        ])->save();

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_ADOPTION_UPDATED,
            $request,
            $oldValues,
            [
                'adoption_status' => $feedback->adoption_status,
                'adoption_note' => $feedback->adoption_note,
                'adopted_at' => optional($feedback->adopted_at)->toDateTimeString(),
                'adopted_by' => $feedback->adopted_by,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => '采纳结果已更新',
            'adoption' => [
                'status' => $feedback->adoption_status,
                'label' => $feedback->getAdoptionLabel(),
            ],
        ]);
    }

    public function reward(Request $request, Feedback $feedback, FeedbackRewardService $feedbackRewardService): JsonResponse
    {
        $data = $request->validate([
            'quota_key' => ['nullable', Rule::in(array_keys(UserCredit::quotaOptions()))],
            'credits' => 'required|integer|min:1|max:9999',
            'validity_days' => 'nullable|integer|min:0|max:3650',
            'reason' => 'nullable|string|max:500',
            'adoption_note' => 'nullable|string|max:2000',
        ]);

        $oldValues = [
            'adoption_status' => $feedback->adoption_status,
            'adoption_note' => $feedback->adoption_note,
            'reward_id' => $feedback->reward?->id,
        ];

        try {
            $reward = $feedbackRewardService->grant($feedback, (int) $request->user()->id, [
                'quota_key' => $data['quota_key'] ?? null,
                'credits' => (int) $data['credits'],
                'validity_days' => array_key_exists('validity_days', $data) && $data['validity_days'] !== null
                    ? (int) $data['validity_days']
                    : null,
                'reason' => $data['reason'] ?? null,
                'adoption_note' => $data['adoption_note'] ?? null,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $feedback->refresh()->loadMissing('reward');

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_REWARD_GRANTED,
            $request,
            $oldValues,
            [
                'adoption_status' => $feedback->adoption_status,
                'adoption_note' => $feedback->adoption_note,
                'reward_id' => $reward->id,
            ],
            [
                'quota_key' => $reward->quota_key,
                'credits' => $reward->credits,
                'validity_days' => $reward->validity_days,
                'reason' => $reward->reason,
                'user_credit_id' => $reward->user_credit_id,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => '已采纳反馈并赠送次卡',
            'reward' => [
                'quota_label' => UserCredit::quotaLabel($reward->quota_key),
                'credits' => $reward->credits,
                'granted_at' => $reward->granted_at?->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function destroy(Request $request, Feedback $feedback): JsonResponse
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        $oldValues = [
            'status' => $feedback->status,
            'priority' => $feedback->priority,
            'adoption_status' => $feedback->adoption_status,
            'has_reward' => $feedback->reward()->exists(),
        ];

        $this->feedbackAuditLogger->log(
            $feedback,
            FeedbackAuditLog::ACTION_FEEDBACK_DELETED,
            $request,
            $oldValues,
        );

        $feedback->delete();

        return response()->json(['success' => true, 'message' => '反馈已删除']);
    }
}
