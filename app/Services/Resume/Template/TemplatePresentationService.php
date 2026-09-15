<?php

declare(strict_types=1);

namespace App\Services\Resume\Template;

use App\Models\ResumeTemplate;
use Illuminate\Support\Collection;

final class TemplatePresentationService
{
    /**
     * @param  array<int, mixed>  $blueprint
     * @return array<int, array<string, mixed>>
     */
    public function buildPreviewModules(array $blueprint, string $position): array
    {
        $defaults = [
            'personal' => [
                'name' => '张三',
                'phone' => '138-0000-0000',
                'email' => 'zhangsan@example.com',
                'location' => '上海',
            ],
            'objective' => [
                'target_job' => $position,
                'content' => "具备扎实的{$position}能力，关注结果和协作效率，期望在新团队持续创造业务价值。",
            ],
            'education' => [
                'title' => '教育背景',
                'subtitle' => 'XX大学 · 计算机科学与技术',
                'date' => '2018.09 - 2022.06',
                'items' => ['本科 · GPA 3.7/4.0', '主修课程：数据结构、操作系统、数据库系统'],
            ],
            'experience' => [
                'title' => '工作经历',
                'subtitle' => "XX科技有限公司 · {$position}",
                'date' => '2022.07 - 至今',
                'items' => ['负责核心业务模块迭代，推动需求按期上线。', '建立数据看板，优化关键流程效率约 30%。'],
            ],
            'project' => [
                'title' => '项目经验',
                'subtitle' => '核心项目 A',
                'date' => '2023.01 - 2023.09',
                'items' => ['从 0 到 1 参与项目方案设计与落地。', '保障项目稳定性，支持高峰期业务访问。'],
            ],
            'skill' => [
                'title' => '技能特长',
                'items' => ['沟通协作', '结构化思维', '数据分析', '项目推进'],
            ],
            'summary' => [
                'title' => '自我评价',
                'content' => "结果导向，学习速度快，能够快速融入团队并独立承担{$position}相关工作。",
            ],
        ];

        $result = [];
        foreach ($blueprint as $index => $module) {
            if (! is_array($module) || ! isset($module['type'])) {
                continue;
            }

            $type = (string) $module['type'];
            $sourceData = is_array($module['data'] ?? null) ? $module['data'] : [];
            $baseData = is_array($defaults[$type] ?? null) ? $defaults[$type] : [];
            $mergedData = array_merge($baseData, $sourceData);

            if ($type === 'personal' && ! isset($mergedData['location']) && isset($mergedData['city'])) {
                $mergedData['location'] = (string) $mergedData['city'];
            }
            if (isset($mergedData['items']) && ! is_array($mergedData['items'])) {
                $mergedData['items'] = [];
            }

            $result[] = [
                'type' => $type,
                'sort_order' => (int) ($module['sort_order'] ?? $index),
                'data' => $mergedData,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function buildDifferentiationPoints(ResumeTemplate $template, int $limit = 4): array
    {
        $templateLabel = match ((string) $template->template) {
            'modern' => '现代双栏，信息分区更清晰',
            'minimal' => '极简排版，阅读干扰更少',
            'timeline' => '时间线布局，经历脉络更直观',
            'creative' => '创意视觉，风格辨识度更高',
            'elegant' => '优雅商务，适合中高端岗位',
            default => '经典单栏，稳定通用',
        };
        $themeLabel = match ((string) $template->theme) {
            'green' => '清新绿主题，风格更亲和',
            'purple' => '紫罗兰主题，视觉更聚焦',
            'orange' => '活力橙主题，强调主动性',
            'coral' => '珊瑚红主题，表达更鲜明',
            default => '商务蓝主题，专业稳重',
        };
        $densityLabel = match ((string) $template->density) {
            '高' => '高信息密度，适合经历较丰富候选人',
            '低' => '低信息密度，适合校招或简洁表达',
            default => '中信息密度，兼顾信息量与可读性',
        };
        $atsLabel = (string) $template->ats_level === 'A+'
            ? 'ATS 兼容性更强，关键词检索更友好'
            : 'ATS 兼容性良好，适配主流招聘系统';

        $points = [
            "版式特征：{$templateLabel}",
            "主题特征：{$themeLabel}",
            "ATS 表现：{$atsLabel}",
            "内容承载：{$densityLabel}",
        ];

        $blueprint = is_array($template->module_blueprint) ? $template->module_blueprint : [];
        $types = collect($blueprint)
            ->filter(static fn ($item): bool => is_array($item) && isset($item['type']))
            ->pluck('type')
            ->map(static fn ($type): string => (string) $type)
            ->values()
            ->all();

        if (in_array('project', $types, true) && in_array('experience', $types, true)) {
            $points[] = '模块侧重：工作经历 + 项目经验并重，利于展示成果闭环';
        } elseif (in_array('project', $types, true)) {
            $points[] = '模块侧重：项目表达更突出，适合技术/产品岗位';
        } elseif (in_array('skill', $types, true)) {
            $points[] = '模块侧重：技能标签清晰，便于快速筛选';
        }

        return collect($points)->unique()->take(max(1, $limit))->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public function buildSuitableScenes(ResumeTemplate $template): array
    {
        $scenes = [];
        $level = (string) $template->level;
        $position = (string) $template->position;
        $category = (string) $template->category;

        if (str_contains($level, '校招')) {
            $scenes[] = '校招/实习投递（强调基础能力与潜力）';
        }
        if (str_contains($level, '中高级') || str_contains($level, '管理')) {
            $scenes[] = '中高级岗位投递（强调项目影响力与业务结果）';
        }
        if ($category === '技术') {
            $scenes[] = '技术岗位投递（突出项目与技能深度）';
        } elseif ($category === '产品') {
            $scenes[] = '产品岗位投递（突出方法论与跨团队协同）';
        } elseif ($category === '设计') {
            $scenes[] = '设计岗位投递（突出视觉风格与项目表达）';
        } else {
            $scenes[] = '通用岗位投递（兼顾专业性与阅读效率）';
        }

        if (str_contains($position, '运营') || str_contains($position, '营销')) {
            $scenes[] = '增长/运营方向（突出数据指标与转化结果）';
        }

        return collect($scenes)->unique()->values()->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function focusStyleMap(): array
    {
        return [
            '通用稳妥' => ['专业蓝', '极简白', '一页速览'],
            '成果导向' => ['成果导向', '业绩看板', '咨询叙事'],
            '项目强化' => ['创意橙', '时间线', '项目分栏', '叙事履历', '项目案例集', '项目前置'],
            '技能矩阵' => ['技能版', '技能墙'],
            '校招潜力' => ['校招版', '学术成长'],
            '管理表达' => ['商务灰', '管理横幅'],
            '形象表达' => ['头像主视觉'],
            '能力叙事' => ['能力叙事'],
        ];
    }

    public function focusLabelByStyle(string $style): string
    {
        foreach ($this->focusStyleMap() as $label => $styles) {
            if (in_array($style, $styles, true)) {
                return $label;
            }
        }

        return '通用稳妥';
    }

    /**
     * @param  Collection<int, ResumeTemplate>  $relatedTemplates
     * @return Collection<int, ResumeTemplate>
     */
    public function normalizeRelatedTemplates(Collection $relatedTemplates): Collection
    {
        $uniqueById = $relatedTemplates->unique('id')->values();
        $picked = collect();
        $seenSignature = [];

        foreach ($uniqueById as $template) {
            $signature = strtolower(trim((string) $template->position).'|'.trim((string) $template->style));
            if (isset($seenSignature[$signature]) && $picked->count() < 4) {
                continue;
            }
            $seenSignature[$signature] = true;
            $picked->push($template);
            if ($picked->count() >= 6) {
                break;
            }
        }

        if ($picked->count() < 6) {
            foreach ($uniqueById as $template) {
                if ($picked->contains('id', $template->id)) {
                    continue;
                }
                $picked->push($template);
                if ($picked->count() >= 6) {
                    break;
                }
            }
        }

        return $picked->values();
    }

    /**
     * @param  Collection<int, ResumeTemplate>  $templates
     * @return Collection<int, ResumeTemplate>
     */
    public function diversifyTemplates(Collection $templates, int $maxPerPosition = 4): Collection
    {
        $grouped = [];
        $result = collect();

        foreach ($templates as $template) {
            $positionKey = trim((string) $template->position);
            $count = $grouped[$positionKey] ?? 0;
            if ($count >= $maxPerPosition) {
                continue;
            }
            $grouped[$positionKey] = $count + 1;
            $result->push($template);
        }

        return $result;
    }
}
