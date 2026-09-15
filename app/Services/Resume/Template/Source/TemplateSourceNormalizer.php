<?php

declare(strict_types=1);

namespace App\Services\Resume\Template\Source;

use App\Models\ResumeTemplate;
use App\Models\TemplateSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TemplateSourceNormalizer
{
    private const CATEGORY_MAP = [
        'tech' => '技术',
        'product' => '产品',
        'design' => '设计',
        'marketing' => '市场运营',
        'finance' => '职能',
        'hr' => '职能',
        'admin' => '职能',
        'intern' => '校招实习',
        'fresh' => '校招实习',
        '技术' => '技术',
        '产品' => '产品',
        '设计' => '设计',
        '市场运营' => '市场运营',
        '职能' => '职能',
        '校招实习' => '校招实习',
    ];

    private const LEVEL_MAP = [
        'intern' => '实习生',
        'fresh' => '应届生',
        'junior' => '社招1-3年',
        'mid' => '社招3-5年',
        'senior' => '社招5-10年',
        'lead' => '资深/管理',
        'manager' => '资深/管理',
        '实习生' => '实习生',
        '应届生' => '应届生',
        '社招1-3年' => '社招1-3年',
        '社招3-5年' => '社招3-5年',
        '社招5-10年' => '社招5-10年',
        '资深/管理' => '资深/管理',
    ];

    private const TEMPLATE_MAP = [
        'classic' => 'classic',
        'modern' => 'modern',
        'minimal' => 'minimal',
        'timeline' => 'timeline',
        'creative' => 'creative',
        'elegant' => 'elegant',
        'professional' => 'professional',
        'academic' => 'academic',
        'internet' => 'internet',
        'executive' => 'executive',
        'startup' => 'startup',
        'student' => 'student',
        'simple' => 'minimal',
        'standard' => 'classic',
        'business' => 'professional',
        'tech' => 'internet',
    ];

    /**
     * @param  array<string, mixed>  $externalData
     * @return array<string, mixed>
     */
    public function normalize(TemplateSource $source, array $externalData): array
    {
        $externalId = (string) ($externalData['id'] ?? $externalData['template_id'] ?? '');

        return [
            'slug' => $this->buildSlug($source, $externalId),
            'name' => $this->extractName($externalData),
            'category' => $this->mapCategory($externalData),
            'position' => $this->extractPosition($externalData),
            'level' => $this->mapLevel($externalData),
            'industry' => $this->extractIndustry($externalData),
            'style' => $this->extractStyle($externalData),
            'template' => $this->mapTemplate($externalData),
            'theme' => $this->mapTheme($externalData),
            'ats_level' => $this->mapAtsLevel($externalData),
            'density' => $this->mapDensity($externalData),
            'tags' => $this->extractTags($externalData),
            'font_settings' => $this->extractFontSettings($externalData),
            'module_blueprint' => $this->extractBlueprint($externalData),
            'preview_image_url' => $this->extractPreviewUrl($externalData),
            'is_active' => true,
            'is_featured' => (bool) ($externalData['is_featured'] ?? $externalData['featured'] ?? false),
            'usage_count' => (int) ($externalData['usage_count'] ?? $externalData['use_count'] ?? 0),
            'sort_order' => (int) ($externalData['sort_order'] ?? 200),
            'source_id' => $source->id,
            'source_driver' => $source->driver,
            'external_id' => $externalId,
            'external_url' => $this->extractExternalUrl($externalData, $source),
            'synced_at' => now(),
        ];
    }

    private function buildSlug(TemplateSource $source, string $externalId): string
    {
        $prefix = Str::slug($source->slug);
        $suffix = Str::slug($externalId);

        return "ext-{$prefix}-{$suffix}";
    }

    private function extractName(array $data): string
    {
        return (string) ($data['name'] ?? $data['title'] ?? $data['template_name'] ?? '未命名模板');
    }

    private function mapCategory(array $data): string
    {
        $raw = (string) ($data['category'] ?? $data['type'] ?? '');

        return self::CATEGORY_MAP[$raw] ?? $raw;
    }

    private function extractPosition(array $data): string
    {
        return (string) ($data['position'] ?? $data['job_title'] ?? $data['target_role'] ?? '通用岗位');
    }

    private function mapLevel(array $data): string
    {
        $raw = (string) ($data['level'] ?? $data['experience_level'] ?? $data['seniority'] ?? '');

        return self::LEVEL_MAP[$raw] ?? ($raw !== '' ? $raw : '社招1-3年');
    }

    private function extractIndustry(array $data): ?string
    {
        $industry = (string) ($data['industry'] ?? $data['field'] ?? '');

        return $industry !== '' ? $industry : null;
    }

    private function extractStyle(array $data): string
    {
        return (string) ($data['style'] ?? $data['layout_style'] ?? '通用');
    }

    private function mapTemplate(array $data): string
    {
        $raw = strtolower(trim((string) ($data['template'] ?? $data['layout'] ?? $data['template_type'] ?? 'classic')));

        return self::TEMPLATE_MAP[$raw] ?? 'classic';
    }

    private function mapTheme(array $data): string
    {
        $raw = strtolower(trim((string) ($data['theme'] ?? $data['color_scheme'] ?? 'blue')));
        $valid = ['blue', 'coral', 'green', 'purple', 'orange'];

        return in_array($raw, $valid, true) ? $raw : 'blue';
    }

    private function mapAtsLevel(array $data): string
    {
        $raw = strtoupper(trim((string) ($data['ats_level'] ?? $data['ats_score'] ?? 'A')));

        return in_array($raw, ['A+', 'A', 'B+', 'B'], true) ? $raw : 'A';
    }

    private function mapDensity(array $data): string
    {
        $raw = (string) ($data['density'] ?? $data['content_density'] ?? '');

        return in_array($raw, ['高', '中', '低'], true) ? $raw : '中';
    }

    private function extractTags(array $data): array
    {
        $tags = $data['tags'] ?? $data['keywords'] ?? [];

        return is_array($tags) ? array_slice($tags, 0, 10) : [];
    }

    private function extractFontSettings(array $data): ?array
    {
        $settings = $data['font_settings'] ?? $data['font'] ?? null;

        return is_array($settings) ? $settings : null;
    }

    private function extractBlueprint(array $data): ?array
    {
        $blueprint = $data['module_blueprint'] ?? $data['modules'] ?? $data['sections'] ?? null;

        return is_array($blueprint) ? $blueprint : null;
    }

    private function extractPreviewUrl(array $data): ?string
    {
        $url = (string) ($data['preview_image_url'] ?? $data['preview_url'] ?? $data['thumbnail'] ?? $data['cover_url'] ?? '');

        return $url !== '' ? $url : null;
    }

    private function extractExternalUrl(array $data, TemplateSource $source): ?string
    {
        $path = (string) ($data['url'] ?? $data['detail_url'] ?? $data['link'] ?? '');
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $baseUrl = trim((string) $source->base_url, '/');

        return $baseUrl !== '' ? "{$baseUrl}/" . ltrim($path, '/') : null;
    }
}
