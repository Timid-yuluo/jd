<?php

declare(strict_types=1);

namespace App\Services\Interview;

use App\Models\Resume;
use Illuminate\Support\Str;

final class InterviewResumeContextBuilder
{
    /**
     * @return array<int, string>
     */
    public function extractResumeAnchors(?Resume $resume): array
    {
        if ($resume === null) {
            return [];
        }

        $anchors = [];
        $highlights = is_array($resume->highlights) ? $resume->highlights : [];
        foreach ($highlights as $item) {
            if (! is_string($item)) {
                continue;
            }
            $text = trim($item);
            if ($text === '' || mb_strlen($text) < 6) {
                continue;
            }
            $anchors[] = Str::limit($text, 48, '...');
            if (count($anchors) >= 4) {
                break;
            }
        }

        $resume->loadMissing('modules');
        foreach ($resume->modules as $module) {
            if (count($anchors) >= 6) {
                break;
            }

            $data = is_array($module->data) ? $module->data : [];
            foreach ($data as $value) {
                if (! is_string($value)) {
                    continue;
                }
                $text = trim(strip_tags($value));
                if ($text === '' || mb_strlen($text) < 8) {
                    continue;
                }
                $anchors[] = Str::limit($text, 48, '...');
                if (count($anchors) >= 6) {
                    break;
                }
            }
        }

        return array_values(array_unique($anchors));
    }

    public function buildResumeExcerpt(?Resume $resume): string
    {
        if ($resume === null) {
            return '';
        }

        $source = trim((string) ($resume->optimized_text ?: $resume->content_raw ?: ''));
        if ($source === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', $source);
        $maxChars = max(300, (int) config('interview.resume_excerpt_chars', 1200));

        return Str::limit((string) $normalized, $maxChars, '...');
    }

    /**
     * @return array<int, string>
     */
    public function extractFocusPoints(?Resume $resume): array
    {
        if ($resume === null) {
            return [];
        }

        $raw = trim((string) ($resume->optimized_text ?: $resume->content_raw ?: ''));
        if ($raw === '') {
            return [];
        }

        $lines = preg_split('/[\r\n]+/u', $raw) ?: [];
        $points = [];
        foreach ($lines as $line) {
            $text = trim((string) $line, " \t\n\r\0\x0B-•*");
            if ($text === '' || mb_strlen($text) < 8) {
                continue;
            }
            $points[] = Str::limit($text, 60, '...');
            if (count($points) >= 6) {
                break;
            }
        }

        return $points;
    }

    /**
     * @return array{level:string,summary:string,has_experience:bool,has_project:bool}
     */
    public function buildResumeExperienceSignal(?Resume $resume): array
    {
        if ($resume === null) {
            return [
                'level' => 'empty',
                'summary' => '未提供简历经验信号',
                'has_experience' => false,
                'has_project' => false,
            ];
        }

        $resume->loadMissing('modules');
        $hasExperience = false;
        $hasProject = false;
        foreach ($resume->modules as $module) {
            $type = (string) ($module->type ?? '');
            $data = is_array($module->data) ? $module->data : [];
            if ($type === 'experience' && $this->moduleHasMeaningfulContent($data)) {
                $hasExperience = true;
            }
            if ($type === 'project' && $this->moduleHasMeaningfulContent($data)) {
                $hasProject = true;
            }
        }

        $level = match (true) {
            $hasExperience && $hasProject => 'experience_and_project',
            $hasExperience => 'experience_only',
            $hasProject => 'project_only',
            default => 'low_signal',
        };

        $summary = match ($level) {
            'experience_and_project' => '已检测到实习/工作与项目经历，可适度提高案例深度',
            'experience_only' => '检测到实习/工作经历，可优先围绕真实业务场景追问',
            'project_only' => '以项目经历为主，建议先问项目实践与学习路径',
            default => '经验信号较少，建议优先基础友好型问题',
        };

        return [
            'level' => $level,
            'summary' => $summary,
            'has_experience' => $hasExperience,
            'has_project' => $hasProject,
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function moduleHasMeaningfulContent(array $data): bool
    {
        $content = trim((string) ($data['content'] ?? ''));
        if ($content !== '' && mb_strlen($content) >= 10) {
            return true;
        }
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        foreach ($items as $item) {
            if (is_string($item) && mb_strlen(trim($item)) >= 10) {
                return true;
            }
        }

        return false;
    }
}
