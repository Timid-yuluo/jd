<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 批量忽略推荐请求（#11, #12）
 *
 * 关联路由：POST /user/recommendations/batch-dismiss
 * 校验：ids 数组上限 50、必须为整数、且必须属于当前用户
 */
final class BatchDismissRecommendationsRequest extends FormRequest
{
    private const MAX_BATCH = 50;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 校验规则
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_BATCH],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 自定义错误消息
     */
    public function messages(): array
    {
        return [
            'ids.required' => '请至少选择一项推荐',
            'ids.array' => '推荐 ID 格式不正确',
            'ids.min' => '请至少选择一项推荐',
            'ids.max' => '一次最多只能忽略 ' . self::MAX_BATCH . ' 项推荐',
            'ids.*.integer' => '推荐 ID 必须为整数',
            'ids.*.min' => '推荐 ID 必须大于 0',
        ];
    }
}
