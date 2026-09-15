<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ResumeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'target_job' => ['nullable', 'string', 'max:255'],
            'target_company' => ['nullable', 'string', 'max:255'],
            'content_raw' => ['required', 'string'],
            'template' => ['nullable', 'string', 'in:classic,modern,minimal,timeline,creative,elegant'],
            'career_track_id' => ['nullable', 'integer', 'exists:career_tracks,id'],
            'modules' => ['nullable', 'string', 'json', 'max:262144'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '请输入简历标题。',
            'content_raw.required' => '请输入简历内容。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $plan = $user->currentPlan();
            $resumeLimit = (int) ($plan?->getTotalLimit('resumes') ?? 0);
            if ($resumeLimit <= 0) {
                return;
            }

            $resumeUsed = (int) $user->resumes()->count();
            if ($resumeUsed >= $resumeLimit) {
                $validator->errors()->add(
                    'title',
                    "当前套餐最多可创建 {$resumeLimit} 份简历，已达到上限，请升级套餐后继续创建。"
                );
            }
        });
    }
}
