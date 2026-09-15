<?php

declare(strict_types=1);

namespace App\Services\Resume\Parsing;

final class ResumeEntryParser
{
    /**
     * @param  array<int,string>  $lines
     * @return array<int, array<int, string>>
     */
    public function splitIntoEntryChunks(array $lines, string $sectionType): array
    {
        $cleaned = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $cleaned[] = $line;
            }
        }

        if ($cleaned === []) {
            return [];
        }

        $chunks = [];
        $current = [];
        foreach ($cleaned as $line) {
            $isHeader = $this->isLikelyEntryHeader($line, $sectionType);
            if ($isHeader && $current !== []) {
                $chunks[] = $current;
                $current = [];
            }
            $current[] = $line;
        }
        if ($current !== []) {
            $chunks[] = $current;
        }

        return count($chunks) > 1 ? $chunks : [];
    }

    /**
     * @param  array<int,string>  $lines
     * @return array{subtitle:string,date:string,location:string,items:array<int,string>,content:string}
     */
    public function parseExperienceLike(array $lines): array
    {
        $subtitle = '';
        $date = '';
        $location = '';
        $items = [];
        $contentLines = [];

        $firstNonEmpty = true;
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }

            if ($firstNonEmpty || ($items === [] && $this->consumeMetaLine($t, $subtitle, $date, $location))) {
                $firstNonEmpty = false;

                continue;
            }

            if (preg_match('/^([-*■•]\s+|\d+[\.、]\s*)(.+)$/u', $t, $m)) {
                $items[] = $m[2];

                continue;
            }

            $contentLines[] = $line;
        }

        [$date, $location, $contentLines] = $this->extractMetaFromContentLines($date, $location, $contentLines);

        return [
            'subtitle' => $subtitle,
            'date' => $date,
            'location' => $location,
            'items' => $items,
            'content' => implode("\n", $contentLines),
        ];
    }

    /**
     * @param  array<int,string>  $contentLines
     * @return array{0:string,1:string,2:array<int,string>}
     */
    public function extractMetaFromContentLines(string $date, string $location, array $contentLines): array
    {
        $remaining = [];
        foreach ($contentLines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if ($date === '') {
                $dateCandidate = $this->extractDateFromLine($trimmed);
                if ($dateCandidate !== '') {
                    $date = $dateCandidate;
                    $trimmed = trim(str_replace($dateCandidate, '', $trimmed));
                }
            }

            if ($location === '') {
                $locationCandidate = $this->extractLocationFromLine($trimmed);
                if ($locationCandidate !== '') {
                    $location = $locationCandidate;
                    $trimmed = trim(str_replace($locationCandidate, '', $trimmed));
                }
            }

            if ($trimmed === '') {
                continue;
            }

            $remaining[] = $trimmed;
        }

        return [$date, $location, $remaining];
    }

    public function extractDateFromLine(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $normalized = str_replace(['－', '–', '—', '〜', '～'], '-', $text);

        if (preg_match('/(?:时间|日期|周期|任职时间|项目周期|Duration|Period)[:：]\s*([^\s,，;；]+)/iu', $normalized, $m)) {
            $candidate = trim((string) ($m[1] ?? ''));
            if ($candidate !== '' && $this->isDateLike($candidate)) {
                return $candidate;
            }
        }

        if (preg_match('/((?:19|20)\d{2}(?:[\.\/-]\d{1,2})?)\s*(?:-|~|至|到)\s*((?:(?:19|20)\d{2}(?:[\.\/-]\d{1,2})?)|至今|现在|Present|Now)/iu', $normalized, $m)) {
            return trim((string) $m[1]).'-'.trim((string) $m[2]);
        }

        if (preg_match('/((?:19|20)\d{2}(?:[\.\/-]\d{1,2})?\s*(?:-|~|至|到)\s*(?:(?:19|20)\d{2}(?:[\.\/-]\d{1,2})?|至今|现在|Present|Now))/iu', $normalized, $m)) {
            return trim((string) $m[1]);
        }

        if (preg_match_all('/((?:19|20)\d{2}(?:[\.\/-]\d{1,2})?)/u', $normalized, $all) === 1 && isset($all[1]) && count($all[1]) >= 2) {
            $first = trim((string) $all[1][0]);
            $last = trim((string) $all[1][count($all[1]) - 1]);
            if ($first !== '' && $last !== '' && $first !== $last) {
                return $first.'-'.$last;
            }
        }

        if (preg_match('/((?:19|20)\d{2}[\.\/-]\d{1,2})/u', $normalized, $m)) {
            return trim((string) $m[1]);
        }

        return '';
    }

    public function extractLocationFromLine(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/(?:地点|城市|地址|Location|City)[:：]\s*([^\n,，;；]+)/iu', $text, $m)) {
            $candidate = trim((string) ($m[1] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $parts = preg_split('/\s+/u', $text) ?: [$text];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && $this->isLocationLike($part)) {
                return $part;
            }
        }

        return '';
    }

    /**
     * @param  array<int,string>  $lines
     * @return array<int,string>
     */
    public function extractListItems(array $lines): array
    {
        $items = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if (preg_match('/^([-*■•]\s+|\d+[\.、]\s*)(.+)$/u', $t, $m)) {
                $items[] = $m[2];
            } elseif ($t !== '') {
                $items[] = $t;
            }
        }

        return $items;
    }

    public function isListLike(string $line): bool
    {
        return preg_match('/^([-*■•]\s+|\d+[\.、]\s*)(.+)$/u', trim($line)) === 1;
    }

    /**
     * @param  array<string,mixed>  $entry
     */
    public function isEmptyExperienceLike(array $entry): bool
    {
        $subtitle = trim((string) ($entry['subtitle'] ?? ''));
        $date = trim((string) ($entry['date'] ?? ''));
        $location = trim((string) ($entry['location'] ?? ''));
        $content = trim((string) ($entry['content'] ?? ''));
        $items = is_array($entry['items'] ?? null)
            ? array_filter($entry['items'], static fn ($item): bool => is_string($item) && trim($item) !== '')
            : [];

        return $subtitle === '' && $date === '' && $location === '' && $content === '' && $items === [];
    }

    public function isLikelyEntryHeader(string $line, string $sectionType): bool
    {
        $line = trim($line);
        if ($line === '' || $this->isListLike($line)) {
            return false;
        }

        $hasDate = $this->isDateLike($line);
        $hasDivider = preg_match('/[\|｜\/·•]/u', $line) === 1;
        $hasDashDivider = preg_match('/\s[-–—]\s/u', $line) === 1;
        $lineLength = mb_strlen($line);

        if ($sectionType === 'education') {
            $isSchoolLike = preg_match('/(大学|学院|学校|University|College|Institute|Academy|School)/iu', $line) === 1;
            if ($isSchoolLike && $lineLength <= 90) {
                return true;
            }

            if ($hasDate && ($hasDivider || $hasDashDivider) && $lineLength <= 120) {
                return true;
            }

            return false;
        }

        if ($sectionType === 'experience') {
            $isCompanyLike = preg_match('/(公司|集团|科技|有限|股份|Inc\.?|Ltd\.?|LLC|Corp\.?|Company|Studio|工作室|研究院)/iu', $line) === 1;
            if (($isCompanyLike || $hasDate) && ($hasDivider || $hasDashDivider) && $lineLength <= 140) {
                return true;
            }

            return $hasDate && $lineLength <= 80;
        }

        if ($sectionType === 'project') {
            $isProjectLike = preg_match('/(项目|系统|平台|小程序|APP|App|Platform|System)/u', $line) === 1;
            if (($isProjectLike || $hasDate) && ($hasDivider || $hasDashDivider) && $lineLength <= 140) {
                return true;
            }

            return $hasDate && $lineLength <= 80;
        }

        if ($sectionType === 'certificate') {
            return ($hasDate || preg_match('/(奖|证书|认证|荣誉|资格)/u', $line) === 1) && $lineLength <= 120;
        }

        return false;
    }

    public function isDateLike(string $text): bool
    {
        return preg_match('/((19|20)\d{2}([\.\/-]\d{1,2})?(\s*[~-]\s*|至|到)?((19|20)\d{2}([\.\/-]\d{1,2})?|至今|现在)?|\d{4}\.\d{1,2}\s*-\s*\d{4}\.\d{1,2})/u', $text) === 1;
    }

    public function isLocationLike(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return preg_match('/(远程|驻场|海外|北京|上海|深圳|广州|杭州|苏州|成都|武汉|西安|南京|长沙|厦门|天津|重庆|郑州|合肥|福州|珠海|东莞|宁波|青岛|香港|澳门|台湾|.+(省|市|区|县))$/u', $text) === 1;
    }

    private function consumeMetaLine(string $line, string &$subtitle, string &$date, string &$location): bool
    {
        $line = trim($line);
        if ($line === '' || $this->isListLike($line)) {
            return false;
        }

        $parts = preg_split('/\s*[\|｜\/·•]\s*/u', $line) ?: [$line];
        $subtitleParts = [];
        $matched = false;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $dateCandidate = $this->extractDateFromLine($part);
            if ($date === '' && $dateCandidate !== '') {
                $date = $dateCandidate;
                $matched = true;
                $remainder = trim(str_replace($dateCandidate, '', $part));
                if ($remainder !== '' && ! $this->isLocationLike($remainder)) {
                    $subtitleParts[] = $remainder;
                }

                continue;
            }

            $locationCandidate = $this->extractLocationFromLine($part);
            if ($location === '' && $locationCandidate !== '') {
                $location = $locationCandidate;
                $matched = true;
                $remainder = trim(str_replace($locationCandidate, '', $part));
                if ($remainder !== '' && ! $this->isDateLike($remainder)) {
                    $subtitleParts[] = $remainder;
                }

                continue;
            }

            $subtitleParts[] = $part;
        }

        if ($subtitle === '' && ! empty($subtitleParts)) {
            $subtitle = implode(' | ', $subtitleParts);
            $matched = true;
        }

        if (! $matched && $subtitle === '') {
            $subtitle = $line;

            return true;
        }

        if (! $matched && $subtitle !== '' && ($this->isDateLike($line) || $this->isLocationLike($line))) {
            if ($date === '' && $this->isDateLike($line)) {
                $date = $line;
            } elseif ($location === '' && $this->isLocationLike($line)) {
                $location = $line;
            }

            return true;
        }

        return $matched;
    }
}
