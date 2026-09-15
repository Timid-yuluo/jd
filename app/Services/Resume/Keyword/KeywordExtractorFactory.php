<?php

declare(strict_types=1);

namespace App\Services\Resume\Keyword;

final class KeywordExtractorFactory
{
    public function __construct(
        private readonly SimpleKeywordExtractor $simpleExtractor,
    ) {}

    public function make(?string $driver = null): KeywordExtractorInterface
    {
        $resolved = trim((string) ($driver ?? config('resume.keyword_extractor.driver', 'simple')));

        return match ($resolved) {
            'simple', 'jieba' => $this->simpleExtractor,
            default => $this->simpleExtractor,
        };
    }
}
