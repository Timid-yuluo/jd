<?php

declare(strict_types=1);

namespace App\Services\Resume\Parsing;

final class ResumeEducationParser
{
    /**
     * @param  array<int,string>  $lines
     * @return array{subtitle:string,date:string,location:string,items:array<int,string>,content:string,school:string,major:string,degree:string}
     */
    public function parseEntry(array $lines, array $experienceLikeResult): array
    {
        $parsed = $experienceLikeResult;
        $school = '';
        $major = '';
        $degree = '';
        $contentLines = [];

        $allLines = [];
        if (trim((string) ($parsed['subtitle'] ?? '')) !== '') {
            $allLines[] = (string) $parsed['subtitle'];
        }
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $allLines[] = $line;
            }
        }

        foreach ($allLines as $line) {
            [$lineSchool, $lineMajor, $lineDegree, $cleanLine] = $this->extractMetaFromLine($line);
            if ($school === '' && $lineSchool !== '') {
                $school = $lineSchool;
            }
            if ($major === '' && $lineMajor !== '') {
                $major = $lineMajor;
            }
            if ($degree === '' && $lineDegree !== '') {
                $degree = $lineDegree;
            }
            if ($cleanLine !== '' && ! $this->isDateLikePart($cleanLine)) {
                $contentLines[] = $cleanLine;
            }
        }

        $subtitle = trim((string) ($parsed['subtitle'] ?? ''));
        if ($school !== '' || $major !== '') {
            $subtitle = implode(' / ', array_filter([$school, $major], static fn (string $v): bool => $v !== ''));
        }

        $baseContent = trim((string) ($parsed['content'] ?? ''));
        $mergedContentLines = [];
        if ($baseContent !== '') {
            $mergedContentLines = array_merge($mergedContentLines, array_map('trim', explode("\n", $baseContent)));
        }
        if ($degree !== '') {
            $mergedContentLines[] = '学历：'.$degree;
        }
        $mergedContentLines = array_merge($mergedContentLines, $contentLines);
        $mergedContentLines = array_values(array_filter(array_unique($mergedContentLines), static fn (string $line): bool => trim($line) !== ''));

        $parsed['subtitle'] = $subtitle;
        $parsed['content'] = implode("\n", $mergedContentLines);
        $parsed['school'] = $school;
        $parsed['major'] = $major;
        $parsed['degree'] = $degree;

        return $parsed;
    }

    /**
     * @return array{0:string,1:string,2:string,3:string}
     */
    public function extractMetaFromLine(string $line): array
    {
        $raw = trim($line);
        if ($raw === '') {
            return ['', '', '', ''];
        }

        $school = '';
        $major = '';
        $degree = '';
        $clean = (string) preg_replace('/\((?:19|20)\d{2}[^\)]*\)/u', '', $raw);
        $clean = trim((string) preg_replace('/((?:19|20)\d{2}([\.\/-]\d{1,2})?\s*[~-]\s*(?:(?:19|20)\d{2}([\.\/-]\d{1,2})?|至今|现在|Present|Now))/iu', '', $clean));
        $clean = trim((string) preg_replace('/\b(GPA|绩点)\s*[:：]?\s*\d+(?:\.\d+)?/iu', '', $clean));
        $parts = preg_split('/\s*[\|｜\/·•,，]\s*/u', $clean) ?: [$clean];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));

        foreach ($parts as $part) {
            if ($school === '' && preg_match($this->schoolRegex(), $part, $m)) {
                $school = $this->normalizeByAlias(trim((string) $m[1]), $this->schoolAliases());

                continue;
            }

            if ($school === '') {
                $schoolAlias = $this->resolveAliasFromText($part, $this->schoolAliases());
                if ($schoolAlias !== '') {
                    $school = $schoolAlias;

                    continue;
                }

                continue;
            }

            if ($degree === '' && preg_match('/(博士研究生|硕士研究生|本科|硕士|博士|研究生|专科|大专|MBA|EMBA|Bachelor|Master|Ph\.?D)/iu', $part, $m)) {
                $degree = $this->normalizeDegree((string) $m[1]);

                continue;
            }

            if ($major === '') {
                $majorAlias = $this->resolveAliasFromText($part, $this->majorAliases());
                if ($majorAlias !== '') {
                    $major = $majorAlias;

                    continue;
                }
            }

            if ($major === '' && $this->looksLikeMajorPart($part)) {
                $major = $this->normalizeByAlias($part, $this->majorAliases());
            }
        }

        if ($school === '' && preg_match($this->schoolRegex(), $clean, $m)) {
            $school = $this->normalizeByAlias(trim((string) $m[1]), $this->schoolAliases());
            $clean = trim(str_replace((string) $m[1], '', $clean));
        }

        if ($degree === '' && preg_match('/(博士研究生|硕士研究生|本科|硕士|博士|研究生|专科|大专|MBA|EMBA|Bachelor|Master|Ph\.?D)/iu', $clean, $m)) {
            $degree = $this->normalizeDegree((string) $m[1]);
            $clean = trim(str_replace((string) $m[1], '', $clean));
        }

        if ($major === '' && preg_match('/([^\|\/·•,\s]{2,}(?:专业|工程|科学|设计|管理|语言|技术|法律|经济|金融|数学|统计|物理|化学|生物|医学))/u', $clean, $m)) {
            $major = $this->normalizeByAlias(trim((string) $m[1]), $this->majorAliases());
            $clean = trim(str_replace((string) $m[1], '', $clean));
        }

        if ($major === '' && $school !== '') {
            foreach ($parts as $part) {
                if ($part === $school || $this->isDateLikePart($part)) {
                    continue;
                }
                if (mb_strlen($part) >= 2 && mb_strlen($part) <= 24 && ! $this->matchesKeyword($part, $this->schoolKeywords())) {
                    $major = $this->normalizeByAlias($part, $this->majorAliases());
                    break;
                }
            }
        }

        $clean = trim((string) preg_replace('/\s*[\|\/·•,，]\s*/u', ' ', $clean));
        $clean = trim((string) preg_replace('/\s{2,}/u', ' ', $clean));
        $clean = trim((string) preg_replace('/(?:学历|学位|专业)\s*[:：]?\s*/u', '', $clean));
        if ($school !== '') {
            $clean = trim(str_replace($school, '', $clean));
        }
        if ($major !== '') {
            $clean = trim(str_replace($major, '', $clean));
        }
        if ($degree !== '') {
            $clean = trim(str_replace($degree, '', $clean));
        }
        $clean = trim((string) preg_replace('/\s{2,}/u', ' ', $clean));

        return [$school, $major, $degree, $clean];
    }

    public function looksLikeMajorPart(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) < 2 || mb_strlen($value) > 36) {
            return false;
        }

        if ($this->isDateLikePart($value)) {
            return false;
        }

        if ($this->matchesKeyword($value, $this->schoolKeywords())) {
            return false;
        }

        if ($this->matchesKeyword($value, $this->majorKeywords())) {
            return true;
        }

        return preg_match('/[A-Za-z]{3,}\s*(Engineering|Science|Technology|Management|Economics|Finance|Law|Design|Business|Computer|Data|Software|Network)/i', $value) === 1;
    }

    public function normalizeDegree(string $degree): string
    {
        $value = strtolower(trim($degree));
        $degreeMap = config('resume.import_parser.education_degree_map', []);
        if (is_array($degreeMap)) {
            foreach ($degreeMap as $keyword => $normalized) {
                if (! is_string($keyword) || ! is_string($normalized) || trim($keyword) === '' || trim($normalized) === '') {
                    continue;
                }
                if (str_contains($value, strtolower(trim($keyword)))) {
                    return trim($normalized);
                }
            }
        }

        return trim($degree);
    }

    /**
     * @return array<int, string>
     */
    public function schoolKeywords(): array
    {
        $configured = config('resume.import_parser.education_school_suffixes', []);
        if (! is_array($configured) || $configured === []) {
            return ['大学', '学院', '学校', 'University', 'College', 'Institute', 'Academy', 'School'];
        }

        return array_values(array_filter(array_map(static fn ($item): string => is_string($item) ? trim($item) : '', $configured)));
    }

    /**
     * @return array<int, string>
     */
    public function majorKeywords(): array
    {
        $configured = config('resume.import_parser.education_major_keywords', []);
        if (! is_array($configured) || $configured === []) {
            return ['专业', '工程', '科学', '技术', 'Engineering', 'Science', 'Technology'];
        }

        return array_values(array_filter(array_map(static fn ($item): string => is_string($item) ? trim($item) : '', $configured)));
    }

    /**
     * @return array<string, string>
     */
    public function schoolAliases(): array
    {
        $configured = config('resume.import_parser.education_school_aliases', []);

        return $this->normalizeAliasMap($configured);
    }

    /**
     * @return array<string, string>
     */
    public function majorAliases(): array
    {
        $configured = config('resume.import_parser.education_major_aliases', []);

        return $this->normalizeAliasMap($configured);
    }

    public function schoolRegex(): string
    {
        $suffixPattern = $this->keywordAlternationPattern($this->schoolKeywords());

        return '/([^\|\/·•,\s]{2,}(?:'.$suffixPattern.'))/iu';
    }

    /**
     * @param  array<int, string>  $keywords
     */
    public function keywordAlternationPattern(array $keywords): string
    {
        $escaped = array_map(static fn (string $keyword): string => preg_quote($keyword, '/'), $keywords);
        if ($escaped === []) {
            return '大学|学院|学校|University|College|Institute|Academy|School';
        }

        return implode('|', $escaped);
    }

    /**
     * @param  array<int, string>  $keywords
     */
    public function matchesKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword === '') {
                continue;
            }
            if (preg_match('/'.preg_quote($keyword, '/').'/iu', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    public function normalizeAliasMap(mixed $configured): array
    {
        if (! is_array($configured) || $configured === []) {
            return [];
        }

        $result = [];
        foreach ($configured as $alias => $target) {
            if (! is_string($alias) || ! is_string($target)) {
                continue;
            }
            $alias = trim($alias);
            $target = trim($target);
            if ($alias === '' || $target === '') {
                continue;
            }
            $result[$alias] = $target;
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $aliasMap
     */
    public function normalizeByAlias(string $value, array $aliasMap): string
    {
        $value = trim($value);
        if ($value === '' || $aliasMap === []) {
            return $value;
        }

        foreach ($aliasMap as $alias => $target) {
            if (mb_strtolower($value) === mb_strtolower($alias)) {
                return $target;
            }
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $aliasMap
     */
    public function resolveAliasFromText(string $text, array $aliasMap): string
    {
        if ($aliasMap === []) {
            return '';
        }

        foreach ($aliasMap as $alias => $target) {
            if (preg_match('/'.preg_quote($alias, '/').'/iu', $text) === 1) {
                return $target;
            }
        }

        return '';
    }

    private function isDateLikePart(string $text): bool
    {
        return preg_match('/((19|20)\d{2}([\.\/-]\d{1,2})?(\s*[~-]\s*|至|到)?((19|20)\d{2}([\.\/-]\d{1,2})?|至今|现在)?|\d{4}\.\d{1,2}\s*-\s*\d{4}\.\d{1,2})/u', $text) === 1;
    }
}
