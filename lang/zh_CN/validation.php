<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | 验证语言行
    |--------------------------------------------------------------------------
    |
    | 以下语言行包含验证器类使用的默认错误消息。
    |
    */

    'accepted' => ':attribute 必须接受。',
    'active_url' => ':attribute 不是一个有效的网址。',
    'after' => ':attribute 必须是一个在 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是一个等于或晚于 :date 的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、破折号和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是一个数组。',
    'before' => ':attribute 必须是一个在 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是一个等于或早于 :date 的日期。',
    'between' => [
        'array' => ':attribute 必须有 :min 到 :max 个元素。',
        'file' => ':attribute 必须在 :min 到 :max KB 之间。',
        'numeric' => ':attribute 必须在 :min 到 :max 之间。',
        'string' => ':attribute 必须在 :min 到 :max 个字符之间。',
    ],
    'boolean' => ':attribute 必须为 true 或 false。',
    'confirmed' => ':attribute 两次输入不一致。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 不是一个有效的日期。',
    'date_equals' => ':attribute 必须等于 :date。',
    'date_format' => ':attribute 的格式必须为 :format。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须在 :min 到 :max 位数字之间。',
    'dimensions' => ':attribute 图片尺寸不正确。',
    'distinct' => ':attribute 字段有重复值。',
    'email' => ':attribute 必须是一个有效的邮箱地址。',
    'ends_with' => ':attribute 必须以 :values 结尾。',
    'exists' => ':attribute 不存在。',
    'file' => ':attribute 必须是一个文件。',
    'filled' => ':attribute 不能为空。',
    'gt' => [
        'array' => ':attribute 必须超过 :value 个元素。',
        'file' => ':attribute 必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须不少于 :value 个元素。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 必须多于或等于 :value 个字符。',
    ],
    'image' => ':attribute 必须是图片。',
    'in' => ':attribute 的值无效。',
    'in_array' => ':attribute 必须在 :other 中存在。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'lt' => [
        'array' => ':attribute 必须少于 :value 个元素。',
        'file' => ':attribute 必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 必须少于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 必须不多于 :value 个元素。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'numeric' => ':attribute 必须小于或等于 :value。',
        'string' => ':attribute 必须少于或等于 :value 个字符。',
    ],
    'max' => [
        'array' => ':attribute 不能超过 :max 个元素。',
        'file' => ':attribute 不能超过 :max KB。',
        'numeric' => ':attribute 不能超过 :max。',
        'string' => ':attribute 不能超过 :max 个字符。',
    ],
    'mimes' => ':attribute 必须是 :values 类型的文件。',
    'mimetypes' => ':attribute 必须是 :values 类型的文件。',
    'min' => [
        'array' => ':attribute 至少要有 :min 个元素。',
        'file' => ':attribute 至少要 :min KB。',
        'numeric' => ':attribute 至少要 :min。',
        'string' => ':attribute 至少要 :min 个字符。',
    ],
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => ':attribute 的值无效。',
    'not_regex' => ':attribute 的格式不正确。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'mixed' => ':attribute 必须包含至少一个大写字母和一个小写字母。',
        'letters' => ':attribute 必须包含至少一个字母。',
        'symbols' => ':attribute 必须包含至少一个特殊符号。',
        'numbers' => ':attribute 必须包含至少一个数字。',
        'uncompromised' => ':attribute 已出现在数据泄露中，请更换其他密码。',
        'min' => [
            'string' => ':attribute 至少需要 :min 个字符。',
        ],
    ],
    'present' => ':attribute 必须存在。',
    'prohibited' => ':attribute 字段被禁止。',
    'prohibited_if' => '当 :other 为 :value 时，:attribute 字段被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中，否则 :attribute 字段被禁止。',
    'regex' => ':attribute 的格式不正确。',
    'required' => ':attribute 不能为空。',
    'required_if' => '当 :other 为 :value 时，:attribute 不能为空。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 不能为空。',
    'required_with' => '当 :values 存在时，:attribute 不能为空。',
    'required_with_all' => '当 :values 存在时，:attribute 不能为空。',
    'required_without' => '当 :values 不存在时，:attribute 不能为空。',
    'required_without_all' => '当 :values 都不存在时，:attribute 不能为空。',
    'same' => ':attribute 和 :other 必须相同。',
    'size' => [
        'array' => ':attribute 必须包含 :size 个元素。',
        'file' => ':attribute 必须为 :size KB。',
        'numeric' => ':attribute 必须为 :size。',
        'string' => ':attribute 必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以 :values 开头。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'unique' => ':attribute 已经存在。',
    'uploaded' => ':attribute 上传失败。',
    'url' => ':attribute 必须是有效的网址。',
    'uuid' => ':attribute 必须是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | 自定义验证语言行
    |--------------------------------------------------------------------------
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => '自定义消息',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 字段名称
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name' => '姓名',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirmation' => '确认密码',
        'phone' => '手机号',
        'school' => '学校',
        'major' => '专业',
    ],

];
