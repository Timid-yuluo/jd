<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ResumeTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ResumeTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = array_merge($this->graduateProfiles(), $this->baseProfiles());
        $stylePacks = $this->stylePacks();
        $targetCount = 3000;

        ResumeTemplate::query()->update(['is_active' => false]);

        $plan = [];
        foreach ($profiles as $profile) {
            $position = (string) $profile['position'];
            $category = (string) $profile['category'];
            $industry = (string) $profile['industry'];
            $levelCandidates = $this->levelCandidates($position);

            foreach ($stylePacks as $pack) {
                foreach ($levelCandidates as $level) {
                    $plan[] = [
                        'position' => $position,
                        'category' => $category,
                        'industry' => $industry,
                        'pack' => $pack,
                        'level' => $level,
                    ];
                }
            }
        }

        $categoryOrder = [
            '医疗健康', '教育', '法律', '金融', '咨询', '公职', '自由职业',
            '建筑地产', '制造', '传媒', '物流', '餐饮酒店',
            '技术', '产品', '设计', '市场运营', '职能', '校招应届', '校招实习',
        ];
        usort($plan, function (array $a, array $b) use ($categoryOrder): int {
            $ai = array_search($a['category'], $categoryOrder, true);
            $bi = array_search($b['category'], $categoryOrder, true);
            $ai = $ai === false ? 999 : $ai;
            $bi = $bi === false ? 999 : $bi;

            return $ai <=> $bi;
        });

        $maxPerCategory = 120;
        $categoryCount = [];
        $serial = 1;
        foreach ($plan as $item) {
            if ($serial > $targetCount) {
                break;
            }

            $position = $item['position'];
            $category = $item['category'];
            $industry = $item['industry'];
            $pack = $item['pack'];
            $level = $item['level'];

            $catCount = $categoryCount[$category] ?? 0;
            if ($catCount >= $maxPerCategory) {
                continue;
            }
            $categoryCount[$category] = $catCount + 1;

            $slug = sprintf('tpl-%04d', $serial);
            $style = (string) $pack['style'];
            $name = $this->buildTemplateName(
                (string) $category,
                (string) ($pack['name_prefix'] ?? $style),
                $level,
                $serial
            );
            $template = (string) $pack['template'];
            $theme = (string) $pack['theme'];
            $atsLevel = (string) $pack['ats_level'];
            $density = (string) $pack['density'];
            $previewImageUrl = (string) $pack['preview_image_url'];
            $moduleProfile = (string) ($pack['module_profile'] ?? 'balanced');
            $avatarUrl = (string) ($pack['avatar_url'] ?? '');

            ResumeTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category' => $category,
                    'position' => $position,
                    'level' => $level,
                    'industry' => $industry,
                    'style' => $style,
                    'template' => $template,
                    'theme' => $theme,
                    'ats_level' => $atsLevel,
                    'density' => $density,
                    'tags' => $this->buildTags($category, $position, $industry, $style, $atsLevel),
                    'font_settings' => [
                        'fontFamily' => (string) ($pack['font_family'] ?? 'pingfang'),
                        'fontSize' => (string) ($pack['font_size'] ?? 'medium'),
                        'lineHeight' => (string) ($pack['line_height'] ?? 'relaxed'),
                    ],
                    'module_blueprint' => $this->defaultModuleBlueprint($position, $moduleProfile, $level, $avatarUrl),
                    'preview_image_url' => $previewImageUrl,
                    'is_active' => true,
                    'is_featured' => $serial <= 36,
                    'sort_order' => intdiv($serial - 1, 12) + 1,
                ]
            );

            $serial++;
        }
    }

    /**
     * @return array<int, string>
     */
    private function buildTags(string $category, string $position, string $industry, string $style, string $atsLevel): array
    {
        $tags = [
            $category,
            $position,
            $industry,
            $style,
            'ATS'.$atsLevel,
            'ATS友好',
            '可直接套用',
        ];

        if (Str::contains($position, ['应届', '校招', '实习生'])) {
            $tags[] = '应届友好';
        }

        return array_values(array_filter($tags, static fn (string $value): bool => trim($value) !== ''));
    }

    /**
     * @return array<int, string>
     */
    private function levelCandidates(string $position): array
    {
        if (Str::contains($position, ['实习生', '应届', '校招'])) {
            return ['校招应届'];
        }

        if (Str::contains($position, ['总监', '负责人', '架构师', '主管'])) {
            return ['中高级', '管理层'];
        }

        return ['社招1-3年', '社招3年', '中高级'];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function graduateProfiles(): array
    {
        return [
            ['category' => '校招应届', 'position' => '应届Java后端工程师', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届前端开发工程师', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届测试开发工程师', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届算法工程师', 'industry' => '人工智能'],
            ['category' => '校招应届', 'position' => '应届数据分析师', 'industry' => '数据智能'],
            ['category' => '校招应届', 'position' => '应届产品经理', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届运营专员', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届新媒体运营', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届UI设计师', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届交互设计师', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '应届人力资源专员', 'industry' => '通用'],
            ['category' => '校招应届', 'position' => '应届财务专员', 'industry' => '财务'],
            ['category' => '校招应届', 'position' => '应届行政专员', 'industry' => '通用'],
            ['category' => '校招应届', 'position' => '应届管培生', 'industry' => '通用'],
            ['category' => '校招应届', 'position' => '开发实习生', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '产品实习生', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '设计实习生', 'industry' => '互联网'],
            ['category' => '校招应届', 'position' => '运营实习生', 'industry' => '互联网'],
        ];
    }

    private function buildTemplateName(string $category, string $style, string $level, int $serial): string
    {
        $suffix = sprintf('%03d', $serial);

        return "{$category}-{$style}-{$level}-模板{$suffix}";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultModuleBlueprint(
        string $position,
        string $moduleProfile = 'balanced',
        string $level = '社招1-3年',
        string $avatarUrl = ''
    ): array {
        $isGraduate = Str::contains($level, ['校招', '应届']);
        $experienceSubtitle = $isGraduate ? '实习/校园经历' : '工作经历';
        $experienceItems = $isGraduate
            ? [
                "XX公司 | {$position}实习生 | 2023.07-2023.12",
                '1. 参与业务需求分析与执行，按时完成分配任务。',
                '2. 配合导师完成数据整理与阶段复盘，持续优化效率。',
            ]
            : [
                "XX公司 | {$position} | 2022.07-至今",
                '1. 负责核心业务模块，明确目标并拆解执行路径。',
                '2. 建立数据化复盘机制，持续优化效率与质量。',
            ];

        $base = [
            [
                'type' => 'personal',
                'sort_order' => 0,
                'data' => [
                    'name' => '',
                    'phone' => '',
                    'email' => '',
                    'city' => '',
                    'avatar' => $avatarUrl,
                    'custom_fields' => [],
                ],
            ],
            [
                'type' => 'objective',
                'sort_order' => 1,
                'data' => [
                    'target_job' => $position,
                    'target_city' => '',
                    'salary' => '',
                    'content' => "期望应聘{$position}岗位，具备较强执行力与协同能力。",
                ],
            ],
            [
                'type' => 'education',
                'sort_order' => 2,
                'data' => [
                    'subtitle' => '教育背景',
                    'items' => ['XX大学 | XX专业 | 本科 | 2018.09-2022.06'],
                    'content' => '',
                ],
            ],
            [
                'type' => 'experience',
                'sort_order' => 3,
                'data' => [
                    'subtitle' => $experienceSubtitle,
                    'items' => $experienceItems,
                    'content' => '',
                ],
            ],
            [
                'type' => 'project',
                'sort_order' => 4,
                'data' => [
                    'subtitle' => '项目经验',
                    'items' => [
                        '项目A：从需求分析到上线全流程负责，最终达成预期业务目标。',
                    ],
                    'content' => '',
                ],
            ],
            [
                'type' => 'skill',
                'sort_order' => 5,
                'data' => [
                    'subtitle' => '技能特长',
                    'items' => ['沟通协作', '结构化思维', '数据分析', '项目推进'],
                    'content' => '',
                ],
            ],
            [
                'type' => 'summary',
                'sort_order' => 6,
                'data' => [
                    'subtitle' => '自我评价',
                    'content' => "聚焦{$position}方向，结果导向，具备快速学习与跨团队协作能力。",
                    'items' => [],
                ],
            ],
        ];

        if ($moduleProfile === 'achievement_first') {
            $base[3]['data']['items'] = [
                '1. 主导关键需求推进，按期上线并达成预期目标。',
                '2. 通过数据分析定位瓶颈，推动核心指标提升。',
                '3. 建立可复用协作机制，提升跨团队交付效率。',
            ];
            $base[0]['data']['custom_fields'] = [
                ['label' => '作品集', 'value' => 'portfolio.example.com'],
                ['label' => 'Github', 'value' => 'github.com/example'],
            ];
        } elseif ($moduleProfile === 'project_heavy') {
            $base[4]['sort_order'] = 3;
            $base[3]['sort_order'] = 4;
            $base[4]['data']['items'] = [
                '项目A：负责方案设计与落地，交付稳定版本。',
                '项目B：优化性能与体验，支撑业务增长场景。',
                '项目C：与跨部门协同推进，保障节点按时完成。',
            ];
            $base[1]['data']['content'] = "重点突出{$position}方向项目落地与成果沉淀。";
        } elseif ($moduleProfile === 'skill_focus') {
            $base[5]['sort_order'] = 3;
            $base[4]['sort_order'] = 5;
            $base[5]['data']['items'] = ['结构化表达', '跨团队协作', '数据分析', '项目管理', '快速学习'];
        } elseif ($moduleProfile === 'campus_potential') {
            $base[2]['data']['items'] = ['XX大学 | XX专业 | 本科 | 2020.09-2024.06', '成绩排名前 20%，连续获得奖学金'];
            $base[3]['data']['items'] = [
                "校园项目/实习 | {$position}方向",
                '1. 参与项目实施，承担需求整理与执行。',
                '2. 输出阶段性复盘材料，沉淀方法经验。',
            ];
            $base[6]['data']['content'] = "具备{$position}基础能力与成长潜力，学习主动性强，适应快。";
        } elseif ($moduleProfile === 'management_brief') {
            $base[1]['data']['content'] = "聚焦{$position}管理职责，擅长目标拆解、团队协作与结果落地。";
            $base[3]['data']['items'] = [
                '1. 负责团队目标管理与进度把控，提升交付稳定性。',
                '2. 优化协作流程，推动跨部门高效协同。',
                '3. 建立复盘机制，持续沉淀管理方法论。',
            ];
        } elseif ($moduleProfile === 'avatar_spotlight') {
            $base[0]['data']['custom_fields'] = [
                ['label' => '个人主页', 'value' => 'about.me/example'],
                ['label' => '求职状态', 'value' => '可立即入职'],
            ];
            $base[4]['sort_order'] = 2;
            $base[2]['sort_order'] = 4;
            $base[3]['sort_order'] = 5;
        } elseif ($moduleProfile === 'portfolio_split') {
            $base[4]['sort_order'] = 2;
            $base[5]['sort_order'] = 3;
            $base[3]['sort_order'] = 4;
            $base[4]['data']['items'] = [
                '项目A：负责完整方案设计与复盘，沉淀通用方法。',
                '项目B：主导关键模块实现，确保性能与稳定性。',
            ];
            $base[5]['data']['items'] = ['产品思维', '视觉表达', '沟通协同', '数据洞察', '执行推进'];
        } elseif ($moduleProfile === 'executive_banner') {
            $base[1]['data']['content'] = "擅长战略目标拆解、团队管理与业务推进，聚焦{$position}方向的结果交付。";
            $base[6]['data']['content'] = '管理跨度覆盖目标制定、过程跟踪、风险控制与组织协同。';
            $base[3]['data']['items'] = [
                '1. 组织跨团队协同推进重点项目，确保关键里程碑达成。',
                '2. 建立评估指标体系，持续优化团队执行效率。',
            ];
        } elseif ($moduleProfile === 'one_page_compact') {
            $base[1]['sort_order'] = 5;
            $base[4]['sort_order'] = 2;
            $base[5]['sort_order'] = 3;
            $base[3]['sort_order'] = 4;
            $base[4]['data']['items'] = [
                '项目A：主导关键模块建设，支撑业务从 0 到 1 快速落地。',
                '项目B：优化核心流程性能，提升用户体验与转化效率。',
            ];
            $base[5]['data']['items'] = ['关键能力1：结构化拆解', '关键能力2：高效协作', '关键能力3：数据驱动'];
            $base[6]['data']['content'] = '一页呈现核心亮点，突出可读性与关键信息密度。';
        } elseif ($moduleProfile === 'narrative_timeline') {
            $base[2]['sort_order'] = 1;
            $base[1]['sort_order'] = 2;
            $base[3]['sort_order'] = 3;
            $base[4]['sort_order'] = 4;
            $base[6]['sort_order'] = 5;
            $base[5]['sort_order'] = 6;
            $base[3]['data']['items'] = [
                '阶段一：快速熟悉业务并承接核心任务，保障交付质量。',
                '阶段二：主导流程优化，推动效率和结果双提升。',
                '阶段三：沉淀方法体系，复制到团队协作场景。',
            ];
            $base[6]['data']['content'] = "通过阶段化叙事展示{$position}成长路径与业务价值沉淀。";
        } elseif ($moduleProfile === 'result_dashboard') {
            $base[1]['sort_order'] = 0;
            $base[0]['sort_order'] = 1;
            $base[4]['sort_order'] = 2;
            $base[3]['sort_order'] = 3;
            $base[2]['sort_order'] = 4;
            $base[5]['sort_order'] = 5;
            $base[6]['sort_order'] = 6;
            $base[1]['data']['content'] = "聚焦{$position}核心结果：效率提升、质量稳定、业务增长。";
            $base[4]['data']['items'] = [
                '结果看板A：关键目标达成率持续提升，形成标准化方法。',
                '结果看板B：缩短交付周期并降低返工率，提升团队产能。',
                '结果看板C：驱动跨团队协同，确保关键里程碑按期完成。',
            ];
            $base[5]['data']['items'] = ['指标拆解', '复盘机制', '协同推进', '风险控制'];
        } elseif ($moduleProfile === 'skill_wall') {
            $base[5]['sort_order'] = 1;
            $base[4]['sort_order'] = 2;
            $base[3]['sort_order'] = 3;
            $base[1]['sort_order'] = 4;
            $base[6]['sort_order'] = 5;
            $base[5]['data']['subtitle'] = '核心技能矩阵';
            $base[5]['data']['items'] = [
                '业务理解',
                '数据分析',
                '方案设计',
                '跨团队协作',
                '项目推进',
                '风险控制',
            ];
            $base[1]['data']['content'] = "突出{$position}能力矩阵与可迁移方法。";
        } elseif ($moduleProfile === 'project_casebook') {
            $base[4]['sort_order'] = 1;
            $base[3]['sort_order'] = 2;
            $base[1]['sort_order'] = 3;
            $base[2]['sort_order'] = 4;
            $base[5]['sort_order'] = 5;
            $base[6]['sort_order'] = 6;
            $base[4]['data']['subtitle'] = '代表项目案例';
            $base[4]['data']['items'] = [
                '案例一：负责需求拆解、方案设计与上线复盘，形成可复制模板。',
                '案例二：主导关键模块迭代，稳定支撑高并发与复杂协同场景。',
                '案例三：推动流程优化，显著缩短交付周期并提升质量。',
            ];
            $base[3]['data']['items'] = [
                "XX公司 | {$position} | 2021.07-至今",
                '1. 聚焦重点项目推进与落地，保障业务稳定交付。',
                '2. 沉淀流程机制，持续提升团队协同效率。',
            ];
        } elseif ($moduleProfile === 'academic_track') {
            $base[2]['sort_order'] = 1;
            $base[5]['sort_order'] = 2;
            $base[4]['sort_order'] = 3;
            $base[3]['sort_order'] = 4;
            $base[1]['sort_order'] = 5;
            $base[6]['sort_order'] = 6;
            $base[2]['data']['items'] = [
                'XX大学 | XX专业 | 本科 | 2018.09-2022.06',
                '核心课程：算法设计、软件工程、统计学习',
                '奖项：校级奖学金 / 学科竞赛奖',
            ];
            $base[1]['data']['content'] = "强调{$position}方向的学习能力与成长曲线。";
            $base[6]['data']['content'] = '学习驱动、逻辑清晰，具备快速适应与持续迭代能力。';
        } elseif ($moduleProfile === 'consulting_story') {
            $base[1]['sort_order'] = 0;
            $base[6]['sort_order'] = 1;
            $base[3]['sort_order'] = 2;
            $base[4]['sort_order'] = 3;
            $base[5]['sort_order'] = 4;
            $base[2]['sort_order'] = 5;
            $base[1]['data']['content'] = "围绕{$position}场景，采用问题-分析-方案-结果的表达框架。";
            $base[6]['data']['subtitle'] = '方法论摘要';
            $base[6]['data']['content'] = '擅长结构化分析复杂问题，并将方案拆解为可执行动作。';
            $base[3]['data']['items'] = [
                '案例A：识别核心瓶颈，提出分阶段方案并推进落地。',
                '案例B：构建评估指标体系，持续跟踪与优化结果。',
            ];
        } elseif ($moduleProfile === 'capability_story') {
            $base[6]['sort_order'] = 1;
            $base[5]['sort_order'] = 2;
            $base[3]['sort_order'] = 3;
            $base[4]['sort_order'] = 4;
            $base[1]['sort_order'] = 5;
            $base[2]['sort_order'] = 6;
            $base[6]['data']['subtitle'] = '能力总览';
            $base[6]['data']['content'] = "围绕{$position}核心能力，按问题拆解、行动落地、结果复盘进行结构化表达。";
            $base[5]['data']['items'] = ['复杂问题拆解', '跨团队协同推进', '数据化复盘', '高效沟通表达'];
            $base[1]['data']['content'] = "强调{$position}所需方法论与业务理解能力。";
        } elseif ($moduleProfile === 'project_first_minimal') {
            $base[4]['sort_order'] = 1;
            $base[1]['sort_order'] = 2;
            $base[3]['sort_order'] = 3;
            $base[5]['sort_order'] = 4;
            $base[6]['sort_order'] = 5;
            $base[2]['sort_order'] = 6;
            $base[4]['data']['items'] = [
                '代表项目一：主导关键模块从设计到落地，保障高质量上线。',
                '代表项目二：优化核心流程与协作机制，提升整体交付效率。',
                '代表项目三：沉淀标准方案，支撑团队规模化复用。',
            ];
            $base[3]['data']['items'] = [
                "XX公司 | {$position} | 2022.07-至今",
                '1. 聚焦关键业务目标，推动项目稳定交付。',
                '2. 通过流程与机制优化，持续降低返工与沟通成本。',
            ];
        }

        return collect($base)->sortBy('sort_order')->values()->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function stylePacks(): array
    {
        return [
            ['style' => '头像主视觉', 'name_prefix' => '头像主视觉', 'template' => 'modern', 'theme' => 'purple', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'avatar_spotlight', 'avatar_url' => '/uploads/resume-templates/avatars/avatar-profile-a.svg', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '项目分栏', 'name_prefix' => '项目分栏', 'template' => 'timeline', 'theme' => 'coral', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'portfolio_split', 'avatar_url' => '/uploads/resume-templates/avatars/avatar-profile-b.svg', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-coral.svg'],
            ['style' => '管理横幅', 'name_prefix' => '管理横幅', 'template' => 'elegant', 'theme' => 'green', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'executive_banner', 'avatar_url' => '/uploads/resume-templates/avatars/avatar-profile-c.svg', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-green.svg'],
            ['style' => '一页速览', 'name_prefix' => '一页速览', 'template' => 'minimal', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'one_page_compact', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '叙事履历', 'name_prefix' => '叙事履历', 'template' => 'timeline', 'theme' => 'purple', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'narrative_timeline', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '业绩看板', 'name_prefix' => '业绩看板', 'template' => 'creative', 'theme' => 'orange', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'result_dashboard', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-orange.svg'],
            ['style' => '技能墙', 'name_prefix' => '技能墙', 'template' => 'modern', 'theme' => 'green', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'skill_wall', 'font_family' => 'pingfang', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-green.svg'],
            ['style' => '项目案例集', 'name_prefix' => '项目案例集', 'template' => 'creative', 'theme' => 'coral', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'project_casebook', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-coral.svg'],
            ['style' => '学术成长', 'name_prefix' => '学术成长', 'template' => 'minimal', 'theme' => 'purple', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'academic_track', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '咨询叙事', 'name_prefix' => '咨询叙事', 'template' => 'elegant', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'consulting_story', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '能力叙事', 'name_prefix' => '能力叙事', 'template' => 'modern', 'theme' => 'green', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'capability_story', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-green.svg'],
            ['style' => '项目前置', 'name_prefix' => '项目前置', 'template' => 'minimal', 'theme' => 'orange', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'project_first_minimal', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-orange.svg'],
            ['style' => '专业蓝', 'name_prefix' => '结构化通用', 'template' => 'classic', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'balanced', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '极简白', 'name_prefix' => '极简直投', 'template' => 'minimal', 'theme' => 'blue', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'balanced', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '成果导向', 'name_prefix' => '成果导向', 'template' => 'modern', 'theme' => 'purple', 'ats_level' => 'A', 'density' => '高', 'module_profile' => 'achievement_first', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '商务灰', 'name_prefix' => '管理简报', 'template' => 'elegant', 'theme' => 'green', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'management_brief', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-green.svg'],
            ['style' => '创意橙', 'name_prefix' => '项目强化', 'template' => 'creative', 'theme' => 'orange', 'ats_level' => 'A', 'density' => '高', 'module_profile' => 'project_heavy', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-orange.svg'],
            ['style' => '时间线', 'name_prefix' => '成长路径', 'template' => 'timeline', 'theme' => 'coral', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'balanced', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-coral.svg'],
            ['style' => '技能版', 'name_prefix' => '能力矩阵', 'template' => 'classic', 'theme' => 'green', 'ats_level' => 'A+', 'density' => '高', 'module_profile' => 'skill_focus', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-green.svg'],
            ['style' => '校招版', 'name_prefix' => '潜力表达', 'template' => 'minimal', 'theme' => 'purple', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'campus_potential', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '商务精英', 'name_prefix' => '商务精英', 'template' => 'professional', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'executive_banner', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '学术研究', 'name_prefix' => '学术研究', 'template' => 'academic', 'theme' => 'blue', 'ats_level' => 'A', 'density' => '高', 'module_profile' => 'academic_track', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '互联网风', 'name_prefix' => '互联网风', 'template' => 'internet', 'theme' => 'purple', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'result_dashboard', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '高管履历', 'name_prefix' => '高管履历', 'template' => 'executive', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'management_brief', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '创业者', 'name_prefix' => '创业者', 'template' => 'startup', 'theme' => 'coral', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'achievement_first', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-coral.svg'],
            ['style' => '校园清新', 'name_prefix' => '校园清新', 'template' => 'student', 'theme' => 'purple', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'campus_potential', 'font_family' => 'system', 'font_size' => 'small', 'line_height' => 'normal', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '医疗蓝', 'name_prefix' => '医疗专业', 'template' => 'medical', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'balanced', 'font_family' => 'system', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '教育暖', 'name_prefix' => '教育专业', 'template' => 'teacher', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'academic_track', 'font_family' => 'system', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '法律深蓝', 'name_prefix' => '法律专业', 'template' => 'lawyer', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'executive_banner', 'font_family' => 'serif', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '金融蓝', 'name_prefix' => '金融专业', 'template' => 'finance', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'achievement_first', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '咨询绿', 'name_prefix' => '咨询专业', 'template' => 'consulting', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'consulting_story', 'font_family' => 'system', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '设计粉', 'name_prefix' => '创意设计', 'template' => 'designer', 'theme' => 'blue', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'portfolio_split', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
            ['style' => '公职红', 'name_prefix' => '公职专业', 'template' => 'government', 'theme' => 'blue', 'ats_level' => 'A+', 'density' => '中', 'module_profile' => 'balanced', 'font_family' => 'system', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-blue.svg'],
            ['style' => '自由紫', 'name_prefix' => '自由职业', 'template' => 'freelancer', 'theme' => 'blue', 'ats_level' => 'A', 'density' => '中', 'module_profile' => 'achievement_first', 'font_family' => 'pingfang', 'font_size' => 'medium', 'line_height' => 'relaxed', 'preview_image_url' => '/uploads/resume-templates/covers/cover-purple.svg'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function baseProfiles(): array
    {
        return [
            ['category' => '技术', 'position' => 'Java后端工程师', 'industry' => '互联网'],
            ['category' => '技术', 'position' => '前端开发工程师', 'industry' => '互联网'],
            ['category' => '技术', 'position' => '全栈工程师', 'industry' => '互联网'],
            ['category' => '技术', 'position' => 'Python开发工程师', 'industry' => '数据智能'],
            ['category' => '技术', 'position' => 'Golang开发工程师', 'industry' => '互联网'],
            ['category' => '技术', 'position' => '算法工程师', 'industry' => '人工智能'],
            ['category' => '技术', 'position' => '测试开发工程师', 'industry' => '互联网'],
            ['category' => '技术', 'position' => 'DevOps工程师', 'industry' => '云计算'],
            ['category' => '技术', 'position' => '数据工程师', 'industry' => '数据智能'],
            ['category' => '技术', 'position' => '架构师', 'industry' => '互联网'],
            ['category' => '产品', 'position' => '产品经理', 'industry' => '互联网'],
            ['category' => '产品', 'position' => '高级产品经理', 'industry' => '互联网'],
            ['category' => '产品', 'position' => 'AI产品经理', 'industry' => '人工智能'],
            ['category' => '产品', 'position' => '增长产品经理', 'industry' => '互联网'],
            ['category' => '产品', 'position' => '产品总监', 'industry' => '互联网'],
            ['category' => '产品', 'position' => '项目经理', 'industry' => '企业服务'],
            ['category' => '设计', 'position' => 'UI设计师', 'industry' => '互联网'],
            ['category' => '设计', 'position' => 'UX设计师', 'industry' => '互联网'],
            ['category' => '设计', 'position' => '交互设计师', 'industry' => '互联网'],
            ['category' => '设计', 'position' => '视觉设计师', 'industry' => '品牌营销'],
            ['category' => '设计', 'position' => '品牌设计师', 'industry' => '品牌营销'],
            ['category' => '设计', 'position' => '设计主管', 'industry' => '互联网'],
            ['category' => '市场运营', 'position' => '市场营销', 'industry' => '消费'],
            ['category' => '市场运营', 'position' => '品牌营销经理', 'industry' => '品牌营销'],
            ['category' => '市场运营', 'position' => '新媒体运营', 'industry' => '互联网'],
            ['category' => '市场运营', 'position' => '内容运营', 'industry' => '互联网'],
            ['category' => '市场运营', 'position' => '增长运营', 'industry' => '互联网'],
            ['category' => '市场运营', 'position' => '电商运营', 'industry' => '电商'],
            ['category' => '市场运营', 'position' => 'SEO专员', 'industry' => '互联网'],
            ['category' => '市场运营', 'position' => '商务拓展BD', 'industry' => '互联网'],
            ['category' => '职能', 'position' => '人力资源专员', 'industry' => '通用'],
            ['category' => '职能', 'position' => '招聘专员', 'industry' => '通用'],
            ['category' => '职能', 'position' => '行政专员', 'industry' => '通用'],
            ['category' => '职能', 'position' => '财务专员', 'industry' => '财务'],
            ['category' => '职能', 'position' => '法务专员', 'industry' => '法务'],
            ['category' => '职能', 'position' => '采购专员', 'industry' => '供应链'],
            ['category' => '职能', 'position' => '客服专员', 'industry' => '通用'],
            ['category' => '校招实习', 'position' => '开发实习生', 'industry' => '互联网'],
            ['category' => '校招实习', 'position' => '产品实习生', 'industry' => '互联网'],
            ['category' => '校招实习', 'position' => '设计实习生', 'industry' => '互联网'],
            ['category' => '校招实习', 'position' => '运营实习生', 'industry' => '互联网'],
            ['category' => '校招实习', 'position' => '人事实习生', 'industry' => '通用'],
            ['category' => '校招实习', 'position' => '财务实习生', 'industry' => '财务'],
            ['category' => '校招实习', 'position' => '校招应届生', 'industry' => '通用'],
            ['category' => '医疗健康', 'position' => '临床医师', 'industry' => '医疗'],
            ['category' => '医疗健康', 'position' => '护士/护师', 'industry' => '医疗'],
            ['category' => '医疗健康', 'position' => '药剂师', 'industry' => '医药'],
            ['category' => '医疗健康', 'position' => '医学研究员', 'industry' => '医药'],
            ['category' => '医疗健康', 'position' => '健康管理师', 'industry' => '医疗'],
            ['category' => '医疗健康', 'position' => '医疗器械工程师', 'industry' => '医疗器械'],
            ['category' => '教育', 'position' => '中小学教师', 'industry' => '教育'],
            ['category' => '教育', 'position' => '高校教师/讲师', 'industry' => '教育'],
            ['category' => '教育', 'position' => '教育培训讲师', 'industry' => '教育培训'],
            ['category' => '教育', 'position' => '课程设计师', 'industry' => '教育培训'],
            ['category' => '教育', 'position' => '教务管理', 'industry' => '教育'],
            ['category' => '法律', 'position' => '律师', 'industry' => '法律'],
            ['category' => '法律', 'position' => '法务经理', 'industry' => '法律'],
            ['category' => '法律', 'position' => '合规专员', 'industry' => '法律'],
            ['category' => '法律', 'position' => '知识产权专员', 'industry' => '法律'],
            ['category' => '金融', 'position' => '投资分析师', 'industry' => '金融'],
            ['category' => '金融', 'position' => '风控经理', 'industry' => '金融'],
            ['category' => '金融', 'position' => '基金经理', 'industry' => '金融'],
            ['category' => '金融', 'position' => '审计师', 'industry' => '金融'],
            ['category' => '金融', 'position' => '信贷经理', 'industry' => '金融'],
            ['category' => '金融', 'position' => '精算师', 'industry' => '保险'],
            ['category' => '咨询', 'position' => '管理咨询顾问', 'industry' => '咨询'],
            ['category' => '咨询', 'position' => '战略咨询顾问', 'industry' => '咨询'],
            ['category' => '咨询', 'position' => 'IT咨询顾问', 'industry' => '咨询'],
            ['category' => '咨询', 'position' => '人力资源咨询顾问', 'industry' => '咨询'],
            ['category' => '设计', 'position' => '插画师', 'industry' => '创意'],
            ['category' => '设计', 'position' => '动效设计师', 'industry' => '互联网'],
            ['category' => '设计', 'position' => '3D建模师', 'industry' => '创意'],
            ['category' => '设计', 'position' => '游戏UI设计师', 'industry' => '游戏'],
            ['category' => '公职', 'position' => '公务员', 'industry' => '政府'],
            ['category' => '公职', 'position' => '事业单位职员', 'industry' => '政府'],
            ['category' => '公职', 'position' => '社区工作者', 'industry' => '政府'],
            ['category' => '自由职业', 'position' => '自由撰稿人', 'industry' => '传媒'],
            ['category' => '自由职业', 'position' => '独立开发者', 'industry' => '互联网'],
            ['category' => '自由职业', 'position' => '自由设计师', 'industry' => '创意'],
            ['category' => '自由职业', 'position' => '翻译', 'industry' => '语言服务'],
            ['category' => '自由职业', 'position' => '摄影师', 'industry' => '传媒'],
            ['category' => '建筑地产', 'position' => '建筑设计师', 'industry' => '建筑'],
            ['category' => '建筑地产', 'position' => '土木工程师', 'industry' => '建筑'],
            ['category' => '建筑地产', 'position' => '室内设计师', 'industry' => '建筑'],
            ['category' => '建筑地产', 'position' => '工程造价师', 'industry' => '建筑'],
            ['category' => '制造', 'position' => '机械工程师', 'industry' => '制造'],
            ['category' => '制造', 'position' => '电气工程师', 'industry' => '制造'],
            ['category' => '制造', 'position' => '质量工程师', 'industry' => '制造'],
            ['category' => '制造', 'position' => '供应链经理', 'industry' => '制造'],
            ['category' => '传媒', 'position' => '记者/编辑', 'industry' => '传媒'],
            ['category' => '传媒', 'position' => '编导', 'industry' => '传媒'],
            ['category' => '传媒', 'position' => '短视频运营', 'industry' => '传媒'],
            ['category' => '传媒', 'position' => '公关经理', 'industry' => '公关'],
            ['category' => '物流', 'position' => '物流经理', 'industry' => '物流'],
            ['category' => '物流', 'position' => '仓储主管', 'industry' => '物流'],
            ['category' => '餐饮酒店', 'position' => '酒店管理', 'industry' => '酒店'],
            ['category' => '餐饮酒店', 'position' => '餐饮经理', 'industry' => '餐饮'],
            ['category' => '餐饮酒店', 'position' => '厨师长', 'industry' => '餐饮'],
        ];
    }
}
