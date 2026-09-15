<?php

declare(strict_types=1);

namespace App\Services\Resume\Support;

use Illuminate\Support\Arr;

final class ResumeOptimizeResultParser
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function parseRawToModules(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $sections = [];
        $currentTitle = '个人信息';
        $currentType = 'personal';
        $buffer = [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if (preg_match('/^#{1,4}\s*(.+)$/u', $trimmed, $matches) === 1) {
                if ($buffer !== []) {
                    $sections[] = $this->buildSection($currentType, $currentTitle, $buffer);
                }
                $currentTitle = trim((string) $matches[1]);
                $currentType = $this->guessSectionType($currentTitle);
                $buffer = [];

                continue;
            }
            $buffer[] = (string) $line;
        }

        if ($buffer !== []) {
            $sections[] = $this->buildSection($currentType, $currentTitle, $buffer);
        }

        if ($sections === []) {
            return [[
                'type' => 'summary',
                'data' => ['title' => '自我评价', 'content' => trim($raw)],
                'sort_order' => 0,
            ]];
        }

        return array_values(array_map(static function (array $section, int $idx): array {
            $section['sort_order'] = $idx;

            return $section;
        }, $sections, array_keys($sections)));
    }

    /**
     * @param  array<int,string>  $buffer
     * @return array<string,mixed>
     */
    public function buildSection(string $type, string $title, array $buffer): array
    {
        $contentLines = [];
        $items = [];
        foreach ($buffer as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/^[-*]\s+(.+)$/u', $trimmed, $matches) === 1) {
                $items[] = trim((string) $matches[1]);

                continue;
            }
            $contentLines[] = $trimmed;
        }

        $data = [
            'title' => $title,
            'content' => implode("\n", $contentLines),
        ];
        if ($items !== []) {
            $data['items'] = $items;
        }

        if ($type === 'personal') {
            $data = [
                'name' => trim((string) ($contentLines[0] ?? '')),
                'phone' => '',
                'email' => '',
                'location' => '',
                'content' => implode("\n", $contentLines),
            ];
        }

        return [
            'type' => $type,
            'data' => $data,
        ];
    }

    public function guessSectionType(string $title): string
    {
        $map = [
            '个人' => 'personal',
            '求职' => 'objective',
            '目标' => 'objective',
            '教育' => 'education',
            '工作' => 'experience',
            '实习' => 'experience',
            '项目' => 'project',
            '技能' => 'skill',
            '证书' => 'certificate',
            '荣誉' => 'certificate',
            '评价' => 'summary',
            '总结' => 'summary',
        ];
        foreach ($map as $needle => $type) {
            if (str_contains($title, $needle)) {
                return $type;
            }
        }

        return 'summary';
    }

    public function moduleTypeTitle(string $type): string
    {
        return Arr::get([
            'objective' => '求职意向',
            'education' => '教育经历',
            'experience' => '实习经历',
            'project' => '项目经验',
            'skill' => '技能证书',
            'certificate' => '获奖情况',
            'summary' => '自我评价',
        ], $type, '内容模块');
    }
}
