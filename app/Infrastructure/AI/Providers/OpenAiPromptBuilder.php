<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Providers;

use App\Services\Resume\PromptStrategy\PromptInstructionBuilder;
use App\Services\Resume\PromptStrategy\PromptStrategyResolver;
use Illuminate\Support\Facades\Log;

final class OpenAiPromptBuilder
{
    /**
     * @param  array<int,string>  $goals
     */
    public function buildOptimizeSystemPrompt(array $goals, string $mode, string $templateKey = '', string $content = '', string $targetJob = '', string $jobDescription = ''): string
    {
        $goalMap = [
            'ats_keywords' => '【ATS 关键词优化】从目标岗位描述中提取 8-12 个核心关键词（硬技能、工具、方法论），自然嵌入工作经历和项目描述中，确保每个核心关键词至少在经历描述中出现 1 次。',
            'structure' => '【结构优化】调整简历布局，使模块层次分明：模块标题 → 机构/公司 → 时间地点 → 条目列表。突出与目标岗位最相关的内容前置。',
            'quantified' => '【量化成果强制】优先提取原始简历中已有的客观数字（性能、效率、规模、成本、时延等）。若原文无明确数据，不得编造具体数值，可改为"可补充指标建议"或保守表述。',
            'skill_match' => '【技能匹配】强化与目标岗位的技能关联，删除过于基础的技能描述（如"熟练使用 Office"对技术岗位而言），补充目标岗位明确要求但简历中缺失的技能表述。',
            'language' => '【语言表达】用行业标准术语替换口语化表达，统一日期格式为 YYYY.MM，删除所有无证据支撑的主观评价（"性格开朗"、"吃苦耐劳"、"学习能力强"）。',
            'highlights' => '【亮点提炼】凸显候选人的独特价值：技术深度、业务影响力、团队贡献、创新成果。若原始简历有可挖掘的亮点（如开源贡献、专利、演讲），要重点放大。在 highlights 中提炼 3-5 个最具说服力的核心卖点。',
            'tailor_job' => '【岗位定制】围绕 JD 的核心职责重排内容，优先展示强相关经历；每段经历首条突出与目标岗位直接匹配的任务与结果。',
            'concise' => '【精简降噪】删除重复和低信息密度描述，控制单条经历在 1-2 句高价值表达，避免泛泛而谈与模板化空话。',
            'authenticity' => '【真实性增强】避免夸张与不可验证表述，优先使用可核验事实（项目规模、职责范围、客观结果），不确定信息用保守措辞，禁止杜撰经历与成果。',
            'readability' => '【可读性优化】优化段落长度与句式节奏，保持关键词自然分布，保证招聘者在 30 秒内快速抓住岗位匹配点。',
            'industry_fit' => '【行业适配优化】根据目标行业调整简历用语风格与侧重点：互联网行业突出数据驱动与迭代速度，国企/体制内突出规范执行与组织协调，外企突出跨文化协作与流程合规，金融行业突出风控意识与合规表述，制造业突出精益改善与质量管控。识别 JD 中的行业信号词并匹配对应表达范式。',
            'career_pivot' => '【跨职能转型优化】针对转行/跨职能求职场景，重点强化可迁移能力（项目管理、数据分析、跨团队协作、快速学习）的表达，将原职能经验重新框架化为目标岗位可理解的贡献。用"在 X 场景中运用了 Y 能力，产出 Z 结果"的格式桥接两个职能领域，避免使用仅原行业理解的术语。',
            'leadership' => '【领导力展示优化】突出管理决策与团队影响力：将"负责 XX"改写为"主导 XX 决策，带领 N 人团队达成 XX 目标"；提炼战略规划、资源协调、人才培养、组织变革等维度的事实证据；每段管理经历至少包含一个可验证的团队/业务成果指标。注意：仅基于已有事实提炼，不得编造管理职责。',
            'i18n_expression' => '【国际化表达优化】优化中英双语表达与文化适配：为关键术语提供标准英文对照（如"微服务架构 → Microservices Architecture"）；调整表述风格符合目标地区职场文化（如北美强调个人贡献与量化结果，欧洲强调协作与流程规范）；若目标岗位为外企或海外岗位，优先使用英文行业术语并确保表达简洁直接。',
            'project_impact' => '【项目影响力优化】突出项目规模、影响范围与业务价值：为每个项目补充用户量/营收/覆盖率等影响指标；用"影响 N 万用户/节省 N 万成本/提升 N% 效率"量化项目贡献；区分个人贡献与团队成果，明确标注"主导/核心参与/协助"角色；将技术成果翻译为业务语言（如"优化 SQL 查询"→"将报表生成时间从 30 分钟降至 3 秒，提升运营决策效率"）。',
            'tech_depth' => '【技术深度优化】强化技术方案选型与架构决策表达：为技术选型补充决策理由（"选用 Kafka 而非 RabbitMQ，因需要高吞吐与持久化回放"）；突出架构设计中的权衡取舍（一致性 vs 可用性、性能 vs 可维护性）；展示技术难点攻克过程（问题→方案→验证→结果）；补充系统规模指标（QPS、数据量、并发数）。',
            'cross_cultural' => '【跨文化协作优化】突出多元团队协作与跨文化沟通能力：展示与海外/跨国团队协作经历（时区协调、文化差异处理、远程沟通）；突出多语言工作能力（中英双语会议、英文文档撰写、海外客户对接）；展示跨文化项目成果（"协调中美 3 地团队，在 2 个月内完成产品全球化上线"）；强调文化敏感性与适应力。',
            'certification' => '【资质认证优化】突出专业认证、资质与行业认可：将专业认证（PMP/CPA/CFA/AWS/CISSP 等）置于技能或资质模块醒目位置；在经历描述中关联认证知识的应用（"运用 PMP 方法论管理跨部门项目"）；补充行业认可（专利、论文、行业奖项、开源贡献）；若认证与目标岗位强相关，在摘要中提及。',
            'innovation' => '【创新能力优化】突出创新实践、技术突破与行业贡献：展示从 0 到 1 的创新成果（新产品/新方案/新流程的发起与落地）；突出技术突破（"首次在团队引入 XX 技术，解决 YY 痛点"）；展示创新方法论（设计思维、敏捷实验、A/B 测试验证）；补充行业贡献（开源项目、技术分享、行业标准参与）。',
            'data_driven' => '【数据驱动优化】强化数据思维与指标导向的表达：将主观描述改为指标驱动（"提升了用户体验"→"NPS 从 30 提升至 52"）；为每个业务成果补充数据佐证（转化率、留存率、ROI、成本节约）；展示数据分析能力（A/B 测试、漏斗分析、归因分析）；在决策描述中体现数据依据（"基于用户行为数据分析，发现 XX 痛点，推动 XX 改进"）。',
        ];

        $goalSections = [];
        foreach ($goalMap as $key => $text) {
            if (in_array($key, $goals, true)) {
                $goalSections[] = $text;
            }
        }
        if (empty($goalSections)) {
            foreach ($goalMap as $text) {
                $goalSections[] = $text;
            }
        }

        $goalsText = implode("\n", $goalSections);
        $conflictRules = $this->resolveOptimizeGoalConflicts($goals);
        if ($conflictRules !== []) {
            $goalsText .= "\n【冲突消解规则】\n".implode("\n", $conflictRules);
        }

        $templateHint = $this->buildTemplateSpecificHint($templateKey, $goals);
        if ($templateHint !== '') {
            $goalsText .= "\n\n【岗位策略差异化指令】\n".$templateHint;
        }

        $modeMap = [
            'quick' => '【快速模式】轻量优化，优先保留原始事实与结构，仅做必要措辞修正与关键词补强。',
            'balanced' => '【平衡模式】在真实性优先前提下做适度重写，重点优化关键词、结构和可读性。',
            'deep' => '【深度模式】在事实边界内进行深度重构，可调整表达顺序，但不得新增未经证实事实。',
        ];
        $modeText = $modeMap[$mode] ?? $modeMap['balanced'];

        return <<<PROMPT
你是一位专业的简历优化助手，目标是在"真实可信"的前提下提升岗位匹配度与可读性。

你的优化必须同时满足两个目标：
① 让 ATS 系统高分通过（关键词密度、格式规范、结构清晰）
② 让人类面试官眼前一亮（量化成果、差异化亮点、专业表达）

━━━ 本次用户选择的优化重点 ━━━
{$goalsText}

━━━ 本次优化模式 ━━━
{$modeText}

━━━ 通用执行规则 ━━━
【STAR 法则重构】
- 所有经历条目必须重写为：在[什么情境]下，[承担什么任务]，[采取什么行动]，[达成什么结果]。
- 示例：将"负责 XX 系统开发"改为"在 XX 业务场景中承担 XX 模块开发，采用 XX 方案优化 XX 问题，结果为 XX（以原文可验证信息为准）"。

【冗余剔除】
- 删除与目标岗位完全无关的经历（如应聘后端开发却保留超市促销经历）。

【专业度提升】
- 确保排版层次分明：模块标题 → 机构/公司 → 时间地点 → 条目列表。

【真实性底线】
- 严禁编造未出现的公司、项目、角色、成果和具体数值。
- 若原文缺少量化数据，仅做"可补充指标建议"，不要伪造数字。
- 禁止删除用户已有的量化数据（如原文写了"性能提升 80%"，优化后必须保留该数据）。
- 禁止修改公司名称、职位名称、任职时间线。
- 禁止添加用户未提及的技术栈到经历描述中（可在技能列表建议中补充）。
- 保留原文中所有已有的关键词，不得因精简而丢失核心技能词。

返回格式（严格 JSON）：
{
    "optimized_text": "完整优化后的简历正文，使用 Markdown 格式排版",
    "highlights": ["核心卖点1", "核心卖点2", "核心卖点3"],
    "changes_summary": "简要说明优化了哪些方面",
    "gap_actions": [
        {"module": "skill", "action": "add", "content": "建议补充 Docker 容器化经验"},
        {"module": "project", "action": "enhance", "content": "项目描述缺少量化数据，建议补充性能提升百分比"}
    ]
}
gap_actions 为可选字段，仅在发现明显缺口时返回，每条包含目标模块、动作类型（add/enhance/rewrite）和具体建议。

{$this->resolveLanguageInstruction($content, $targetJob, $jobDescription)}
PROMPT;
    }

    /**
     * @param  array<string,mixed>  $options
     */
    public function buildOptimizeUserPrompt(string $content, string $targetJob, array $options): string
    {
        $company = is_string($options['target_company'] ?? null) ? trim($options['target_company']) : '';
        $jobTitle = is_string($options['target_job_title'] ?? null) ? trim($options['target_job_title']) : '';
        $jobDescription = is_string($options['target_job_description'] ?? null) ? trim($options['target_job_description']) : '';
        $promptStrategyTemplate = is_string($options['prompt_strategy_template'] ?? null) ? trim($options['prompt_strategy_template']) : '';
        $focusKeywords = is_array($options['focus_keywords'] ?? null) ? $options['focus_keywords'] : [];
        $focusKeywords = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $focusKeywords), static fn (string $item): bool => $item !== ''));
        $resumeProfile = is_array($options['resume_profile'] ?? null) ? $options['resume_profile'] : [];
        $moduleStrategies = is_array($options['module_strategies'] ?? null) ? $options['module_strategies'] : [];
        $careerTrackKeywords = is_array($options['career_track_keywords'] ?? null) ? $options['career_track_keywords'] : [];
        $careerTrackAvoidWords = is_array($options['career_track_avoid_words'] ?? null) ? $options['career_track_avoid_words'] : [];
        $careerTrackFocus = is_array($options['career_track_focus'] ?? null) ? $options['career_track_focus'] : [];

        $parts = [];
        $parts[] = '目标职位：'.($targetJob !== '' ? $targetJob : '未指定');

        if ($company !== '') {
            $parts[] = '目标公司：'.$company;
            $companyHints = $this->resolveCompanyHints($company, $targetJob);
            if ($companyHints !== '') {
                $parts[] = '公司画像参考：'.$companyHints;
            }
        }
        if ($jobTitle !== '') {
            $parts[] = '目标岗位名称：'.$jobTitle;
        }
        if ($jobDescription !== '') {
            $parts[] = "目标岗位描述/JD：\n{$jobDescription}";
        }
        if ($promptStrategyTemplate !== '') {
            $parts[] = '策略模板：'.$promptStrategyTemplate;
        }

        $isFreshGraduate = $this->isFreshGraduateOptimizeScenario($content, $targetJob, $jobTitle, $jobDescription, $promptStrategyTemplate);

        try {
            /** @var PromptStrategyResolver $resolver */
            $resolver = app(PromptStrategyResolver::class);
            /** @var PromptInstructionBuilder $builder */
            $builder = app(PromptInstructionBuilder::class);
            $resolved = $resolver->resolve($promptStrategyTemplate);
            $lines = $builder->buildLines($resolved);
            if ($lines !== []) {
                $parts[] = "结构化策略包：\n- ".implode("\n- ", $lines);
            }
        } catch (\Throwable $e) {
            Log::debug('Prompt strategy resolution failed', ['error' => $e->getMessage()]);
        }

        if (! empty($focusKeywords)) {
            $parts[] = '重点匹配关键词：'.implode('、', $focusKeywords);
        }
        // 赛道专属优化指导
        if ($careerTrackFocus !== [] || $careerTrackKeywords !== [] || $careerTrackAvoidWords !== []) {
            $trackLines = [];
            if ($careerTrackFocus !== []) {
                $trackLines[] = '优化侧重点：'.implode('、', array_slice($careerTrackFocus, 0, 8));
            }
            if ($careerTrackKeywords !== []) {
                $trackLines[] = '赛道核心关键词：'.implode('、', array_slice($careerTrackKeywords, 0, 15));
            }
            if ($careerTrackAvoidWords !== []) {
                $trackLines[] = '避坑词汇（优化时避免使用）：'.implode('、', array_slice($careerTrackAvoidWords, 0, 10));
            }
            if ($trackLines !== []) {
                $parts[] = "赛道专属优化指导：\n- ".implode("\n- ", $trackLines);
            }
        }
        if ($isFreshGraduate) {
            $parts[] = "应届生优化建议：\n- 优先突出课程项目、毕业设计、实习经历，按岗位相关度排序。\n- 描述以可验证事实为主，缺少数字时给".'"待补充指标建议"，不要编造。'."\n- 强调学习能力与上手速度，但避免".'"独立主导全链路"类过度表述。';
        }
        if (! empty($moduleStrategies)) {
            $strategyLabels = [
                'balanced' => '平衡优化',
                'results' => '结果导向',
                'technical' => '技术细节',
            ];
            $strategyRows = [];
            foreach ($moduleStrategies as $moduleKey => $strategy) {
                $module = trim((string) $moduleKey);
                $strategyKey = trim((string) $strategy);
                if ($module === '' || $strategyKey === '') {
                    continue;
                }
                $strategyRows[] = sprintf('%s：%s', $module, $strategyLabels[$strategyKey] ?? $strategyKey);
            }
            if ($strategyRows !== []) {
                $parts[] = "模块策略偏好：\n- ".implode("\n- ", array_slice($strategyRows, 0, 16));
            }
        }
        if (! empty($resumeProfile)) {
            $profileLines = [];
            if (is_numeric($resumeProfile['estimated_optimize_score'] ?? null)) {
                $profileLines[] = '当前估算分：'.(int) $resumeProfile['estimated_optimize_score'];
            }
            if (is_numeric($resumeProfile['module_overall_score'] ?? null)) {
                $profileLines[] = '模块总分：'.(int) $resumeProfile['module_overall_score'];
            }
            if (is_numeric($resumeProfile['quantified_ratio'] ?? null)) {
                $profileLines[] = '量化条目占比：'.(int) $resumeProfile['quantified_ratio'].'%';
            }
            if (is_numeric($resumeProfile['keyword_hit'] ?? null) && is_numeric($resumeProfile['keyword_total'] ?? null)) {
                $profileLines[] = '关键词命中：'.(int) $resumeProfile['keyword_hit'].'/'.(int) $resumeProfile['keyword_total'];
            }
            if (is_array($resumeProfile['weak_modules'] ?? null) && ! empty($resumeProfile['weak_modules'])) {
                $profileLines[] = '短板模块：'.implode('、', array_map(static fn ($item): string => (string) $item, $resumeProfile['weak_modules']));
            }
            if (is_array($resumeProfile['keyword_missing'] ?? null) && ! empty($resumeProfile['keyword_missing'])) {
                $profileLines[] = '优先补齐关键词：'.implode('、', array_map(static fn ($item): string => (string) $item, $resumeProfile['keyword_missing']));
            }
            if (! empty($profileLines)) {
                $parts[] = "简历画像信号：\n- ".implode("\n- ", $profileLines);
            }
        }

        $parts[] = "\n原始简历内容：\n{$content}";

        $benchmarkHints = $this->resolveBenchmarkHints($targetJob);
        if ($benchmarkHints !== '') {
            $parts[] = '同岗位参考信号：'.$benchmarkHints;
        }

        return implode("\n", $parts);
    }

    public function isFreshGraduateOptimizeScenario(
        string $content,
        string $targetJob,
        string $jobTitle,
        string $jobDescription,
        string $promptStrategyTemplate
    ): bool {
        if (trim($promptStrategyTemplate) === 'fresh_graduate') {
            return true;
        }

        $targetText = mb_strtolower(trim($targetJob.' '.$jobTitle.' '.$jobDescription));
        $signals = ['应届', '校招', '毕业生', 'new grad', 'fresh graduate', '实习生'];
        foreach ($signals as $signal) {
            if (str_contains($targetText, $signal)) {
                return true;
            }
        }

        return str_contains($content, '实习') && ! str_contains($content, '管理团队');
    }

    /**
     * @param  array<int,mixed>  $goals
     * @return array<int,string>
     */
    public function resolveOptimizeGoalConflicts(array $goals): array
    {
        $goalSet = array_fill_keys(array_values(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $goals),
            static fn (string $item): bool => $item !== ''
        )), true);
        $rules = [];
        $matrix = config('resume.optimize_session.goal_conflicts', []);
        $matched = [];

        foreach ($matrix as $row) {
            $requiredGoals = is_array($row['goals'] ?? null) ? $row['goals'] : [];
            if ($requiredGoals === []) {
                continue;
            }
            $isMatched = true;
            foreach ($requiredGoals as $requiredGoal) {
                $goal = trim((string) $requiredGoal);
                if ($goal === '' || ! isset($goalSet[$goal])) {
                    $isMatched = false;
                    break;
                }
            }
            if (! $isMatched) {
                continue;
            }
            $matched[] = [
                'priority' => (int) ($row['priority'] ?? 0),
                'rule' => trim((string) ($row['rule'] ?? '')),
            ];
        }

        usort($matched, static fn (array $a, array $b): int => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
        foreach ($matched as $item) {
            if (($item['rule'] ?? '') === '') {
                continue;
            }
            $rules[] = '- '.$item['rule'];
        }

        return $rules;
    }

    private function resolveCompanyHints(string $company, string $targetJob): string
    {
        $companyLower = mb_strtolower($company);
        $hints = [];

        $techGiants = [
            '阿里' => '互联网大厂，重视技术深度与业务理解，面试看重系统设计和项目复杂度',
            '腾讯' => '互联网大厂，重视用户体验和技术实现，面试看重项目细节和编程基础',
            '字节' => '互联网大厂，重视工程效率和数据驱动，面试看重算法和系统设计',
            '百度' => '互联网大厂，重视AI/搜索技术，面试看重算法能力和技术深度',
            '美团' => '互联网大厂，重视业务理解和技术落地，面试看重项目复杂度和解决方案',
            '华为' => '科技企业，重视技术深度和执行力，面试看重专业知识和项目经验',
            '小米' => '科技企业，重视产品思维和快速迭代，面试看重项目落地能力',
            '京东' => '电商大厂，重视供应链和物流技术，面试看重业务理解和系统稳定性',
            '网易' => '互联网公司，重视游戏和教育技术，面试看重创意和技术实现',
            '快手' => '短视频平台，重视推荐算法和音视频技术，面试看重数据处理能力',
            'google' => '全球科技巨头，重视算法和系统设计，面试看重技术深度和创新思维',
            'microsoft' => '全球科技巨头，重视云计算和企业服务，面试看重架构设计和工程实践',
            'amazon' => '全球电商/云巨头，重视领导力原则和大规模系统，面试看重STAR表达和系统设计',
            'meta' => '全球社交巨头，重视大规模分布式系统，面试看重技术深度和产品思维',
            'apple' => '全球科技巨头，重视硬件软件结合和用户体验，面试看重细节和创新',
        ];

        foreach ($techGiants as $key => $hint) {
            if (str_contains($companyLower, $key) || str_contains(mb_strtolower($key), $companyLower)) {
                $hints[] = $hint;
                break;
            }
        }

        $industryHints = [
            '银行' => '金融行业，重视数据安全和系统稳定性，强调合规和风控经验',
            '证券' => '金融行业，重视高频交易和数据处理，强调性能优化和低延迟',
            '保险' => '金融行业，重视精算和风险评估，强调数据分析和业务理解',
            '基金' => '金融行业，重视量化分析和投资决策，强调数据处理和算法能力',
            '医院' => '医疗行业，重视数据隐私和系统可靠性，强调合规和安全经验',
            '制药' => '医疗行业，重视临床数据和研发流程，强调数据处理和质量管理',
            '教育' => '教育行业，重视用户体验和内容管理，强调互动性和可访问性',
            '游戏' => '游戏行业，重视性能优化和实时渲染，强调图形学和网络编程',
            '电商' => '电商行业，重视高并发和库存管理，强调系统可用性和支付安全',
            '物流' => '物流行业，重视路径优化和实时追踪，强调算法和数据处理',
            '制造' => '制造业，重视自动化和质量控制，强调嵌入式和物联网经验',
        ];

        foreach ($industryHints as $key => $hint) {
            if (str_contains($companyLower, $key)) {
                $hints[] = $hint;
                break;
            }
        }

        return implode('；', $hints);
    }

    private function resolveLanguageInstruction(string $content, string $targetJob, string $jobDescription): string
    {
        $allText = $content . ' ' . $targetJob . ' ' . $jobDescription;
        $chineseChars = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $allText);
        $totalChars = mb_strlen(preg_replace('/\s+/', '', $allText));
        $ratio = $totalChars > 0 ? $chineseChars / $totalChars : 1;

        if ($ratio < 0.15) {
            return "\n【语言要求】The resume and target job are in English. Optimize using professional English resume conventions: use action verbs, quantify achievements with metrics, maintain consistent tense, follow Western resume formatting standards. Return optimized_text in English.";
        }

        return '';
    }

    private function buildTemplateSpecificHint(string $templateKey, array $goals): string
    {
        if ($templateKey === '') {
            return '';
        }

        $hints = [
            'backend' => [
                'default' => '后端岗位重点：强化技术栈关键词密度（框架、数据库、中间件、部署工具），项目经历用"问题-方案-结果"结构，突出系统设计决策和性能指标。',
                'ats_keywords' => '后端 ATS 关键词侧重：框架名（Spring/Laravel/Django）、数据库（MySQL/Redis/MongoDB）、中间件（Kafka/RabbitMQ）、部署工具（Docker/K8s）、协议（REST/gRPC），确保每项在经历中至少出现 1 次。',
                'skill_match' => '后端技能匹配侧重：按 JD 技术栈逐项对齐，缺失技能在 gap_actions 中建议补充，删除"熟练 Office"等无关技能。',
                'quantified' => '后端量化侧重：优先提取 QPS、响应时间、可用性（99.9%）、数据规模、成本节约等运维/性能指标。',
                'highlights' => '后端亮点侧重：突出系统设计决策、性能优化成果、技术选型理由、线上故障排查经验。',
            ],
            'frontend' => [
                'default' => '前端岗位重点：强化 UI/UX 技术栈和工程化能力，项目经历突出组件设计、性能优化和用户体验提升。',
                'ats_keywords' => '前端 ATS 关键词侧重：框架（React/Vue/Angular）、构建工具（Vite/Webpack）、状态管理、CSS 方案、测试框架。',
                'skill_match' => '前端技能匹配侧重：按 JD 前端技术栈对齐，补充缺失的工程化/性能优化技能表述。',
                'highlights' => '前端亮点侧重：突出组件库建设、性能优化（首屏/FCP/LCP）、跨端方案、设计系统贡献。',
            ],
            'fullstack' => [
                'default' => '全栈岗位重点：平衡前后端技术深度，突出端到端交付能力和技术选型决策。',
                'skill_match' => '全栈技能匹配侧重：同时覆盖前端框架、后端框架、数据库、部署工具，展示端到端技术视野。',
            ],
            'mobile' => [
                'default' => '移动端岗位重点：强化平台技术栈和性能优化经验，突出跨端方案和用户量级。',
                'ats_keywords' => '移动端 ATS 关键词侧重：平台（Android/iOS/Flutter/RN）、性能优化、崩溃率、包体积、热修复。',
                'quantified' => '移动端量化侧重：优先提取 DAU/MAU、崩溃率、启动速度、包体积、ANR 率等移动端指标。',
            ],
            'qa_test' => [
                'default' => '测试岗位重点：强化测试策略和自动化能力，突出质量指标和测试覆盖率。',
                'quantified' => '测试量化侧重：优先提取测试覆盖率、缺陷发现率、自动化比例、回归效率提升等指标。',
            ],
            'devops_sre' => [
                'default' => '运维/DevOps 岗位重点：强化基础设施和自动化能力，突出系统可用性和运维效率。',
                'ats_keywords' => 'DevOps ATS 关键词侧重：CI/CD、容器化、监控告警、日志分析、IaC、云平台。',
                'quantified' => 'DevOps 量化侧重：优先提取部署频率、MTTR、可用性（SLA）、资源利用率、自动化覆盖率。',
            ],
            'data_ai' => [
                'default' => '数据/AI 岗位重点：强化算法和数据处理能力，突出模型效果和业务价值。',
                'ats_keywords' => '数据/AI ATS 关键词侧重：算法名称、框架（PyTorch/TensorFlow）、数据处理工具、模型部署方案。',
                'quantified' => '数据/AI 量化侧重：优先提取模型精度提升、推理时延降低、数据处理效率、A/B 实验效果等指标。',
            ],
            'security' => [
                'default' => '安全岗位重点：强化安全攻防和合规能力，突出漏洞发现和安全体系建设。',
                'ats_keywords' => '安全 ATS 关键词侧重：渗透测试、漏洞扫描、零信任、WAF、等保合规、安全加固。',
                'quantified' => '安全量化侧重：优先提取漏洞发现数量、修复率、安全事件响应时间、合规通过率。',
            ],
            'product' => [
                'default' => '产品岗位重点：强化需求分析和业务决策能力，突出产品成果和用户增长。',
                'highlights' => '产品亮点侧重：突出需求-方案-落地-复盘链路，强调数据驱动决策和跨团队推动力。',
                'quantified' => '产品量化侧重：优先提取 DAU 增长、转化率提升、留存改善、NPS 变化等业务指标。',
                'tailor_job' => '产品岗位定制侧重：按 JD 职责重排产品经历，优先展示与目标岗位直接匹配的产品线和用户规模。',
            ],
            'design' => [
                'default' => '设计岗位重点：强化设计系统和用户体验能力，突出设计成果和用户反馈。',
                'highlights' => '设计亮点侧重：突出设计系统建设、用户研究洞察、A/B 实验设计、跨端一致性方案。',
            ],
            'operations' => [
                'default' => '运营岗位重点：强化增长和转化能力，突出运营闭环和数据复盘。',
                'quantified' => '运营量化侧重：优先提取转化率、留存率、GMV、ROI、拉新成本等运营指标。',
                'highlights' => '运营亮点侧重：突出增长策略、活动策划闭环、数据驱动优化、跨部门协作成果。',
            ],
            'marketing' => [
                'default' => '市场岗位重点：强化品牌传播和投放能力，突出 ROI 和市场影响力。',
                'quantified' => '市场量化侧重：优先提取 ROI、曝光量、获客成本、品牌认知度提升等市场指标。',
            ],
            'sales' => [
                'default' => '销售岗位重点：强化客户拓展和商务谈判能力，突出业绩达成和大客户管理。',
                'quantified' => '销售量化侧重：优先提取签约金额、客户数量、续约率、客单价、业绩完成率等销售指标。',
            ],
            'hr_admin' => [
                'default' => '人力行政岗位重点：强化组织管理和流程优化能力，突出人才盘点和制度建设成果。',
                'quantified' => '人力行政量化侧重：优先提取招聘完成率、培训覆盖率、员工满意度、流程效率提升等指标。',
            ],
            'finance_legal' => [
                'default' => '财务法务岗位重点：强化合规风控和数据分析能力，突出内控审计和成本控制成果。',
                'quantified' => '财务法务量化侧重：优先提取成本节约、预算偏差率、合规通过率、风险事件减少率等指标。',
            ],
            'supply_chain' => [
                'default' => '供应链岗位重点：强化供应链管理和采购策略能力，突出库存优化和成本控制成果。',
                'quantified' => '供应链量化侧重：优先提取库存周转率、采购成本节约、交付准时率、供应商评分等指标。',
            ],
            'manufacturing' => [
                'default' => '制造岗位重点：强化工艺优化和质量管理能力，突出精益生产和自动化改造成果。',
                'quantified' => '制造量化侧重：优先提取良品率、产能提升、成本节约、设备 OEE 等制造指标。',
            ],
            'healthcare' => [
                'default' => '医疗岗位重点：强化临床协调和医学事务能力，突出合规管理和患者服务成果。',
            ],
            'education' => [
                'default' => '教育岗位重点：强化课程设计和教学实施能力，突出学习成效和教研成果。',
                'quantified' => '教育量化侧重：优先提取学员满意度、通过率、课程完成率、教研产出等指标。',
            ],
            'ecommerce' => [
                'default' => '电商岗位重点：强化店铺运营和选品策略能力，突出 GMV 和转化率成果。',
                'quantified' => '电商量化侧重：优先提取 GMV、转化率、复购率、客单价、ROI 等电商指标。',
            ],
            'media_content' => [
                'default' => '传媒内容岗位重点：强化内容策划和传播效果能力，突出用户互动和内容产出成果。',
            ],
            'government' => [
                'default' => '政企岗位重点：强化政策研究和项目申报能力，突出合规执行和政府协同成果。',
                'industry_fit' => '政企行业适配侧重：使用规范公文式表达，突出政策理解力和组织协调力，避免互联网黑话。',
            ],
            'fresh_graduate' => [
                'default' => '应届生求职重点：优先把课程项目/毕业设计/实习经历写成 STAR 结构，突出你做了什么、产出了什么。控制 1 页内，按岗位相关度排序。',
                'skill_match' => '应届生技能匹配侧重：优先写与岗位直接相关的工具和框架，在项目经历中出现对应关键词。缺少的技能在 gap_actions 中建议补充。',
                'highlights' => '应届生亮点侧重：从课程项目、毕业设计、竞赛、实习中提炼可验证成果，避免"参与讨论"类空泛表述。',
                'quantified' => '应届生量化侧重：优先提取项目中的数据（数据集规模、实验指标、课程设计评分等），缺失时给"待补充指标建议"，不编造百分比。',
                'industry_fit' => '应届生行业适配侧重：根据目标行业调整表达风格，技术岗突出技术深度，运营岗突出数据意识，产品岗突出需求分析能力。',
            ],
        ];

        $templateHints = $hints[$templateKey] ?? null;
        if ($templateHints === null) {
            return '';
        }

        $lines = [];
        $defaultHint = $templateHints['default'] ?? '';
        if ($defaultHint !== '') {
            $lines[] = $defaultHint;
        }

        $goalSet = array_fill_keys($goals, true);
        foreach ($templateHints as $goalKey => $hint) {
            if ($goalKey === 'default' || ! isset($goalSet[$goalKey])) {
                continue;
            }
            $lines[] = $hint;
        }

        return implode("\n", $lines);
    }

    private function resolveBenchmarkHints(string $targetJob): string
    {
        if ($targetJob === '') return '';

        $cached = \Illuminate\Support\Facades\Cache::remember('ats_benchmark:' . md5($targetJob), 3600, function () use ($targetJob) {
            $jobLower = mb_strtolower($targetJob);
            $highScoreResumes = \App\Models\Resume::query()
                ->whereNotNull('ats_score')
                ->where('ats_score', '>=', 75)
                ->where('target_job', 'like', '%' . $jobLower . '%')
                ->limit(50)
                ->pluck('content_raw');

            if ($highScoreResumes->isEmpty()) return null;

            $allText = $highScoreResumes->join(' ');
            $words = preg_split('/[\s,，。；：、\n]+/', mb_strtolower($allText));
            $freq = array_count_values(array_filter($words, fn ($w) => mb_strlen($w) >= 2));
            arsort($freq);
            $topKeywords = array_slice(array_keys($freq), 0, 10);

            $avgScore = \App\Models\Resume::query()
                ->whereNotNull('ats_score')
                ->where('target_job', 'like', '%' . $jobLower . '%')
                ->avg('ats_score');

            return [
                'top_keywords' => $topKeywords,
                'avg_score' => round($avgScore),
                'sample_count' => $highScoreResumes->count(),
            ];
        });

        if (!$cached) return '';

        $keywords = implode('、', array_slice($cached['top_keywords'], 0, 8));
        return "该岗位高分简历（{$cached['sample_count']}份样本，均分{$cached['avg_score']}）中高频出现的关键词：{$keywords}。可参考这些关键词的使用密度和表达方式。";
    }
}
