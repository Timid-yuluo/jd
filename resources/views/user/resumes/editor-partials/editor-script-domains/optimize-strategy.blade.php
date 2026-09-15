        optimizeGoalOptions() {
            return [
                { key: 'ats_keywords', title: 'ATS 关键词优化', desc: '提升岗位关键词命中与检索可见性', icon: 'ti ti-search', group: 'job_match' },
                { key: 'skill_match', title: '技能匹配优化', desc: '强化与目标岗位的技能对应关系', icon: 'ti ti-target-arrow', group: 'job_match' },
                { key: 'tailor_job', title: '岗位定制优化', desc: '按 JD 重排事实内容，突出强相关经历', icon: 'ti ti-briefcase', group: 'job_match' },
                { key: 'structure', title: '结构优化', desc: '优化模块层次与信息顺序，便于快速阅读', icon: 'ti ti-layout-grid', group: 'content_quality' },
                { key: 'language', title: '语言表达优化', desc: '统一专业术语，降低口语化表达', icon: 'ti ti-writing', group: 'content_quality' },
                { key: 'readability', title: '可读性优化', desc: '优化句式节奏，提升阅读效率', icon: 'ti ti-eye', group: 'content_quality' },
                { key: 'concise', title: '精简降噪优化', desc: '压缩低价值信息，保留核心事实', icon: 'ti ti-cut', group: 'content_quality' },
                { key: 'highlights', title: '亮点提炼优化', desc: '提炼已有证据支撑的优势，不夸大', icon: 'ti ti-star', group: 'content_quality' },
                { key: 'quantified', title: '量化成果优化', desc: '优先提取已有数字；缺失时给补充建议', icon: 'ti ti-chart-line', group: 'risk_control' },
                { key: 'authenticity', title: '真实性护栏', desc: '默认开启：禁止编造经历、成果和数据', icon: 'ti ti-shield-check', group: 'risk_control', locked: true },
                { key: 'industry_fit', title: '行业适配优化', desc: '按目标行业调整用语风格与侧重点', icon: 'ti ti-building-factory', group: 'career_strategy' },
                { key: 'career_pivot', title: '跨职能转型优化', desc: '强化可迁移能力与学习适应力表达', icon: 'ti ti-arrows-exchange', group: 'career_strategy' },
                { key: 'leadership', title: '领导力展示优化', desc: '突出管理决策与团队影响力', icon: 'ti ti-users-group', group: 'career_strategy' },
                { key: 'i18n_expression', title: '国际化表达优化', desc: '优化中英双语表达与文化适配', icon: 'ti ti-language', group: 'career_strategy' },
                { key: 'project_impact', title: '项目影响力优化', desc: '突出项目规模、影响范围与业务价值', icon: 'ti ti-rocket', group: 'career_strategy' },
                { key: 'tech_depth', title: '技术深度优化', desc: '强化技术方案选型与架构决策表达', icon: 'ti ti-code', group: 'career_strategy' },
                { key: 'cross_cultural', title: '跨文化协作优化', desc: '突出多元团队协作与跨文化沟通能力', icon: 'ti ti-world', group: 'career_strategy' },
                { key: 'certification', title: '资质认证优化', desc: '突出专业认证、资质与行业认可', icon: 'ti ti-certificate', group: 'career_strategy' },
                { key: 'innovation', title: '创新能力优化', desc: '突出创新实践、技术突破与行业贡献', icon: 'ti ti-bulb', group: 'career_strategy' },
                { key: 'data_driven', title: '数据驱动优化', desc: '强化数据思维与指标导向的表达', icon: 'ti ti-chart-dots', group: 'career_strategy' },
            ];
        },

        optimizeGoalGroups() {
            return [
                { key: 'job_match', title: '岗位匹配', desc: '提升与目标 JD 的对齐度' },
                { key: 'content_quality', title: '内容质量', desc: '优化结构表达与可读性' },
                { key: 'career_strategy', title: '职业策略', desc: '适配行业、转型与进阶场景' },
                { key: 'risk_control', title: '风险控制', desc: '真实性优先，避免不可验证内容' },
            ];
        },

        optimizeGoalsByGroup(groupKey) {
            return this.optimizeGoalOptions().filter((goal) => goal.group === groupKey);
        },

        optimizeModeOptions() {
            return [
                { key: 'quick', title: '快速', desc: '轻量优化，优先保留原始事实与结构' },
                { key: 'balanced', title: '平衡', desc: '真实性优先下做适度重写，推荐日常使用' },
                { key: 'deep', title: '深度', desc: '在事实边界内深度重构，适合投递前精修' },
            ];
        },

        canUseAdvancedModel() {
            return this.advancedModelEnabled === true;
        },

        advancedTemplateKeys() {
            return ['backend', 'devops_sre', 'data_ai', 'security'];
        },

        isPromptTemplateLocked(templateKey) {
            return !this.canUseAdvancedModel() && this.advancedTemplateKeys().includes(String(templateKey || ''));
        },

        isOptimizeModeLocked(modeKey) {
            return !this.canUseAdvancedModel() && String(modeKey || '') === 'deep';
        },

        lockedFeatureHint(featureName = '该能力') {
            return `${featureName}仅专业版可用，点击可升级套餐`;
        },

        goToPlanUpgrade() {
            const url = String(this.planUpgradeUrl || '/user/pricing').trim();
            if (url !== '') {
                window.location.href = url;
            }
        },

        notifyAdvancedFeatureLocked(featureName = '该能力') {
            this.showToast(`${featureName}仅专业版可用，正在跳转升级页面。`, 'warning');
            setTimeout(() => this.goToPlanUpgrade(), 360);
        },

        setOptimizeMode(modeKey, opts = {}) {
            if (this.isOptimizeModeLocked(modeKey)) {
                if (!opts || opts.silent !== true) {
                    this.notifyAdvancedFeatureLocked('深度优化模式');
                }
                return false;
            }
            this.optimizeMode = modeKey;
            return true;
        },

        enforcePlanFeatureConstraints(silent = false) {
            if (this.canUseAdvancedModel()) {
                return false;
            }
            let changed = false;
            if (this.isPromptTemplateLocked(this.promptStrategyTemplate)) {
                this.promptStrategyTemplate = 'general';
                const fallbackTemplate = this.promptStrategyTemplateOptions().find((item) => item.key === 'general');
                if (fallbackTemplate) {
                    this.optimizeMode = fallbackTemplate.mode;
                    this.optimizeGoals = this.sanitizeOptimizeGoals(fallbackTemplate.goals);
                    this.syncKeywordsForPromptTemplate(fallbackTemplate.key, 10);
                }
                changed = true;
            }
            if (this.optimizeMode === 'deep') {
                this.optimizeMode = 'balanced';
                changed = true;
            }
            if (changed && !silent) {
                this.showToast('当前套餐未包含高级模型，已自动切换到可用策略。', 'info');
            }
            return changed;
        },

        optimizeModeScopeHint() {
            const map = {
                quick: '改写幅度：低；不会新增事实，仅做必要整理。',
                balanced: '改写幅度：中；在真实性优先下提升匹配度与可读性。',
                deep: '改写幅度：中高；可重构结构，但不新增未经证实信息。',
            };
            return map[this.optimizeMode] || map.balanced;
        },

        sanitizeOptimizeGoals(goals) {
            const allowed = new Set(this.optimizeGoalOptions().map((goal) => goal.key));
            const list = Array.isArray(goals) ? goals : [];
            const deduped = [];
            list.forEach((goalKey) => {
                const key = String(goalKey || '').trim();
                if (!allowed.has(key) || deduped.includes(key)) {
                    return;
                }
                deduped.push(key);
            });
            if (!deduped.includes('authenticity')) {
                deduped.push('authenticity');
            }
            return deduped;
        },

        ensureAuthenticityGoal(silent = true) {
            const nextGoals = this.sanitizeOptimizeGoals(this.optimizeGoals);
            const hadAuth = Array.isArray(this.optimizeGoals) && this.optimizeGoals.includes('authenticity');
            this.optimizeGoals = nextGoals;
            if (!silent && !hadAuth) {
                this.showToast('真实性护栏为默认开启项，已自动补齐。', 'info');
            }
        },

        optimizePresetOptions() {
            return [
                { key: 'ats', title: 'ATS 提升', mode: 'balanced', goals: ['ats_keywords', 'skill_match', 'structure', 'industry_fit'] },
                { key: 'impact', title: '成果强化', mode: 'balanced', goals: ['quantified', 'highlights', 'language', 'leadership', 'project_impact'] },
                { key: 'tailored', title: '精准投递', mode: 'deep', goals: ['tailor_job', 'skill_match', 'ats_keywords', 'structure', 'industry_fit'] },
                { key: 'clean', title: '精炼可信', mode: 'balanced', goals: ['concise', 'authenticity', 'readability', 'language'] },
                { key: 'pivot', title: '跨行转型', mode: 'balanced', goals: ['career_pivot', 'skill_match', 'highlights', 'industry_fit', 'language'] },
                { key: 'exec', title: '管理进阶', mode: 'balanced', goals: ['leadership', 'quantified', 'highlights', 'structure', 'industry_fit', 'project_impact'] },
                { key: 'global', title: '外企/海外', mode: 'balanced', goals: ['i18n_expression', 'ats_keywords', 'language', 'skill_match', 'cross_cultural'] },
                { key: 'tech', title: '技术深耕', mode: 'deep', goals: ['tech_depth', 'skill_match', 'ats_keywords', 'project_impact', 'quantified'] },
                { key: 'data', title: '数据赋能', mode: 'balanced', goals: ['data_driven', 'quantified', 'highlights', 'skill_match', 'language'] },
                { key: 'cert', title: '资质导向', mode: 'balanced', goals: ['certification', 'skill_match', 'structure', 'language', 'industry_fit'] },
                { key: 'innov', title: '创新突破', mode: 'balanced', goals: ['innovation', 'highlights', 'project_impact', 'language', 'tech_depth'] },
                { key: 'all', title: '全面优化(真实优先)', goals: this.optimizeGoalOptions().map((item) => item.key) },
            ];
        },

        applyOptimizePreset(presetKey) {
            const preset = this.optimizePresetOptions().find((item) => item.key === presetKey);
            if (!preset) {
                return;
            }
            this.optimizeGoals = this.sanitizeOptimizeGoals(preset.goals);
            if (preset.mode) {
                this.setOptimizeMode(preset.mode);
            }
        },

        isOptimizePresetActive(presetKey) {
            const preset = this.optimizePresetOptions().find((item) => item.key === presetKey);
            if (!preset || !Array.isArray(this.optimizeGoals)) {
                return false;
            }
            const current = this.sanitizeOptimizeGoals(this.optimizeGoals).sort();
            const target = this.sanitizeOptimizeGoals(preset.goals || []).sort();
            return current.length === target.length && current.every((item, idx) => item === target[idx]);
        },

        recommendedPresetByTemplate(templateKey) {
            const map = {
                general: 'impact',
                fresh_graduate: 'tailored',
                backend: 'tailored',
                frontend: 'tailored',
                fullstack: 'tailored',
                mobile: 'tailored',
                qa_test: 'tailored',
                devops_sre: 'tailored',
                data_ai: 'tailored',
                security: 'tailored',
                product: 'impact',
                design: 'clean',
                operations: 'impact',
                marketing: 'impact',
                sales: 'impact',
                hr_admin: 'clean',
                finance_legal: 'clean',
                supply_chain: 'clean',
                manufacturing: 'clean',
                healthcare: 'clean',
                education: 'clean',
                ecommerce: 'impact',
                media_content: 'impact',
                public_service: 'clean',
            };
            return map[templateKey] || 'impact';
        },

        isFreshGraduateContext() {
            if ((this.promptStrategyTemplate || '') === 'fresh_graduate') {
                return true;
            }
            const targetText = `${this.streamTargetJob || ''} ${this.streamTargetJobTitle || ''} ${this.streamTargetJobDescription || ''}`.toLowerCase();
            const textSignals = ['应届', '校招', '毕业生', 'new grad', 'fresh graduate', '实习生'];
            if (textSignals.some((signal) => targetText.includes(signal))) {
                return true;
            }
            const modules = Array.isArray(this.modules) ? this.modules : [];
            const experienceModules = modules.filter((mod) => String(mod?.type || '') === 'experience');
            const hasInternSignal = JSON.stringify(modules).includes('实习');
            return experienceModules.length <= 1 && hasInternSignal;
        },

        shouldSuggestFreshGraduatePreset() {
            return this.isFreshGraduateContext() && (this.promptStrategyTemplate || '') !== 'fresh_graduate';
        },

        freshGraduateHintStorageKey() {
            const resumeId = this.resumeId || 'unknown';
            return `resume_fresh_graduate_hint_seen_${resumeId}`;
        },

        freshGraduateGuideStorageKey() {
            const resumeId = this.resumeId || 'unknown';
            return `resume_fresh_graduate_guide_skip_${resumeId}`;
        },

        markFreshGraduateHintSeen() {
            try {
                window.localStorage?.setItem(this.freshGraduateHintStorageKey(), '1');
            } catch (error) {
                // ignore localStorage errors
            }
        },

        hasSeenFreshGraduateHint() {
            try {
                return window.localStorage?.getItem(this.freshGraduateHintStorageKey()) === '1';
            } catch (error) {
                return false;
            }
        },

        isFreshGraduateGuideSkipped() {
            try {
                return window.localStorage?.getItem(this.freshGraduateGuideStorageKey()) === '1';
            } catch (error) {
                return false;
            }
        },

        markFreshGraduateGuideSkipped() {
            try {
                window.localStorage?.setItem(this.freshGraduateGuideStorageKey(), '1');
            } catch (error) {
                // ignore localStorage errors
            }
        },

        maybeSuggestFreshGraduatePreset() {
            if (!this.shouldSuggestFreshGraduatePreset() || this.hasSeenFreshGraduateHint()) {
                return;
            }
            this.showToast('检测到应届生/校招场景，可一键使用“应届生一键预设”。', 'info');
            this.markFreshGraduateHintSeen();
        },

        onFreshGraduatePresetClick() {
            this.freshGraduateGuideDontShowAgain = false;
            this.openFreshGraduatePresetModal();
        },

        freshGraduateSuggestionRows() {
            if (!this.isFreshGraduateContext()) {
                return [];
            }
            return [
                '优先把“课程项目/毕业设计/实习经历”写成 STAR 结构，突出你做了什么、产出了什么。',
                '如果缺少硬数字，先写可验证结果，再补“待补充指标建议”，不要编造百分比。',
                '技能模块优先写与岗位直接相关的工具和框架，并在项目经历中出现对应关键词。',
                '应届生建议控制在 1 页内，教育背景、项目经历、实习经历按岗位相关度排序。',
            ];
        },

        freshGraduateDynamicTrack() {
            const merged = `${this.streamTargetJob || ''} ${this.streamTargetJobTitle || ''} ${this.streamTargetJobDescription || ''}`.toLowerCase();
            const resolved = this.resolvePromptStrategyTemplateByText(merged);
            const templateKey = (this.promptStrategyTemplate || resolved || 'fresh_graduate').toLowerCase();

            const techTemplates = ['backend', 'frontend', 'fullstack', 'mobile', 'qa_test', 'devops_sre', 'data_ai', 'security'];
            const productTemplates = ['product', 'design'];
            const operationTemplates = ['operations', 'marketing', 'sales', 'ecommerce', 'media_content'];

            if (techTemplates.includes(templateKey)) {
                return 'tech';
            }
            if (productTemplates.includes(templateKey)) {
                return 'product';
            }
            if (operationTemplates.includes(templateKey)) {
                return 'operation';
            }
            return 'general';
        },

        freshGraduateGuideTips() {
            const track = this.freshGraduateDynamicTrack();
            const common = [
                '优先用课程项目、毕业设计、实习经历证明岗位相关能力。',
                '缺少硬数字时先给“待补充指标建议”，不要编造百分比或金额。',
            ];
            const map = {
                tech: [
                    '技术栈请与目标 JD 对齐，项目中至少出现 3-5 个核心关键词（如框架、数据库、部署工具）。',
                    '项目描述建议使用“问题-方案-结果”结构，强调你具体完成的模块而不是团队整体成果。',
                ],
                product: [
                    '突出需求分析、方案设计、推动落地与复盘结果，避免只写“参与讨论”。',
                    '强调数据意识：即便没有完整指标，也要写清观察口径与改进方向。',
                ],
                operation: [
                    '优先描述拉新、转化、留存、内容分发等动作及结果，突出执行闭环。',
                    '活动经历建议写“目标-动作-复盘”，避免泛泛罗列工作事项。',
                ],
                general: [
                    '教育背景、项目经历、实习经历按岗位相关度排序，控制在 1 页内。',
                    '减少空泛自评，优先使用可验证事实与客观结果表达。',
                ],
            };

            return [...common, ...(map[track] || map.general)];
        },

        freshGraduateTrackMeta() {
            const track = this.freshGraduateDynamicTrack();
            const map = {
                tech: {
                    title: '应届生技术岗预设说明',
                    badge: '技术岗',
                    summary: '系统会优先强化技术栈关键词、项目模块贡献和可验证结果表达。',
                },
                product: {
                    title: '应届生产品岗预设说明',
                    badge: '产品岗',
                    summary: '系统会优先强化需求-方案-落地-复盘链路与业务理解表达。',
                },
                operation: {
                    title: '应届生运营岗预设说明',
                    badge: '运营岗',
                    summary: '系统会优先强化增长动作、转化结果与执行闭环表达。',
                },
                general: {
                    title: '应届生通用预设说明',
                    badge: '通用',
                    summary: '系统会优先强化岗位相关性、可读性和真实可信表达。',
                },
            };

            return map[track] || map.general;
        },

        optimizeGoalTitle(goalKey) {
            const row = this.optimizeGoalOptions().find((item) => item.key === goalKey);
            return row?.title || goalKey;
        },

        freshGraduatePresetPlan() {
            const track = this.freshGraduateDynamicTrack();
            const base = ['structure', 'language', 'authenticity'];
            const planMap = {
                tech: {
                    mode: 'balanced',
                    goals: [...base, 'skill_match', 'tailor_job', 'highlights'],
                },
                product: {
                    mode: 'balanced',
                    goals: [...base, 'highlights', 'tailor_job', 'readability'],
                },
                operation: {
                    mode: 'balanced',
                    goals: [...base, 'highlights', 'quantified', 'tailor_job'],
                },
                general: {
                    mode: 'balanced',
                    goals: [...base, 'skill_match', 'highlights', 'tailor_job'],
                },
            };

            const plan = planMap[track] || planMap.general;
            return {
                mode: plan.mode,
                goals: this.sanitizeOptimizeGoals(plan.goals),
            };
        },

        freshGraduatePresetPlanLabels() {
            const plan = this.freshGraduatePresetPlan();
            return plan.goals.map((goal) => this.optimizeGoalTitle(goal));
        },

        freshGraduateEstimatedTouchedModuleTypes() {
            const plan = this.freshGraduatePresetPlan();
            const goals = new Set(plan.goals || []);
            const touched = new Set();

            if (goals.has('structure') || goals.has('language') || goals.has('readability') || goals.has('concise')) {
                (Array.isArray(this.modules) ? this.modules : []).forEach((mod) => {
                    const type = String(mod?.type || '').trim();
                    if (type !== '') {
                        touched.add(type);
                    }
                });
            }
            if (goals.has('skill_match') || goals.has('tailor_job') || goals.has('highlights') || goals.has('quantified')) {
                ['education', 'experience', 'project', 'skill', 'summary', 'objective'].forEach((type) => {
                    if ((this.modules || []).some((mod) => String(mod?.type || '') === type)) {
                        touched.add(type);
                    }
                });
            }

            return Array.from(touched);
        },

        freshGraduateEstimatedTouchedModulesCount() {
            const types = new Set(this.freshGraduateEstimatedTouchedModuleTypes());
            return (Array.isArray(this.modules) ? this.modules : [])
                .filter((mod) => types.has(String(mod?.type || ''))).length;
        },

        freshGraduateKeywordOpportunitySummary() {
            const keywords = this.focusKeywordsForOptimize();
            if (!Array.isArray(keywords) || keywords.length === 0) {
                return {
                    total: 0,
                    covered: 0,
                    missing: 0,
                    potentialGain: 0,
                };
            }

            const text = JSON.stringify(this.modulesForSubmit()).toLowerCase();
            const normalizedKeywords = keywords
                .map((item) => String(item || '').trim().toLowerCase())
                .filter((item) => item !== '');
            const covered = normalizedKeywords.filter((kw) => text.includes(kw)).length;
            const missing = Math.max(0, normalizedKeywords.length - covered);
            const potentialGain = Math.min(missing, 6);

            return {
                total: normalizedKeywords.length,
                covered,
                missing,
                potentialGain,
            };
        },

        freshGraduateReadinessSummary() {
            const modules = Array.isArray(this.modules) ? this.modules : [];
            const hasType = (type) => modules.some((mod) => String(mod?.type || '') === type);
            const checks = [
                {
                    key: 'education',
                    label: '教育背景',
                    passed: hasType('education'),
                    tip: '建议补充学校、专业、时间与核心课程/荣誉。',
                },
                {
                    key: 'project',
                    label: '项目经历',
                    passed: hasType('project'),
                    tip: '建议至少补充 1 段课程项目或毕业设计。',
                },
                {
                    key: 'experience',
                    label: '实习/经历',
                    passed: hasType('experience'),
                    tip: '建议补充实习、社团或实践经历并写清职责与结果。',
                },
                {
                    key: 'skill',
                    label: '技能模块',
                    passed: hasType('skill'),
                    tip: '建议补充与目标岗位相关的工具、语言与框架。',
                },
            ];

            const passedCount = checks.filter((item) => item.passed).length;
            let level = 'high';
            let label = '可直接应用';
            if (passedCount <= 1) {
                level = 'low';
                label = '建议先补齐核心模块';
            } else if (passedCount <= 2) {
                level = 'medium';
                label = '可应用，但建议先补 1-2 项';
            }

            return {
                checks,
                passedCount,
                total: checks.length,
                level,
                label,
                missing: checks.filter((item) => !item.passed),
            };
        },

        freshGraduateReadinessBadgeClass() {
            const level = this.freshGraduateReadinessSummary().level;
            if (level === 'low') {
                return 'bg-danger-lt text-danger';
            }
            if (level === 'medium') {
                return 'bg-warning-lt text-warning';
            }
            return 'bg-success-lt text-success';
        },

        freshGraduateApplyButtonLabel() {
            const level = this.freshGraduateReadinessSummary().level;
            if (level === 'low') {
                return '仍要应用（需确认）';
            }
            if (level === 'medium') {
                return '了解并应用（建议先补齐）';
            }
            return '了解并应用';
        },

        freshGraduateMissingModuleActionLabel(key) {
            const map = {
                education: '补教育背景',
                project: '补项目经历',
                experience: '补实习经历',
                skill: '补技能模块',
            };
            return map[String(key || '')] || '补充模块';
        },

        markFreshGraduateModuleAdded(type) {
            this.freshGraduateJustAddedType = String(type || '');
            this.freshGraduateJustAddedAt = Date.now();
            setTimeout(() => {
                if (this.freshGraduateJustAddedType === String(type || '')) {
                    this.freshGraduateJustAddedType = '';
                    this.freshGraduateJustAddedAt = 0;
                }
            }, 2200);
        },

        isFreshGraduateModuleJustAdded(type) {
            return this.freshGraduateJustAddedType === String(type || '')
                && (Date.now() - Number(this.freshGraduateJustAddedAt || 0)) <= 2500;
        },

        notifyFreshGraduateFocusFallback() {
            const now = Date.now();
            if ((now - Number(this.freshGraduateFocusFallbackHintAt || 0)) < 1600) {
                return;
            }
            this.freshGraduateFocusFallbackHintAt = now;
            this.showToast('已定位到新增模块，请开始填写。', 'info');
        },

        focusFreshGraduateAddedModule(moduleKey = '') {
            this.$nextTick(() => {
                setTimeout(() => {
                    const listEl = document.getElementById('module-list');
                    const cards = listEl?.querySelectorAll('.card');
                    if (!cards || cards.length === 0) {
                        this.notifyFreshGraduateFocusFallback();
                        return;
                    }
                    const expectedKey = String(moduleKey || '').trim();
                    let targetCard = null;
                    if (expectedKey !== '') {
                        targetCard = Array.from(cards).find((card) => String(card?.dataset?.moduleKey || '') === expectedKey) || null;
                    }
                    if (!targetCard) {
                        targetCard = cards[this.activeIndex] || cards[cards.length - 1] || null;
                    }
                    if (!targetCard) {
                        this.notifyFreshGraduateFocusFallback();
                        return;
                    }
                    const focusTarget = targetCard.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), [contenteditable="true"]');
                    if (focusTarget && typeof focusTarget.focus === 'function') {
                        focusTarget.focus();
                    } else {
                        this.notifyFreshGraduateFocusFallback();
                    }
                }, 120);
            });
        },

        addFreshGraduateMissingModule(key) {
            const type = String(key || '').trim();
            if (!['education', 'project', 'experience', 'skill'].includes(type)) {
                return;
            }
            this.addModuleOfType(type);
            const addedModuleKey = this.modules[this.modules.length - 1]?._key || '';
            this.markFreshGraduateModuleAdded(type);
            this.focusFreshGraduateAddedModule(addedModuleKey);
            this.showToast(`已添加${this.moduleTypeLabel(type)}模块，可先补齐内容后再应用预设。`, 'success');
        },

        autoRecommendOptimizePreset(templateKey = null, silent = false) {
            const key = templateKey || this.promptStrategyTemplate || 'general';
            const presetKey = this.recommendedPresetByTemplate(key);
            const preset = this.optimizePresetOptions().find((item) => item.key === presetKey);
            if (!preset) {
                return;
            }
            this.applyOptimizePreset(preset.key);
            if (!silent) {
                this.showToast(`已按岗位自动推荐预设：${preset.title}`, 'success');
            }
        },

        applyFreshGraduatePreset() {
            const plan = this.freshGraduatePresetPlan();
            this.applyPromptStrategyTemplate('fresh_graduate');
            this.setOptimizeMode(plan.mode, { silent: true });
            this.optimizeGoals = plan.goals;
            this.markFreshGraduateHintSeen();
            this.showToast(`已应用应届生一键预设：${this.freshGraduateTrackMeta().badge}方向，保持真实可信。`, 'success');
        },

        @include('user.resumes.editor-partials.editor-script-domains.strategy-catalog')

        autoRecommendOptimizeGoals() {
            const recommended = new Set(['structure', 'language']);
            const hasExperienceLike = this.modules.some((mod) => ['experience', 'project'].includes(mod?.type));
            const hasSkillModule = this.modules.some((mod) => mod?.type === 'skill');
            const allText = JSON.stringify(this.modulesForSubmit());
            const hasDigits = /\d/.test(allText);
            const hasJd = (this.streamTargetJobDescription || '').trim() !== '';
            const textLength = this.modulesToRawText().length;
            const hasManagementRole = /经理|总监|主管|负责人|leader|manager|director|head|vp|chief/i.test(allText);
            const hasForeignSignal = /外企|合资|跨国|global|multinational|overseas|海外|英语|english|bilingual/i.test(allText + (this.streamTargetJobDescription || '') + (this.streamTargetJob || ''));
            const hasTechSignal = /架构|技术栈|框架|中间件|微服务|高并发|分布式|k8s|docker|ci\/cd|api|sdk|qps/i.test(allText + (this.streamTargetJobDescription || ''));
            const hasDataSignal = /数据分析|数据驱动|ab测试|漏斗|归因|bi|指标|dashboard|报表|etl|数据仓库/i.test(allText + (this.streamTargetJobDescription || ''));
            const hasCertSignal = /认证|证书|资质|pmp|cpa|cfa|aws|cissp|prince2|软考|pmp|acca|frm|cisa/i.test(allText);
            const hasProjectSignal = /项目|系统|平台|产品|方案|重构|迁移|上线|落地/i.test(allText);

            if (hasJd) {
                recommended.add('ats_keywords');
                recommended.add('skill_match');
                recommended.add('tailor_job');
                recommended.add('industry_fit');
            }
            if (!hasSkillModule || hasJd) {
                recommended.add('skill_match');
            }
            if (hasExperienceLike && !hasDigits) {
                recommended.add('quantified');
            }
            if (hasExperienceLike) {
                recommended.add('highlights');
            }
            if (textLength > 1800) {
                recommended.add('concise');
                recommended.add('readability');
            }
            if (!hasDigits) {
                recommended.add('authenticity');
            }
            if (hasManagementRole) {
                recommended.add('leadership');
            }
            if (hasForeignSignal) {
                recommended.add('i18n_expression');
                recommended.add('cross_cultural');
            }
            if (hasTechSignal) {
                recommended.add('tech_depth');
            }
            if (hasDataSignal) {
                recommended.add('data_driven');
            }
            if (hasCertSignal) {
                recommended.add('certification');
            }
            if (hasProjectSignal && hasExperienceLike) {
                recommended.add('project_impact');
            }

            this.optimizeGoals = this.sanitizeOptimizeGoals(Array.from(recommended));
            this.showToast('已按当前简历内容自动推荐优化方向', 'success');
        },

        autoRecommendOptimizeGoalsFromJd() {
            const recommended = new Set(['structure', 'language', 'authenticity']);
            const jdResult = this.jdParseResult;

            if (jdResult) {
                recommended.add('ats_keywords');
                recommended.add('skill_match');
                recommended.add('tailor_job');
                recommended.add('industry_fit');

                if (jdResult.hard_skills?.length >= 3) {
                    recommended.add('highlights');
                }
                if (jdResult.experience_years) {
                    recommended.add('quantified');
                }
            }

            const hasExperienceLike = this.modules.some((mod) => ['experience', 'project'].includes(mod?.type));
            const allText = JSON.stringify(this.modulesForSubmit());
            const hasDigits = /\d/.test(allText);
            const hasManagementRole = /经理|总监|主管|负责人|leader|manager|director|head|vp|chief/i.test(allText);
            const hasForeignSignal = /外企|合资|跨国|global|multinational|overseas|海外|英语|english|bilingual/i.test(allText + (this.streamTargetJobDescription || '') + (this.streamTargetJob || ''));
            const hasTechSignal = /架构|技术栈|框架|中间件|微服务|高并发|分布式|k8s|docker|ci\/cd|api|sdk|qps/i.test(allText + (this.streamTargetJobDescription || ''));
            const hasDataSignal = /数据分析|数据驱动|ab测试|漏斗|归因|bi|指标|dashboard|报表|etl|数据仓库/i.test(allText + (this.streamTargetJobDescription || ''));
            const hasCertSignal = /认证|证书|资质|pmp|cpa|cfa|aws|cissp|prince2|软考|acca|frm|cisa/i.test(allText);
            const hasProjectSignal = /项目|系统|平台|产品|方案|重构|迁移|上线|落地/i.test(allText);

            if (hasExperienceLike && !hasDigits) {
                recommended.add('quantified');
            }
            if (hasExperienceLike) {
                recommended.add('highlights');
            }
            if (hasManagementRole) {
                recommended.add('leadership');
            }
            if (hasForeignSignal) {
                recommended.add('i18n_expression');
                recommended.add('cross_cultural');
            }
            if (hasTechSignal) {
                recommended.add('tech_depth');
            }
            if (hasDataSignal) {
                recommended.add('data_driven');
            }
            if (hasCertSignal) {
                recommended.add('certification');
            }
            if (hasProjectSignal && hasExperienceLike) {
                recommended.add('project_impact');
            }

            const textLength = this.modulesToRawText().length;
            if (textLength > 1800) {
                recommended.add('concise');
                recommended.add('readability');
            }

            this.optimizeGoals = this.sanitizeOptimizeGoals(Array.from(recommended));

            const skillCount = (jdResult?.hard_skills?.length || 0) + (jdResult?.soft_skills?.length || 0);
            this.showToast(`已根据 JD 分析推荐 ${recommended.size} 个优化方向（含 ${skillCount} 个关键词）`, 'success');
        },

        isOptimizeGoalSelected(goalKey) {
            return Array.isArray(this.optimizeGoals) && this.optimizeGoals.includes(goalKey);
        },

        toggleOptimizeGoal(goalKey) {
            const current = Array.isArray(this.optimizeGoals) ? [...this.optimizeGoals] : [];
            if (goalKey === 'authenticity') {
                this.showToast('真实性护栏为默认开启项，暂不支持关闭。', 'info');
                this.optimizeGoals = this.sanitizeOptimizeGoals(current);
                return;
            }
            const idx = current.indexOf(goalKey);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(goalKey);
            }
            this.optimizeGoals = this.sanitizeOptimizeGoals(current);
        },

        quantifiedGapChecklist(limit = 5) {
            const checklist = [];
            const modules = Array.isArray(this.modules) ? this.modules : [];
            modules.forEach((module) => {
                const type = String(module?.type || '');
                if (!['experience', 'project'].includes(type)) {
                    return;
                }
                const title = String(module?.data?.subtitle || module?.data?.title || type).trim();
                const content = String(module?.data?.content || '');
                const items = Array.isArray(module?.data?.items) ? module.data.items : [];
                const source = [content, ...items.map((item) => String(item || ''))].join('\n');
                if (!/\d/.test(source)) {
                    checklist.push(`${title || '该模块'}：可补充“规模/效率/结果”指标`);
                }
            });
            return checklist.slice(0, Math.max(0, limit));
        },

        optimizeResultTrustTags() {
            const tags = ['仅优化表达与结构，未新增事实'];
            if (this.quantifiedGapChecklist(1).length > 0 && this.isOptimizeGoalSelected('quantified')) {
                tags.push('含待补充指标建议');
            }
            return tags;
        },
