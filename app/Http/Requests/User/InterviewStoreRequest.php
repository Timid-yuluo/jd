<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\InterviewSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class InterviewStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'resume_id' => [
                'required',
                Rule::exists('resumes', 'id')->where(static fn ($query) => $query->where('user_id', $userId)),
            ],
            'candidate_profile' => ['required', 'in:fresh_graduate,no_experience,junior,experienced'],
            'position' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_description' => ['nullable', 'string', 'max:8000'],
            'type' => ['required', Rule::in(config('interview.supported_types', ['mixed']))],
            'mode' => ['required', 'in:text,voice'],
            'is_practice' => ['nullable', 'boolean'],
            'language' => ['required', 'in:zh,en'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'question_count' => ['required', 'integer', 'min:1', 'max:50'],
            'tech_keywords' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'resume_id.required' => '请选择关联的简历。',
            'resume_id.exists' => '所选简历不存在或不属于当前账号。',
            'candidate_profile.required' => '请选择你的求职身份。',
            'candidate_profile.in' => '求职身份选项无效。',
            'position.required' => '请输入应聘职位。',
            'type.required' => '请选择面试类型。',
            'type.in' => '面试类型无效。',
            'question_count.required' => '请选择面试题目数量。',
            'question_count.integer' => '题目数量必须为整数。',
            'question_count.min' => '题目数量至少为1题。',
            'question_count.max' => '题目数量不能超过50题。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $isPractice = (bool) $this->input('is_practice', false);

            // 练习模式跳过额度相关校验
            if ($isPractice) {
                return;
            }

            $plan = $user?->currentPlan();
            $quotas = is_array($plan?->quotas ?? null) ? $plan->quotas : [];
            $customQuestionsEnabled = (bool) ($quotas['custom_questions'] ?? false);

            $maxQuestions = max(1, (int) (($quotas['interview_sessions']['max_questions'] ?? null) ?: config('interview.max_questions', 5)));
            $requestedQuestionCount = (int) $this->input('question_count', $maxQuestions);
            if ($requestedQuestionCount > $maxQuestions) {
                $validator->errors()->add(
                    'question_count',
                    "当前套餐单场面试最多支持 {$maxQuestions} 题。"
                );
            }

            if (! $customQuestionsEnabled && trim((string) $this->input('job_description', '')) !== '') {
                $validator->errors()->add(
                    'job_description',
                    '当前套餐暂不支持 JD 定制题目，请升级后再填写岗位 JD。'
                );
            }

            // 每人最多同时进行3场未完成面试
            $activeCount = InterviewSession::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [InterviewSession::STATUS_PENDING, InterviewSession::STATUS_IN_PROGRESS, InterviewSession::STATUS_PAUSED])
                ->count();
            if ($activeCount >= 3) {
                $validator->errors()->add(
                    'position',
                    '你已有 ' . $activeCount . ' 场进行中的面试，请先完成或结束后再创建新面试。'
                );
            }
        });
    }
}
