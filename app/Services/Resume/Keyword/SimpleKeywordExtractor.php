<?php

declare(strict_types=1);

namespace App\Services\Resume\Keyword;

final class SimpleKeywordExtractor implements KeywordExtractorInterface
{
    public function __construct(
        private readonly KeywordNormalizer $normalizer,
    ) {}

    /**
     * @return array<int,string>
     */
    public function extract(string $text, string $title = '', int $maxCount = 20): array
    {
        $source = mb_strtolower($text);
        if (trim($source) === '') {
            return [];
        }
        $titleText = mb_strtolower($title);

        $stopwords = array_flip([
            '负责', '进行', '以及', '相关', '优先', '经验', '能力', '岗位', '公司', '工作', '描述', '要求',
            '熟悉', '掌握', '能够', '具备', '以上', '以下', '我们', '你将', '如果', '作为', '参与', '完成',
            'and', 'the', 'for', 'with', 'from', 'that', 'this', 'you', 'will', 'have',
        ]);
        $weights = [
            'laravel' => 3.2,
            'php' => 2.8,
            'mysql' => 2.6,
            'redis' => 2.6,
            'docker' => 2.4,
            'kubernetes' => 2.5,
            'java' => 2.3,
            'spring' => 2.3,
            'vue' => 2.2,
            'react' => 2.2,
            'python' => 2.2,
            'golang' => 2.2,
            'linux' => 2.1,
            'kafka' => 2.1,
            'rabbitmq' => 2.0,
            'elasticsearch' => 2.1,
            '微服务' => 2.4,
            '高并发' => 2.4,
            'rest' => 1.8,
            'api' => 1.8,
            'ats' => 2.0,
        ];

        preg_match_all('/[\p{Han}]{2,8}|[a-z0-9][a-z0-9\+\#\.\-]{1,30}/u', $source, $matches);
        $tokens = is_array($matches[0] ?? null) ? $matches[0] : [];
        $scores = [];
        foreach ($tokens as $token) {
            $normalized = $this->normalizer->normalize((string) $token);
            if ($normalized === '' || isset($stopwords[$normalized])) {
                continue;
            }
            $baseWeight = (float) ($weights[$normalized] ?? 1.0);
            $titleBoost = str_contains($titleText, $normalized) ? 1.4 : 1.0;
            $scores[$normalized] = ($scores[$normalized] ?? 0.0) + ($baseWeight * $titleBoost);
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, max(1, $maxCount));
    }
}
