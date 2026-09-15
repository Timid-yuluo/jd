<?php

declare(strict_types=1);

namespace App\Services\Resume\Keyword;

final class KeywordNormalizer
{
    public function __construct(
        private readonly KeywordSynonymDictionary $synonymDictionary,
    ) {}

    public function normalize(string $keyword): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $keyword) ?? ''));
        if ($normalized === '') {
            return '';
        }

        $map = $this->synonymDictionary->map();

        return $map[$normalized] ?? $normalized;
    }
}
