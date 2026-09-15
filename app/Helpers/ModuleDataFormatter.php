<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * 简历模块数据格式化辅助类，用于版本对比展示
 */
final class ModuleDataFormatter
{
    /**
     * 将模块快照数据格式化为可读 HTML
     */
    public static function toHtml(?array $module): string
    {
        if (! $module) {
            return '<span class="text-secondary">（无数据）</span>';
        }

        $data = $module['data'] ?? [];
        $type = $module['type'] ?? 'custom';

        return match ($type) {
            'summary' => self::formatSummary($data),
            'work_experience' => self::formatListItems($data, ['company', 'position', 'period', 'description']),
            'education' => self::formatListItems($data, ['school', 'major', 'degree', 'period']),
            'skills' => self::formatSkills($data),
            'projects' => self::formatListItems($data, ['name', 'role', 'period', 'description']),
            'certifications' => self::formatListItems($data, ['name', 'issuer', 'date']),
            'languages' => self::formatSkills($data),
            'awards' => self::formatListItems($data, ['name', 'issuer', 'date']),
            default => self::formatGeneric($data),
        };
    }

    private static function formatSummary(array $data): string
    {
        $text = $data['content'] ?? $data['text'] ?? '';
        if (empty($text)) {
            return '<span class="text-secondary">（空）</span>';
        }

        return nl2br(e(mb_substr($text, 0, 500)));
    }

    private static function formatListItems(array $data, array $fields): string
    {
        $items = $data['items'] ?? $data['list'] ?? [];
        if (empty($items) || ! is_array($items)) {
            $single = self::extractFields($data, $fields);
            if ($single) {
                return '<div class="ps-2 border-start">' . $single . '</div>';
            }

            return '<span class="text-secondary">（空）</span>';
        }

        $html = '';
        foreach (array_slice($items, 0, 5) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $html .= '<div class="ps-2 border-start mb-1">' . self::extractFields($item, $fields) . '</div>';
        }

        return $html ?: '<span class="text-secondary">（空）</span>';
    }

    private static function extractFields(array $data, array $fields): string
    {
        $parts = [];
        foreach ($fields as $field) {
            $val = $data[$field] ?? null;
            if (! empty($val) && is_string($val)) {
                $parts[] = '<span class="d-inline-block me-2">' . e(mb_substr($val, 0, 100)) . '</span>';
            }
        }

        return implode('', $parts) ?: '<span class="text-secondary">-</span>';
    }

    private static function formatSkills(array $data): string
    {
        $items = $data['items'] ?? $data['list'] ?? $data['skills'] ?? [];
        if (is_string($items)) {
            return e(mb_substr($items, 0, 300));
        }
        if (! is_array($items)) {
            $text = $data['content'] ?? $data['text'] ?? '';
            if ($text) {
                return e(mb_substr($text, 0, 300));
            }

            return '<span class="text-secondary">（空）</span>';
        }

        $names = [];
        foreach (array_slice($items, 0, 10) as $item) {
            if (is_string($item)) {
                $names[] = e($item);
            } elseif (is_array($item) && isset($item['name'])) {
                $names[] = e($item['name']);
            } elseif (is_array($item) && isset($item['skill'])) {
                $names[] = e($item['skill']);
            }
        }

        return $names ? implode('<span class="text-secondary mx-1">·</span>', $names) : '<span class="text-secondary">（空）</span>';
    }

    private static function formatGeneric(array $data): string
    {
        $text = $data['content'] ?? $data['text'] ?? '';
        if ($text) {
            return nl2br(e(mb_substr($text, 0, 500)));
        }

        $parts = [];
        foreach (array_slice($data, 0, 8) as $key => $val) {
            if (is_string($val)) {
                $parts[] = '<strong>' . e($key) . ':</strong> ' . e(mb_substr($val, 0, 80));
            }
        }

        return $parts ? implode('<br>', $parts) : '<span class="text-secondary">（空）</span>';
    }
}
