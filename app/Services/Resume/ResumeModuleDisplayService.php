<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use App\Services\Resume\Parsing\ResumeModuleParserService;

final class ResumeModuleDisplayService
{
    public function __construct(
        private readonly ResumeModuleParserService $moduleParserService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildDefaultModules(Resume $resume): array
    {
        $modules = $resume->modules;

        if ($modules->count() === 1
            && $modules->first()->type === 'personal'
            && filled($resume->content_raw)
        ) {
            $firstData = $modules->first()->data ?? [];
            $hasRealPersonal = filled($firstData['name'] ?? '')
                || filled($firstData['phone'] ?? '')
                || filled($firstData['email'] ?? '');
            if (! $hasRealPersonal) {
                $parsed = $this->moduleParserService->parseRawToModules((string) $resume->content_raw);
                if (! empty($parsed)) {
                    return $parsed;
                }
            }
        }

        if ($modules->isNotEmpty()) {
            return $modules->toArray();
        }

        $parsed = $this->moduleParserService->parseRawToModules((string) $resume->content_raw);

        if (! empty($parsed)) {
            return $parsed;
        }

        return $this->emptyDefaultModules($resume);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildShowModules(Resume $resume): array
    {
        $dbModules = $this->buildDefaultModules($resume);

        if (! filled($resume->content_raw)) {
            return $dbModules;
        }

        $parsed = $this->moduleParserService->parseRawToModules((string) $resume->content_raw);
        if (empty($parsed)) {
            return $dbModules;
        }

        $parsedRichness = $this->calculateModulesRichness($parsed);
        $dbRichness = $this->calculateModulesRichness($dbModules);

        if ($parsedRichness > $dbRichness * 1.2) {
            return $this->hydratePersonalAvatarFromDb($parsed, $dbModules);
        }

        return $this->hydratePersonalAvatarFromDb(
            $this->mergeModulesWithParsed($dbModules, $parsed),
            $dbModules
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $targetModules
     * @param  array<int, array<string, mixed>>  $dbModules
     * @return array<int, array<string, mixed>>
     */
    public function hydratePersonalAvatarFromDb(array $targetModules, array $dbModules): array
    {
        $avatar = '';
        foreach ($dbModules as $module) {
            if (($module['type'] ?? '') !== 'personal') {
                continue;
            }
            $candidate = trim((string) ($module['data']['avatar'] ?? ''));
            if ($candidate !== '') {
                $avatar = $candidate;
                break;
            }
        }

        if ($avatar === '') {
            return $targetModules;
        }

        foreach ($targetModules as $index => $module) {
            if (($module['type'] ?? '') !== 'personal') {
                continue;
            }
            $current = trim((string) ($module['data']['avatar'] ?? ''));
            if ($current === '') {
                $targetModules[$index]['data']['avatar'] = $avatar;
            }
            break;
        }

        return $targetModules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     */
    public function calculateModulesRichness(array $modules): int
    {
        $score = 0;
        foreach ($modules as $mod) {
            $data = $mod['data'] ?? [];
            $items = array_filter($data['items'] ?? [], fn ($i) => trim((string) $i) !== '');
            $score += count($items) * 10;
            $content = trim((string) ($data['content'] ?? ''));
            if ($content !== '') {
                $score += min(strlen($content), 200);
            }
            if (filled($data['subtitle'] ?? '')) {
                $score += 3;
            }
            if (filled($data['name'] ?? '')) {
                $score += 5;
            }
            if (filled($data['phone'] ?? '')) {
                $score += 3;
            }
            if (filled($data['email'] ?? '')) {
                $score += 3;
            }
            if (filled($data['target_job'] ?? '')) {
                $score += 4;
            }
        }

        return $score;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dbModules
     * @param  array<int, array<string, mixed>>  $parsed
     * @return array<int, array<string, mixed>>
     */
    public function mergeModulesWithParsed(array $dbModules, array $parsed): array
    {
        $parsedByType = [];
        foreach ($parsed as $p) {
            $type = $p['type'];
            $parsedByType[$type] = $parsedByType[$type] ?? [];
            $parsedByType[$type][] = $p['data'];
        }

        $usedCount = [];

        foreach ($dbModules as $idx => $mod) {
            $type = $mod['type'] ?? '';
            $dbData = $mod['data'] ?? [];
            if (! isset($parsedByType[$type])) {
                continue;
            }

            $usedCount[$type] = $usedCount[$type] ?? 0;
            $parsedIdx = $usedCount[$type];
            if (! isset($parsedByType[$type][$parsedIdx])) {
                continue;
            }
            $parsedData = $parsedByType[$type][$parsedIdx];
            $usedCount[$type]++;

            $merged = $dbData;
            foreach ($parsedData as $key => $val) {
                $currentVal = $dbData[$key] ?? null;

                if ($key === 'items' && is_array($val) && is_array($currentVal)) {
                    $hasRealItems = ! empty(array_filter($currentVal, fn ($i) => trim((string) $i) !== ''));
                    $hasParsedItems = ! empty(array_filter($val, fn ($i) => trim((string) $i) !== ''));
                    if ($hasParsedItems && ! $hasRealItems) {
                        $merged[$key] = $val;
                    } elseif ($hasParsedItems && $hasRealItems) {
                        $merged[$key] = count($val) > count($currentVal) ? $val : $currentVal;
                    }

                    continue;
                }

                if ($key === 'content') {
                    $pc = trim((string) $val);
                    $cc = trim((string) $currentVal);
                    if ($pc !== '' && $cc === '') {
                        $merged[$key] = $val;
                    } elseif ($pc !== '' && $cc !== '') {
                        $merged[$key] = strlen($pc) > strlen($cc) ? $val : $currentVal;
                    }

                    continue;
                }

                if (empty($currentVal) || (is_string($currentVal) && trim($currentVal) === '')) {
                    $merged[$key] = $val;
                }
            }

            $dbModules[$idx]['data'] = $merged;
        }

        return $dbModules;
    }

    /**
     * @return array<int, string>
     */
    public function allowedModuleTypes(): array
    {
        return ['personal', 'objective', 'education', 'experience', 'project', 'skill', 'certificate', 'summary'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function emptyDefaultModules(Resume $resume): array
    {
        return [
            ['type' => 'personal', 'data' => ['name' => '', 'phone' => '', 'email' => '', 'location' => '', 'avatar' => ''], 'sort_order' => 0],
            ['type' => 'objective', 'data' => ['target_job' => (string) ($resume->target_job ?? ''), 'content' => ''], 'sort_order' => 1],
            ['type' => 'experience', 'data' => ['title' => '实习经历', 'subtitle' => '', 'date' => '', 'location' => '', 'items' => []], 'sort_order' => 2],
            ['type' => 'education', 'data' => ['title' => '教育经历', 'subtitle' => '', 'date' => '', 'location' => '', 'items' => []], 'sort_order' => 3],
            ['type' => 'skill', 'data' => ['title' => '技能证书', 'items' => []], 'sort_order' => 4],
        ];
    }
}
