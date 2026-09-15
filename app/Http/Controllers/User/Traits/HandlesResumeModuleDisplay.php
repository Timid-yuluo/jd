<?php

declare(strict_types=1);

namespace App\Http\Controllers\User\Traits;

use App\Models\Resume;

trait HandlesResumeModuleDisplay
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildDefaultModules(Resume $resume): array
    {
        return $this->resumeModuleDisplayService->buildDefaultModules($resume);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildShowModules(Resume $resume): array
    {
        return $this->resumeModuleDisplayService->buildShowModules($resume);
    }

    /**
     * @param  array<int, array<string, mixed>>  $targetModules
     * @param  array<int, array<string, mixed>>  $dbModules
     * @return array<int, array<string, mixed>>
     */
    private function hydratePersonalAvatarFromDb(array $targetModules, array $dbModules): array
    {
        return $this->resumeModuleDisplayService->hydratePersonalAvatarFromDb($targetModules, $dbModules);
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     */
    private function calculateModulesRichness(array $modules): int
    {
        return $this->resumeModuleDisplayService->calculateModulesRichness($modules);
    }

    /**
     * @param  array<int, array<string, mixed>>  $dbModules
     * @param  array<int, array<string, mixed>>  $parsed
     * @return array<int, array<string, mixed>>
     */
    private function mergeModulesWithParsed(array $dbModules, array $parsed): array
    {
        return $this->resumeModuleDisplayService->mergeModulesWithParsed($dbModules, $parsed);
    }

    /**
     * @return array<int, string>
     */
    private function allowedModuleTypes(): array
    {
        return $this->resumeModuleDisplayService->allowedModuleTypes();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function emptyDefaultModules(Resume $resume): array
    {
        return $this->resumeModuleDisplayService->emptyDefaultModules($resume);
    }
}
