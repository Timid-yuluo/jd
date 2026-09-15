<?php

declare(strict_types=1);

namespace App\Enums;

final class Interview
{
    public const PROFILE_FRESH_GRAD = 'fresh_graduate';
    public const PROFILE_NO_EXPERIENCE = 'no_experience';
    public const PROFILE_JUNIOR = 'junior';
    public const PROFILE_EXPERIENCED = 'experienced';

    public const FRAMEWORK_STAR = 'STAR 法则';
    public const FRAMEWORK_PYRAMID = '金字塔原理';
    public const FRAMEWORK_CARL = 'CARL（情境-行动-结果-学习）';
    public const FRAMEWORK_IDEAL = 'IDEAL（识别-定义-探索-行动-回顾）';
    public const FRAMEWORK_KUSS = 'K.U.S.S（保持-改进-停止-开始）';
    public const FRAMEWORK_70_20_10 = '70-20-10法则';
    public const FRAMEWORK_DOUBLE_DIAMOND = '双钻模型';
    public const FRAMEWORK_ELEVATOR = '电梯演讲';

    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';

    public const MODE_TEXT = 'text';
    public const MODE_VOICE = 'voice';

    public const LANG_ZH = 'zh';
    public const LANG_EN = 'en';

    public const BEGINNER_PROFILES = [self::PROFILE_FRESH_GRAD, self::PROFILE_NO_EXPERIENCE];

    /**
     * @return array<int,string>
     */
    public static function supportedTypes(): array
    {
        return (array) config('interview.supported_types', ['mixed']);
    }
}
