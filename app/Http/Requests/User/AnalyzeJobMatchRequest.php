<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\JobMatchAnalysis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class AnalyzeJobMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'job_description' => ['required', 'string', 'min:50', 'max:'.$this->jobDescriptionMaxLength()],
            'resume_id' => [
                'nullable',
                'integer',
                Rule::exists('resumes', 'id')->where(static fn ($query) => $query->where('user_id', $userId)),
            ],
            'use_credit' => ['nullable', 'boolean'],
            'credit_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        $jobDescriptionMaxLength = $this->jobDescriptionMaxLength();

        return [
            'job_description.required' => '请粘贴岗位描述后再分析。',
            'job_description.min' => '岗位描述至少需要 50 字，请补充后再分析。',
            'job_description.max' => "岗位描述最多支持 {$jobDescriptionMaxLength} 字，请精简后再分析。",
            'resume_id.integer' => '所选简历格式无效。',
            'resume_id.exists' => '所选简历不存在或不属于当前账号。',
            'use_credit.boolean' => '次卡确认参数格式无效。',
            'credit_id.integer' => '所选次卡格式无效。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->wantsCreditConsumption() && $this->selectedCreditId() === null) {
                $validator->errors()->add('credit_id', '请选择要使用的次卡后再继续分析。');
            }

            if (! $this->wantsCreditConsumption() && $this->selectedCreditId() !== null) {
                $validator->errors()->add('credit_id', '未确认使用次卡时，不能直接提交次卡编号。');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $jobDescription = str_replace("\r\n", "\n", (string) $this->input('job_description', ''));
        $jobDescription = preg_replace("/\n{3,}/u", "\n\n", $jobDescription) ?? $jobDescription;
        $jobDescription = trim($jobDescription);
        $resumeId = $this->input('resume_id');
        $creditId = $this->input('credit_id');
        $useCredit = $this->input('use_credit');

        $this->merge([
            'job_description' => $jobDescription,
            'resume_id' => $resumeId === '' ? null : $resumeId,
            'use_credit' => $useCredit === null || $useCredit === '' ? null : $this->boolean('use_credit'),
            'credit_id' => $creditId === '' ? null : $creditId,
        ]);
    }

    public function jobDescription(): string
    {
        return (string) $this->validated('job_description');
    }

    public function jobDescriptionLength(): int
    {
        return mb_strlen($this->input('job_description', ''));
    }

    public function selectedResumeId(): ?int
    {
        $resumeId = $this->validated('resume_id');

        return $resumeId === null ? null : (int) $resumeId;
    }

    public function wantsCreditConsumption(): bool
    {
        return $this->boolean('use_credit');
    }

    public function selectedCreditId(): ?int
    {
        $creditId = $this->input('credit_id');

        return $creditId === null || $creditId === '' ? null : (int) $creditId;
    }

    public function jobDescriptionPreview(): string
    {
        return JobMatchAnalysis::maskJobDescriptionPreview($this->input('job_description', ''));
    }

    private function jobDescriptionMaxLength(): int
    {
        return max(500, (int) config('job-matching.job_description_max_length', 5000));
    }
}
