<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\Traits\RespondsWithJsonSuccess;
use App\Models\Feedback;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    use RespondsWithJsonSuccess;

    public function index(Request $request): View
    {
        $feedbacks = Feedback::where('user_id', $request->user()->id)
            ->with('reward')
            ->withCount('replies')
            ->latest('id')
            ->paginate((int) config('ui.pagination.user_list', 10));

        return view('user.feedbacks.index', compact('feedbacks'));
    }

    public function show(Request $request, Feedback $feedback): View
    {
        $this->authorize('view', $feedback);

        $feedback->load(['replies.user', 'reward.grantedBy', 'reward.userCredit']);

        return view('user.feedbacks.show', compact('feedback'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(Feedback::CATEGORIES)],
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:5000',
            'page_url' => ['nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || $value === '') {
                    return;
                }
                $host = parse_url($value, PHP_URL_HOST);
                if ($host !== null && $host !== false && $host !== request()->getHost()) {
                    $fail('页面地址必须为当前站点的链接');
                }
            }],
            'page_name' => 'nullable|string|max:100',
            'visitor_email' => 'nullable|email|max:255',
        ]);

        $data['user_id'] = $request->user()?->id;
        $data['metadata'] = [
            'user_agent' => mb_substr((string) $request->header('User-Agent'), 0, 500),
            'screen' => $this->sanitizeScreen($request->input('screen')),
            'language' => $this->sanitizeLanguage($request->input('language')),
        ];

        if ($data['user_id']) {
            $data['visitor_email'] = null;
        }

        $feedback = Feedback::create($data);

        return $this->respondSuccessPayload([
            'message' => '感谢您的反馈，我们会尽快处理！',
            'data' => [
                'feedback_id' => $feedback->id,
                'title' => $feedback->title,
                'detail_url' => route('feedback.show', ['feedback' => $feedback->id]),
                'list_url' => route('feedback.index'),
            ],
        ]);
    }

    private function sanitizeScreen(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d+x\d+$/', $value)) {
            return $value;
        }

        return null;
    }

    private function sanitizeLanguage(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $value)) {
            return $value;
        }

        return null;
    }

    public function reply(Request $request, Feedback $feedback): JsonResponse
    {
        $this->authorize('reply', $feedback);

        if ($feedback->status === Feedback::STATUS_CLOSED) {
            return $this->fail('该反馈已关闭，无法追加回复', 422);
        }

        $data = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $feedback->replies()->create([
            'user_id' => $request->user()->id,
            'is_admin' => false,
            'content' => $data['content'],
        ]);

        return $this->respondSuccessPayload(['message' => '回复已发送']);
    }

    /**
     * 提交满意度评价（反馈关闭后）
     */
    public function rateSatisfaction(Request $request, Feedback $feedback): JsonResponse
    {
        $this->authorize('view', $feedback);

        if ($feedback->status !== Feedback::STATUS_CLOSED && $feedback->status !== Feedback::STATUS_REPLIED) {
            return $this->fail('仅已回复或已关闭的反馈可评价', 422);
        }

        if ($feedback->satisfaction_score !== null) {
            return $this->fail('已评价，不可重复提交', 422);
        }

        $data = $request->validate([
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $feedback->update([
            'satisfaction_score' => $data['score'],
            'satisfaction_comment' => $data['comment'] ?? null,
        ]);

        return $this->respondSuccessPayload(['message' => '评价已提交，感谢你的反馈']);
    }
}
