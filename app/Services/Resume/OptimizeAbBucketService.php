<?php

declare(strict_types=1);

namespace App\Services\Resume;

final class OptimizeAbBucketService
{
    public function isEnabled(): bool
    {
        return (bool) config('resume.optimize_session.ab.enabled', false);
    }

    public function resolveVariant(int $userId, int $resumeId): string
    {
        if (! $this->isEnabled()) {
            return 'baseline';
        }

        $ratios = config('resume.optimize_session.ab.ratio', []);
        $baselineRatio = max(0, min(100, (int) ($ratios['baseline'] ?? 50)));
        $authRatio = max(0, min(100, (int) ($ratios['authenticity_first'] ?? (100 - $baselineRatio))));
        $total = max(1, $baselineRatio + $authRatio);
        $salt = (string) config('resume.optimize_session.ab.salt', 'resume-optimize-v1');

        $hash = crc32(sprintf('%s:%d:%d', $salt, $userId, $resumeId));
        $slot = $hash % $total;

        return $slot < $baselineRatio ? 'baseline' : 'authenticity_first';
    }
}
