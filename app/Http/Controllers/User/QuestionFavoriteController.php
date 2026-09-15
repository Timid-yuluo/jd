<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestion;
use App\Models\InterviewQuestionFavorite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 面试问题收藏（错题本）控制器
 */
final class QuestionFavoriteController extends Controller
{
    /**
     * 收藏/取消收藏面试问题
     */
    public function toggle(Request $request, InterviewQuestion $question): JsonResponse
    {
        $user = $request->user();

        // 确保用户有权访问该问题
        if ($question->interviewSession?->user_id !== $user->id) {
            abort(403);
        }

        $favorite = InterviewQuestionFavorite::query()
            ->where('user_id', $user->id)
            ->where('interview_question_id', $question->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json(['favorited' => false, 'message' => '已取消收藏']);
        }

        InterviewQuestionFavorite::create([
            'user_id' => $user->id,
            'interview_question_id' => $question->id,
            'note' => $request->input('note'),
        ]);

        return response()->json(['favorited' => true, 'message' => '已收藏']);
    }

    /**
     * 更新收藏备注
     */
    public function updateNote(Request $request, InterviewQuestionFavorite $favorite): JsonResponse
    {
        if ($favorite->user_id !== $request->user()->id) {
            abort(403);
        }

        $favorite->update(['note' => $request->input('note', '')]);

        return response()->json(['ok' => true]);
    }

    /**
     * 错题本列表
     */
    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->questionFavorites()
            ->with(['question.interviewSession'])
            ->latest()
            ->paginate((int) config('ui.pagination.user_list', 20));

        return view('user.interviews.favorites', compact('favorites'));
    }

    /**
     * 删除收藏
     */
    public function destroy(Request $request, InterviewQuestionFavorite $favorite): JsonResponse
    {
        if ($favorite->user_id !== $request->user()->id) {
            abort(403);
        }

        $favorite->delete();

        return response()->json(['ok' => true]);
    }
}
