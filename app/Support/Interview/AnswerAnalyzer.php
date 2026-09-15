<?php

declare(strict_types=1);

namespace App\Support\Interview;

final class AnswerAnalyzer
{
    public const NON_ANSWER_PATTERN = '/(不知道|不清楚|不会|不了解|没做过|没经验|想不起来|不太会)/u';

    private const FLUENCY_RISK_PATTERN = '/(然后然后|就是就是|那个那个|嗯嗯|啊啊|。。。|，，，|！！！|？？？|是否是|是不是是)/u';

    private const ACTION_VERB_PATTERN = '/(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成)/u';

    private const METRIC_PATTERN = '/(\d+%|\d+\+|[1-9]\d{1,}|提升|增长|降低|缩短|减少|节省)/u';

    public static function isNonAnswer(string $answer): bool
    {
        return preg_match(self::NON_ANSWER_PATTERN, $answer) === 1;
    }

    public static function hasFluencyRisk(string $answer): bool
    {
        if (preg_match(self::FLUENCY_RISK_PATTERN, $answer) === 1) {
            return true;
        }
        $fillerCount = preg_match_all('/(然后|就是|那个|嗯|啊)/u', $answer);
        $length = mb_strlen($answer);
        if ($length > 0 && $fillerCount / $length > 0.15) {
            return true;
        }
        $punctuationRuns = preg_match_all('/[。，！？]{3,}/u', $answer);

        return $punctuationRuns > 0;
    }

    public static function buildQualityMeta(string $answer): array
    {
        $answer = trim($answer);
        $length = mb_strlen($answer);
        $hasActionVerb = preg_match(self::ACTION_VERB_PATTERN, $answer) === 1;
        $hasMetric = preg_match(self::METRIC_PATTERN, $answer) === 1;
        $isNonAnswer = self::isNonAnswer($answer);
        $hasFluencyRisk = self::hasFluencyRisk($answer);

        return [
            'length' => $length,
            'has_action_verb' => $hasActionVerb,
            'has_metric' => $hasMetric,
            'is_non_answer' => $isNonAnswer,
            'has_fluency_risk' => $hasFluencyRisk,
            'quality_score' => self::computeQualityScore($length, $hasActionVerb, $hasMetric, $isNonAnswer, $hasFluencyRisk),
        ];
    }

    private static function computeQualityScore(int $length, bool $hasActionVerb, bool $hasMetric, bool $isNonAnswer, bool $hasFluencyRisk): int
    {
        if ($isNonAnswer) {
            return 1;
        }
        if ($hasFluencyRisk) {
            return 2;
        }
        $score = 3;
        if ($length >= 80) {
            $score++;
        }
        if ($length >= 200) {
            $score++;
        }
        if ($hasActionVerb) {
            $score++;
        }
        if ($hasMetric) {
            $score++;
        }

        return min(10, $score);
    }
}
