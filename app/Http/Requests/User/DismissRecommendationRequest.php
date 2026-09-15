<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 忽略单条推荐请求（#18）
 *
 * 关联路由：POST /user/recommendations/{recommendation}/dismiss
 * 校验：reason 必须在枚举内，避免脏数据写入埋点
 */
final class DismissRecommendationRequest extends FormRequest
{
    /**
     * 允许的忽略原因枚举
     */
    public const REASON_SALARY = 'salary';
    public const REASON_LOCATION = 'location';
    public const REASON_INDUSTRY = 'industry';
    public const REASON_SENIORITY = 'seniority';
    public const REASON_OTHER = 'other';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // #18 reason 枚举校验，防止任意字符串入库
            'reason' => ['nullable', 'string', 'in:salary,location,industry,seniority,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => '忽略原因不合法，请从下拉列表选择',
        ];
    }
}
