<?php

declare(strict_types=1);

namespace App\Services\Resume\Builders;

final class DocxTemplateService
{
    /**
     * @return array{accent:string,accent_light:string}
     */
    public function resolveThemePalette(string $theme): array
    {
        return match ($theme) {
            'coral' => ['accent' => 'FF6B6B', 'accent_light' => 'FFF0F0'],
            'green' => ['accent' => '27AE60', 'accent_light' => 'E8F5E9'],
            'purple' => ['accent' => '7C3AED', 'accent_light' => 'F3E8FF'],
            'orange' => ['accent' => 'F59E0B', 'accent_light' => 'FFF7ED'],
            default => ['accent' => '2563EB', 'accent_light' => 'EFF6FF'],
        };
    }

    /**
     * @param  array{accent:string,accent_light:string}  $palette
     * @return array<string,string|int>
     */
    public function resolveTemplateVariant(string $template, array $palette): array
    {
        return match ($template) {
            'timeline' => [
                'template' => 'timeline',
                'info_fill' => 'FFFFFF',
                'info_name_color' => '1F2937',
                'info_target_color' => $palette['accent'],
                'info_contact_color' => '4B5563',
                'divider_color' => $palette['accent'],
                'section_color' => '1F2937',
                'section_border_color' => $palette['accent'],
                'section_size' => 24,
                'entry_fill' => 'F8FAFC',
                'entry_title_color' => '1F2937',
                'chip_fill' => 'F0F9FF',
                'chip_color' => $palette['accent'],
                'highlight_fill' => 'F8FAFC',
                'highlight_color' => '1F2937',
            ],
            'creative' => [
                'template' => 'creative',
                'info_fill' => $palette['accent'],
                'info_name_color' => 'FFFFFF',
                'info_target_color' => 'FFFFFF',
                'info_contact_color' => 'F8FAFC',
                'divider_color' => $palette['accent'],
                'section_color' => $palette['accent'],
                'section_border_color' => $palette['accent'],
                'section_size' => 26,
                'entry_fill' => 'FFFFFF',
                'entry_title_color' => $palette['accent'],
                'chip_fill' => $palette['accent'],
                'chip_color' => 'FFFFFF',
                'highlight_fill' => $palette['accent_light'],
                'highlight_color' => $palette['accent'],
            ],
            'elegant' => [
                'template' => 'elegant',
                'info_fill' => 'FFFFFF',
                'info_name_color' => '2C3E50',
                'info_target_color' => '5D6D7E',
                'info_contact_color' => '7F8C8D',
                'divider_color' => 'D4AF37',
                'section_color' => '2C3E50',
                'section_border_color' => 'D4AF37',
                'section_size' => 22,
                'entry_fill' => 'FAFAF8',
                'entry_title_color' => '2C3E50',
                'chip_fill' => 'F8F5EF',
                'chip_color' => '5D6D7E',
                'highlight_fill' => 'F8F5EF',
                'highlight_color' => '2C3E50',
            ],
            'modern' => [
                'template' => 'modern',
                'info_fill' => '1E293B',
                'info_name_color' => 'FFFFFF',
                'info_target_color' => 'BFDBFE',
                'info_contact_color' => 'CBD5E1',
                'divider_color' => '1E293B',
                'section_color' => $palette['accent'],
                'section_border_color' => $palette['accent'],
                'section_size' => 24,
                'entry_fill' => 'F8FAFC',
                'entry_title_color' => '111827',
                'chip_fill' => 'E0F2FE',
                'chip_color' => '1D4ED8',
                'highlight_fill' => 'EFF6FF',
                'highlight_color' => '1E3A8A',
            ],
            'minimal' => [
                'template' => 'minimal',
                'info_fill' => 'FFFFFF',
                'info_name_color' => '1A1A1A',
                'info_target_color' => '525252',
                'info_contact_color' => '737373',
                'divider_color' => 'D4D4D4',
                'section_color' => '1A1A1A',
                'section_border_color' => 'D4D4D4',
                'section_size' => 22,
                'entry_fill' => 'FFFFFF',
                'entry_title_color' => '1A1A1A',
                'chip_fill' => 'FFFFFF',
                'chip_color' => '404040',
                'highlight_fill' => 'FAFAFA',
                'highlight_color' => '262626',
            ],
            default => [
                'template' => 'classic',
                'info_fill' => $palette['accent_light'],
                'info_name_color' => $palette['accent'],
                'info_target_color' => '111827',
                'info_contact_color' => '4B5563',
                'divider_color' => $palette['accent'],
                'section_color' => $palette['accent'],
                'section_border_color' => $palette['accent'],
                'section_size' => 26,
                'entry_fill' => $palette['accent_light'],
                'entry_title_color' => '111827',
                'chip_fill' => $palette['accent_light'],
                'chip_color' => $palette['accent'],
                'highlight_fill' => $palette['accent_light'],
                'highlight_color' => '1F2937',
            ],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function sectionTitleForType(string $type, array $entries): string
    {
        $first = $entries[0]['data'] ?? [];
        $title = trim((string) ((is_array($first) ? ($first['title'] ?? '') : '') ?: ''));
        if ($title !== '') {
            return $title;
        }

        return match ($type) {
            'experience' => '实习经历',
            'project' => '项目经历',
            'education' => '教育经历',
            'skill' => '技能证书',
            'certificate' => '证书资质',
            'summary' => '自我评价',
            default => '模块信息',
        };
    }
}
