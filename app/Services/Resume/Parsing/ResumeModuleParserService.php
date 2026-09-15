<?php

declare(strict_types=1);

namespace App\Services\Resume\Parsing;

final class ResumeModuleParserService
{
    private ?ResumeSectionSplitter $sectionSplitter = null;

    private ?ResumeEntryParser $entryParser = null;

    private ?ResumeEducationParser $educationParser = null;

    private function sectionSplitter(): ResumeSectionSplitter
    {
        return $this->sectionSplitter ??= app(ResumeSectionSplitter::class);
    }

    private function entryParser(): ResumeEntryParser
    {
        return $this->entryParser ??= app(ResumeEntryParser::class);
    }

    private function educationParser(): ResumeEducationParser
    {
        return $this->educationParser ??= app(ResumeEducationParser::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseRawToModules(string $raw): array
    {
        $lines = array_map('rtrim', explode("\n", $raw));
        $sections = $this->sectionSplitter()->split($lines);

        if (empty($sections)) {
            return [];
        }

        $modules = [];
        $sortOrder = 0;
        foreach ($sections as $section) {
            $type = $section['type'];
            $data = ['title' => $section['title']];

            switch ($type) {
                case 'personal':
                    $data = array_merge($data, $this->parsePersonalSection($section['lines']));
                    $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    break;
                case 'objective':
                    $data = array_merge($data, $this->parseObjectiveSection($section['lines'], $section['title']));
                    $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    break;
                case 'skill':
                    $data['items'] = $this->entryParser()->extractListItems($section['lines']);
                    $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    break;
                case 'summary':
                    $data['content'] = implode("\n", array_map('trim', $section['lines']));
                    $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    break;
                case 'experience':
                case 'education':
                case 'project':
                case 'certificate':
                    $entryChunks = $this->entryParser()->splitIntoEntryChunks($section['lines'], $type);
                    if ($entryChunks === []) {
                        $entryChunks = [$section['lines']];
                    }
                    foreach ($entryChunks as $chunk) {
                        $experienceResult = $this->entryParser()->parseExperienceLike($chunk);
                        $entry = $type === 'education'
                            ? array_merge($data, $this->educationParser()->parseEntry($chunk, $experienceResult))
                            : array_merge($data, $experienceResult);
                        if ($this->entryParser()->isEmptyExperienceLike($entry)) {
                            continue;
                        }
                        $modules[] = ['type' => $type, 'data' => $entry, 'sort_order' => $sortOrder++];
                    }
                    if ($type === 'certificate' && empty($entryChunks)) {
                        $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    }
                    break;
                default:
                    $data['content'] = implode("\n", array_map('trim', $section['lines']));
                    $modules[] = ['type' => $type, 'data' => $data, 'sort_order' => $sortOrder++];
                    break;
            }
        }

        return $modules;
    }

    /**
     * @return array{name:string,phone:string,email:string,location:string,content:string}
     */
    public function parsePersonalSection(array $lines): array
    {
        $name = '';
        $phone = '';
        $email = '';
        $location = '';
        $contentLines = [];

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }

            if ($name === '' && preg_match('/(?:姓名|Name)[:：]\s*([^\s|\/,，]+)/ui', $t, $m)) {
                $name = trim($m[1]);
                $t = trim(str_replace($m[0], '', $t));
            }

            if ($phone === '' && preg_match('/(?:电话|手机|Tel|Phone)?\s*[:：]?\s*(1[3-9]\d{9}|\d{3,4}-\d{7,8})/ui', $t, $m)) {
                $phone = $m[1];
                $t = trim(str_replace($m[0], '', $t));
            }

            if ($email === '' && preg_match('/(?:邮箱|Email|E-mail|邮件)?\s*[:：]?\s*([\w.\-+]+@[\w.-]+\.\w+)/ui', $t, $m)) {
                $email = trim($m[1]);
                $t = trim(str_replace($m[0], '', $t));
            }

            if ($location === '' && preg_match('/(?:城市|所在地|现居地|地址|Location)[:：]\s*([^|,，]+)/ui', $t, $m)) {
                $location = trim($m[1]);
                $t = trim(str_replace($m[0], '', $t));
            }

            $t = trim((string) preg_replace('/(?:\s*[\|\/,，·]\s*)+/u', ' ', $t), " \t\n\r\0\x0B-");

            if ($name === '' && mb_strlen($t) <= 12 && ! preg_match('/[\d@]/u', $t) && ! preg_match('/(?:电话|手机|邮箱|地址|城市|微信|求职|意向|岗位)/u', $t)) {
                $name = $t;

                continue;
            }

            if ($t !== '') {
                $contentLines[] = $t;
            }
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'location' => $location,
            'content' => implode("\n", $contentLines),
        ];
    }

    /**
     * @return array{target_job:string,content:string}
     */
    public function parseObjectiveSection(array $lines, string $title = ''): array
    {
        $targetJob = '';
        $contentLines = [];

        if ($title !== '' && preg_match('/(?:求职意向|目标岗位|应聘岗位|期望岗位|意向岗位)[：:]\s*(.+)/ui', $title, $m)) {
            $targetJob = trim($m[1]);
        }

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }

            if ($targetJob === '' && preg_match('/(?:目标岗位|应聘岗位|期望岗位|求职意向|意向岗位)[：:]\s*(.+)/ui', $t, $m)) {
                $targetJob = trim($m[1]);

                continue;
            }

            $contentLines[] = $line;
        }

        if ($targetJob === '' && ! empty($contentLines)) {
            $targetJob = trim($contentLines[0]);
            array_shift($contentLines);
        }

        return [
            'target_job' => $targetJob,
            'content' => implode("\n", $contentLines),
        ];
    }
}
