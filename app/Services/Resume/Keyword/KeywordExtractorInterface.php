<?php

declare(strict_types=1);

namespace App\Services\Resume\Keyword;

interface KeywordExtractorInterface
{
    /**
     * @return array<int,string>
     */
    public function extract(string $text, string $title = '', int $maxCount = 20): array;
}
