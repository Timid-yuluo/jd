<?php

declare(strict_types=1);

namespace App\Support\Resume;

final class ResumeModuleSerializer
{
    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $modules
     */
    public static function toRawText(array $modules): string
    {
        $parts = [];
        foreach ($modules as $module) {
            $type = (string) ($module['type'] ?? '');
            $data = (array) ($module['data'] ?? []);
            $part = self::moduleToText($type, $data);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return implode("\n\n", $parts);
    }

    private static function moduleToText(string $type, array $data): string
    {
        return match ($type) {
            'personal' => self::personalToText($data),
            'objective' => self::objectiveToText($data),
            default => self::genericToText($data),
        };
    }

    private static function personalToText(array $data): string
    {
        $parts = array_filter([
            $data['name'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['location'] ?? '',
        ], fn ($v) => trim((string) $v) !== '');

        return implode(' | ', $parts);
    }

    private static function objectiveToText(array $data): string
    {
        $parts = array_filter([
            $data['target_job'] ?? '',
            $data['content'] ?? '',
        ], fn ($v) => trim((string) $v) !== '');

        return implode(': ', $parts);
    }

    private static function genericToText(array $data): string
    {
        $parts = [];
        if (! empty($data['title'])) {
            $parts[] = (string) $data['title'];
        }
        if (! empty($data['subtitle'])) {
            $parts[] = (string) $data['subtitle'];
        }
        if (! empty($data['date'])) {
            $parts[] = (string) $data['date'];
        }
        if (! empty($data['location'])) {
            $parts[] = (string) $data['location'];
        }
        if (! empty($data['content'])) {
            $parts[] = (string) $data['content'];
        }
        if (! empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $parts[] = $item;
                } elseif (is_array($item) && ! empty($item['content'])) {
                    $parts[] = (string) $item['content'];
                }
            }
        }

        return implode("\n", $parts);
    }
}
