<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Resume;

final class ResumeRenderStyle
{
    /**
     * @return array{wrapper_style:string}
     */
    public static function fromResume(Resume $resume): array
    {
        $fontSettings = is_array($resume->content_structured ?? null)
            && is_array($resume->content_structured['font_settings'] ?? null)
            ? $resume->content_structured['font_settings']
            : [];

        $variant = self::normalizeTitleVariant((string) ($fontSettings['titleStyleVariant'] ?? 'template'));
        $titlePreset = self::titlePreset($variant);

        $styleTokens = [
            '--resume-font-family: '.self::fontFamily((string) ($fontSettings['fontFamily'] ?? '')),
            '--resume-body-font-size: '.self::clampFloat($fontSettings['fontSize'] ?? 13.5, 11.0, 16.0, 13.5).'px',
            '--resume-body-line-height: '.self::clampFloat($fontSettings['lineHeight'] ?? 1.7, 1.3, 2.2, 1.7),
            '--resume-heading-scale: '.self::clampFloat($fontSettings['headingFontSize'] ?? 1.11, 0.8, 1.5, 1.11),
            '--resume-accent-color: '.self::accentColor(
                (string) ($fontSettings['accentColor'] ?? ''),
                (string) ($resume->theme ?? 'blue')
            ),
            '--resume-section-spacing: '.self::clampFloat($fontSettings['sectionSpacing'] ?? 18, 8.0, 40.0, 18.0).'px',
            '--resume-title-weight: '.$titlePreset['weight'],
            '--resume-title-spacing: '.$titlePreset['spacing'],
            '--resume-title-transform: '.$titlePreset['transform'],
            '--resume-title-rule-width: '.$titlePreset['rule_width'],
            '--resume-title-rule-color: '.$titlePreset['rule_color'],
            '--resume-title-rule-padding: '.$titlePreset['rule_padding'],
        ];

        $headingColor = trim((string) ($fontSettings['headingColor'] ?? ''));
        if ($headingColor !== '') {
            $styleTokens[] = '--resume-heading-color: '.$headingColor;
        }

        $bodyColor = trim((string) ($fontSettings['bodyFontColor'] ?? ''));
        if ($bodyColor !== '') {
            $styleTokens[] = '--resume-body-color: '.$bodyColor;
        }

        return [
            'wrapper_style' => implode('; ', $styleTokens).';',
        ];
    }

    public static function sharedCss(): string
    {
        return <<<'CSS'
.resume-render-context,
.resume-render-context * {
    font-family: var(--resume-font-family, inherit) !important;
}

.resume-render-context .resume-template-classic,
.resume-render-context .resume-template-modern,
.resume-render-context .resume-template-minimal,
.resume-render-context .resume-template-timeline,
.resume-render-context .resume-template-creative,
.resume-render-context .resume-template-elegant {
    font-size: var(--resume-body-font-size, 13.5px) !important;
    line-height: var(--resume-body-line-height, 1.7) !important;
}

.resume-render-context .resume-template-classic h1,
.resume-render-context .resume-template-classic h2,
.resume-render-context .resume-template-classic h3,
.resume-render-context .resume-template-modern h1,
.resume-render-context .resume-template-modern h3,
.resume-render-context .resume-template-minimal h1,
.resume-render-context .resume-template-minimal h2,
.resume-render-context .resume-template-minimal h3,
.resume-render-context .resume-template-timeline h1,
.resume-render-context .resume-template-timeline h3,
.resume-render-context .resume-template-creative h1,
.resume-render-context .resume-template-creative h3,
.resume-render-context .resume-template-elegant h1,
.resume-render-context .resume-template-elegant h3 {
    color: var(--resume-heading-color, currentColor) !important;
}

.resume-render-context .resume-template-classic h1 { font-size: calc(28px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-classic h2,
.resume-render-context .resume-template-classic h3 {
    font-size: calc(18px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 700) !important;
    letter-spacing: var(--resume-title-spacing, 0.02em) !important;
    text-transform: var(--resume-title-transform, none) !important;
    border-bottom-width: var(--resume-title-rule-width, 1px) !important;
    border-bottom-color: var(--resume-title-rule-color, #e5e7eb) !important;
    padding-bottom: var(--resume-title-rule-padding, 6px) !important;
}

.resume-render-context .resume-template-modern h1 { font-size: calc(20px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-modern h3 {
    font-size: calc(14px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 700) !important;
    letter-spacing: var(--resume-title-spacing, 0.02em) !important;
    text-transform: var(--resume-title-transform, none) !important;
    border-bottom-color: var(--resume-title-rule-color, var(--resume-accent-color, #1e3a5f)) !important;
    padding-bottom: var(--resume-title-rule-padding, 4px) !important;
}
.resume-render-context .resume-template-modern > div:first-child,
.resume-render-context .resume-template-modern table td:first-child {
    background: var(--resume-accent-color, #1e3a5f) !important;
}

.resume-render-context .resume-template-minimal h1 { font-size: calc(26px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-minimal h2,
.resume-render-context .resume-template-minimal h3 {
    font-size: calc(12px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 600) !important;
    letter-spacing: var(--resume-title-spacing, 0.12em) !important;
    text-transform: var(--resume-title-transform, uppercase) !important;
}

.resume-render-context .resume-template-timeline h1 { font-size: calc(28px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-timeline h3 {
    font-size: calc(15px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 700) !important;
    letter-spacing: var(--resume-title-spacing, 0.02em) !important;
    text-transform: var(--resume-title-transform, none) !important;
}
.resume-render-context .resume-template-timeline > div:first-child {
    border-bottom-color: var(--resume-accent-color, #2563eb) !important;
}

.resume-render-context .resume-template-creative h1 { font-size: calc(26px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-creative h3 {
    font-size: calc(14px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 700) !important;
    letter-spacing: var(--resume-title-spacing, 0.01em) !important;
    text-transform: var(--resume-title-transform, none) !important;
}
.resume-render-context .resume-template-creative > div:first-child {
    background: linear-gradient(135deg, var(--resume-accent-color, #6366f1) 0%, var(--resume-accent-color, #6366f1) 100%) !important;
}

.resume-render-context .resume-template-elegant h1 { font-size: calc(32px * var(--resume-heading-scale, 1)) !important; }
.resume-render-context .resume-template-elegant h3 {
    font-size: calc(12px * var(--resume-heading-scale, 1)) !important;
    font-weight: var(--resume-title-weight, 600) !important;
    letter-spacing: var(--resume-title-spacing, 0.12em) !important;
    text-transform: var(--resume-title-transform, uppercase) !important;
}
.resume-render-context .resume-template-elegant > div:first-child,
.resume-render-context .resume-template-elegant > div:last-child {
    background: var(--resume-accent-color, #2c3e50) !important;
}

.resume-render-context .resume-template-classic > div:not(:first-child),
.resume-render-context .resume-template-minimal > div:not(:first-child),
.resume-render-context .resume-template-timeline > div:not(:first-child),
.resume-render-context .resume-template-creative > div:not(:first-child),
.resume-render-context .resume-template-elegant > div:not(:first-child) {
    margin-bottom: var(--resume-section-spacing, 18px) !important;
}
CSS;
    }

    /**
     * @return array{weight:string,spacing:string,transform:string,rule_width:string,rule_color:string,rule_padding:string}
     */
    private static function titlePreset(string $variant): array
    {
        return match ($variant) {
            'professional' => [
                'weight' => '700',
                'spacing' => '0.02em',
                'transform' => 'none',
                'rule_width' => '2px',
                'rule_color' => 'var(--resume-accent-color, #2563eb)',
                'rule_padding' => '5px',
            ],
            'minimal' => [
                'weight' => '600',
                'spacing' => '0.12em',
                'transform' => 'uppercase',
                'rule_width' => '1px',
                'rule_color' => '#e5e7eb',
                'rule_padding' => '8px',
            ],
            'executive' => [
                'weight' => '600',
                'spacing' => '0.12em',
                'transform' => 'uppercase',
                'rule_width' => '1px',
                'rule_color' => '#d7dee7',
                'rule_padding' => '8px',
            ],
            'creative' => [
                'weight' => '700',
                'spacing' => '0.01em',
                'transform' => 'none',
                'rule_width' => '0px',
                'rule_color' => 'transparent',
                'rule_padding' => '0px',
            ],
            'compact' => [
                'weight' => '700',
                'spacing' => '0',
                'transform' => 'none',
                'rule_width' => '1px',
                'rule_color' => '#dbe3ee',
                'rule_padding' => '4px',
            ],
            default => [
                'weight' => '700',
                'spacing' => '0.02em',
                'transform' => 'none',
                'rule_width' => '1px',
                'rule_color' => '#e5e7eb',
                'rule_padding' => '6px',
            ],
        };
    }

    private static function normalizeTitleVariant(string $variant): string
    {
        $value = trim($variant);
        if ($value === '' || $value === 'template') {
            return 'professional';
        }

        return in_array($value, ['professional', 'minimal', 'executive', 'creative', 'compact'], true)
            ? $value
            : 'professional';
    }

    private static function fontFamily(string $fontFamily): string
    {
        $value = trim($fontFamily);

        return $value !== ''
            ? $value
            : '"PingFang SC","Microsoft YaHei","WenQuanYi Micro Hei",sans-serif';
    }

    private static function accentColor(string $accentColor, string $theme): string
    {
        $value = trim($accentColor);
        if ($value !== '') {
            return $value;
        }

        return match ($theme) {
            'coral' => '#ff6b6b',
            'green' => '#27ae60',
            'purple' => '#7c3aed',
            'orange' => '#f59e0b',
            default => '#2563eb',
        };
    }

    private static function clampFloat(mixed $value, float $min, float $max, float $default): string
    {
        if (! is_numeric($value)) {
            return self::formatFloat($default);
        }

        $number = max($min, min($max, (float) $value));

        return self::formatFloat($number);
    }

    private static function formatFloat(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
