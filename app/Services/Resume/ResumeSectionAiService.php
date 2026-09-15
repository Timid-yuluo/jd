<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Enums\AiCacheScenario;
use App\Infrastructure\AI\AiManager;

final class ResumeSectionAiService
{
    public function __construct(
        private readonly AiManager $aiManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function optimizeSection(string $sectionType, string $content, string $targetJob = ''): array
    {
        $cacheKey = 'ai:optsec:'.hash('sha256', $sectionType.$content.$targetJob);

        return $this->aiManager->withCacheFor($cacheKey, function ($provider) use ($sectionType, $content, $targetJob) {
            return $provider->optimizeSection($sectionType, $content, $targetJob);
        }, AiCacheScenario::Heavy);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateSection(string $sectionType, string $brief, string $targetJob = ''): array
    {
        $cacheKey = 'ai:gensec:'.hash('sha256', $sectionType.$brief.$targetJob);

        return $this->aiManager->withCacheFor($cacheKey, function ($provider) use ($sectionType, $brief, $targetJob) {
            return $provider->generateSection($sectionType, $brief, $targetJob);
        }, AiCacheScenario::Heavy);
    }
}
