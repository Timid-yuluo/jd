        promptStrategyTemplateCatalog() {
            return [
                {
                    key: 'general',
                    title: '通用策略',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'highlights', 'industry_fit'],
                    applicable: '适用于尚未明确细分岗位的通用投递场景',
                    notApplicable: '不适用于需突出强技术深度或强业务指标的岗位',
                    riskTip: '建议搭配岗位关键词后再优化，减少泛化表达',
                    aliases: ['通用', '综合', 'general'],
                    seedKeywords: ['沟通', '协作', '执行', '复盘', '项目推进'],
                },
                {
                    key: 'fresh_graduate',
                    title: '应届生求职',
                    mode: 'balanced',
                    goals: ['structure', 'skill_match', 'highlights', 'language', 'tailor_job', 'industry_fit'],
                    applicable: '适用于应届生、校招生、无全职经验但有项目/实习经历的求职场景',
                    notApplicable: '不适用于 3 年以上成熟岗位，尤其需要复杂业务 owner 经验的岗位',
                    riskTip: '重点突出课程项目、实习职责和可验证成果，避免“独立负责全链路”类过度表述',
                    aliases: ['应届', '应届生', '校招', '毕业生', 'new grad', 'fresh graduate', '校招生'],
                    seedKeywords: ['应届生', '校招', '实习', '课程项目', '毕业设计', '竞赛经历', '学习能力'],
                },
                {
                    key: 'backend',
                    title: '后端工程',
                    mode: 'deep',
                    goals: ['skill_match', 'ats_keywords', 'quantified', 'structure', 'industry_fit', 'tech_depth', 'project_impact'],
                    applicable: '适用于后端、平台、基础架构与中后台岗位',
                    notApplicable: '不适用于纯运营或纯设计岗位',
                    riskTip: '会强化技术细节，请确保技术栈与项目事实可验证',
                    aliases: ['后端', 'backend', 'java', 'php', 'golang', 'spring', 'laravel', '微服务'],
                    seedKeywords: ['laravel', 'php', 'java', 'spring', 'golang', 'mysql', 'redis', 'kafka', 'api', '微服务', '高并发'],
                },
                {
                    key: 'frontend',
                    title: '前端工程',
                    mode: 'balanced',
                    goals: ['skill_match', 'language', 'highlights', 'ats_keywords', 'industry_fit', 'tech_depth'],
                    aliases: ['前端', 'frontend', 'web前端', 'h5', 'react', 'vue'],
                    seedKeywords: ['react', 'vue', 'typescript', 'javascript', 'vite', 'webpack', '前端工程化', '组件化'],
                },
                {
                    key: 'fullstack',
                    title: '全栈工程',
                    mode: 'balanced',
                    goals: ['skill_match', 'highlights', 'language', 'ats_keywords', 'industry_fit', 'tech_depth'],
                    aliases: ['全栈', 'fullstack', 'node', '前后端'],
                    seedKeywords: ['node', 'react', 'vue', 'typescript', 'api', 'mysql', 'redis', '全栈'],
                },
                {
                    key: 'mobile',
                    title: '移动端',
                    mode: 'balanced',
                    goals: ['skill_match', 'highlights', 'language', 'ats_keywords', 'industry_fit', 'tech_depth'],
                    aliases: ['android', 'ios', '移动端', 'flutter', '鸿蒙', '客户端'],
                    seedKeywords: ['android', 'ios', 'flutter', 'dart', 'swift', 'kotlin', '移动端', '性能优化'],
                },
                {
                    key: 'qa_test',
                    title: '测试质量',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'language', 'highlights', 'industry_fit'],
                    aliases: ['测试', 'qa', '质量', '自动化测试', '性能测试'],
                    seedKeywords: ['测试用例', '自动化测试', '性能测试', '接口测试', '质量保障', '缺陷管理'],
                },
                {
                    key: 'devops_sre',
                    title: '运维DevOps',
                    mode: 'deep',
                    goals: ['skill_match', 'ats_keywords', 'quantified', 'structure', 'industry_fit', 'tech_depth', 'project_impact'],
                    aliases: ['devops', 'sre', '运维', '云平台', 'kubernetes', 'docker', '容器'],
                    seedKeywords: ['devops', 'sre', 'docker', 'kubernetes', 'linux', '监控告警', 'ci/cd', '自动化运维'],
                },
                {
                    key: 'data_ai',
                    title: '数据/AI',
                    mode: 'deep',
                    goals: ['skill_match', 'quantified', 'highlights', 'ats_keywords', 'industry_fit', 'tech_depth', 'data_driven'],
                    aliases: ['数据', 'data', '算法', 'ai', 'llm', '机器学习', '深度学习', 'nlp'],
                    seedKeywords: ['python', '机器学习', '深度学习', 'llm', 'rag', '向量检索', '数据分析', '模型部署'],
                },
                {
                    key: 'security',
                    title: '网络安全',
                    mode: 'deep',
                    goals: ['skill_match', 'ats_keywords', 'structure', 'quantified', 'industry_fit', 'certification'],
                    aliases: ['安全', '渗透', '攻防', '等保', '合规安全', 'secops'],
                    seedKeywords: ['渗透测试', '漏洞扫描', '零信任', '攻防演练', 'waf', '安全加固', '等保合规'],
                },
                {
                    key: 'product',
                    title: '产品经理',
                    mode: 'balanced',
                    goals: ['highlights', 'quantified', 'language', 'structure', 'industry_fit', 'data_driven', 'project_impact'],
                    applicable: '适用于产品经理、产品运营、需求分析等岗位',
                    notApplicable: '不适用于纯研发深技术岗位',
                    riskTip: '优先展示决策依据和业务结果，避免空泛“推动/协调”表述',
                    aliases: ['产品经理', 'product manager', 'prd', '需求分析', '用户研究'],
                    seedKeywords: ['需求分析', 'prd', '用户研究', '产品规划', '数据驱动', '跨团队协作'],
                },
                {
                    key: 'design',
                    title: '设计体验',
                    mode: 'balanced',
                    goals: ['highlights', 'language', 'structure', 'skill_match', 'industry_fit'],
                    aliases: ['ui', 'ux', '交互设计', '视觉设计', '设计师'],
                    seedKeywords: ['ui', 'ux', '交互设计', '视觉设计', '设计系统', '用户体验', 'figma'],
                },
                {
                    key: 'operations',
                    title: '运营增长',
                    mode: 'balanced',
                    goals: ['highlights', 'quantified', 'language', 'structure', 'industry_fit', 'data_driven'],
                    applicable: '适用于运营增长、用户运营、活动运营等岗位',
                    notApplicable: '不适用于以代码工程能力为主的岗位',
                    riskTip: '建议补充转化率、留存、成本等可核验指标再做深度优化',
                    aliases: ['运营', '增长', '用户运营', '内容运营', '活动运营'],
                    seedKeywords: ['增长', '转化', '用户运营', '活动运营', '内容运营', '数据复盘', 'a/b测试'],
                },
                {
                    key: 'marketing',
                    title: '市场品牌',
                    mode: 'balanced',
                    goals: ['highlights', 'quantified', 'language', 'ats_keywords', 'industry_fit', 'innovation'],
                    aliases: ['市场', '品牌', '投放', 'sem', 'seo', '公关', '媒介'],
                    seedKeywords: ['品牌传播', '营销策划', '广告投放', 'sem', 'seo', '媒介策略', 'roi'],
                },
                {
                    key: 'sales',
                    title: '销售商务',
                    mode: 'balanced',
                    goals: ['highlights', 'quantified', 'language', 'structure', 'leadership'],
                    aliases: ['销售', '商务', 'bd', '客户经理', '大客户', '渠道'],
                    seedKeywords: ['客户拓展', '商务谈判', '销售漏斗', '业绩达成', '大客户管理', '渠道拓展'],
                },
                {
                    key: 'hr_admin',
                    title: '人力行政',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'highlights', 'quantified', 'leadership'],
                    aliases: ['hr', '人力', '招聘', 'hrbp', '薪酬绩效', '培训'],
                    seedKeywords: ['招聘配置', 'hrbp', '薪酬绩效', '培训发展', '组织发展', '人才盘点'],
                },
                {
                    key: 'finance_legal',
                    title: '财务法务',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'quantified', 'skill_match', 'industry_fit', 'certification'],
                    aliases: ['财务', '会计', '审计', '税务', '法务', '合规', '风控'],
                    seedKeywords: ['财务分析', '成本控制', '预算管理', '内控审计', '合同审核', '合规风控'],
                },
                {
                    key: 'supply_chain',
                    title: '供应链采购',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'language', 'highlights', 'industry_fit'],
                    aliases: ['供应链', '采购', '物流', '仓储', '计划'],
                    seedKeywords: ['供应链管理', '采购策略', '库存周转', '物流优化', '供应商管理', '需求计划'],
                },
                {
                    key: 'manufacturing',
                    title: '制造工程',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'skill_match', 'language', 'industry_fit'],
                    aliases: ['制造', '工艺', '自动化', '机械', '电气', '生产', '质量工程'],
                    seedKeywords: ['工艺优化', '自动化', '精益生产', '设备维护', '质量改进', 'ehs'],
                },
                {
                    key: 'healthcare',
                    title: '医疗健康',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'skill_match', 'highlights', 'industry_fit'],
                    aliases: ['医疗', '医药', '器械', '临床', '护理', '康复'],
                    seedKeywords: ['临床协调', '医学事务', '药物警戒', '注册申报', '医疗器械', '患者管理'],
                },
                {
                    key: 'education',
                    title: '教育培训',
                    mode: 'balanced',
                    goals: ['language', 'highlights', 'structure', 'quantified', 'industry_fit'],
                    aliases: ['教育', '教师', '培训', '教研', '课程'],
                    seedKeywords: ['课程设计', '教学实施', '教研', '学习成效', '培训体系', '教学管理'],
                },
                {
                    key: 'ecommerce',
                    title: '电商零售',
                    mode: 'balanced',
                    goals: ['highlights', 'quantified', 'language', 'ats_keywords', 'industry_fit', 'data_driven'],
                    aliases: ['电商', '零售', '店铺运营', '跨境电商', '直播运营'],
                    seedKeywords: ['店铺运营', '选品策略', '转化率', 'gmv', '复购率', '直播运营', '供应链协同'],
                },
                {
                    key: 'media_content',
                    title: '传媒内容',
                    mode: 'balanced',
                    goals: ['language', 'highlights', 'structure', 'quantified', 'industry_fit'],
                    aliases: ['内容', '编辑', '记者', '编导', '视频剪辑', '新媒体'],
                    seedKeywords: ['内容策划', '脚本撰写', '内容生产', '传播效果', '用户互动', '新媒体矩阵'],
                },
                {
                    key: 'government',
                    title: '政企公共',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'highlights', 'skill_match', 'industry_fit'],
                    aliases: ['政企', '公共事务', '招投标', '政府关系', '事业单位'],
                    seedKeywords: ['政策研究', '项目申报', '招投标', '政府协同', '公共治理', '合规执行'],
                },
                {
                    key: 'consulting',
                    title: '咨询顾问',
                    mode: 'balanced',
                    goals: ['structure', 'highlights', 'quantified', 'language', 'industry_fit', 'certification'],
                    aliases: ['咨询', 'consulting', '管理咨询', '战略咨询', 'it咨询', 'mbb', '四大'],
                    seedKeywords: ['战略规划', '商业分析', '行业研究', '方案设计', '客户管理', '数据建模'],
                },
                {
                    key: 'fintech',
                    title: '金融科技',
                    mode: 'deep',
                    goals: ['skill_match', 'ats_keywords', 'quantified', 'structure', 'industry_fit', 'certification'],
                    aliases: ['金融科技', 'fintech', '互联网金融', '支付', '数字银行', '消费金融', '区块链'],
                    seedKeywords: ['风控模型', '支付系统', '合规', '反欺诈', '量化交易', '信贷', '牌照'],
                },
                {
                    key: 'gaming',
                    title: '游戏行业',
                    mode: 'balanced',
                    goals: ['skill_match', 'highlights', 'quantified', 'language', 'industry_fit', 'innovation'],
                    aliases: ['游戏', 'game', '游戏开发', '游戏策划', '游戏运营', 'unity', 'unreal', '手游', '端游'],
                    seedKeywords: ['游戏设计', 'unity', 'unreal', '数值策划', '关卡设计', '留存率', 'arpu', '游戏运营'],
                },
                {
                    key: 'semiconductor',
                    title: '半导体/芯片',
                    mode: 'deep',
                    goals: ['skill_match', 'ats_keywords', 'quantified', 'structure', 'industry_fit', 'tech_depth', 'certification'],
                    aliases: ['半导体', '芯片', 'ic', '集成电路', 'verilog', 'fpga', 'asic', 'eda', '封测', '晶圆'],
                    seedKeywords: ['芯片设计', 'verilog', 'fpga', 'asic', 'eda', '流片', 'dft', '验证', '工艺节点', '良率'],
                },
                {
                    key: 'real_estate',
                    title: '房地产/建筑',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'language', 'highlights', 'industry_fit', 'certification'],
                    aliases: ['房地产', '建筑', '地产', '物业', '施工', '造价', '设计院', '工程管理'],
                    seedKeywords: ['项目开发', '工程管理', '成本控制', '规划设计', '招采', '施工组织', '验收交付'],
                },
                {
                    key: 'energy',
                    title: '能源环保',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'skill_match', 'language', 'industry_fit', 'certification'],
                    aliases: ['能源', '新能源', '光伏', '风电', '储能', '环保', '碳中和', '电力', '氢能'],
                    seedKeywords: ['光伏', '风电', '储能', '碳中和', '环保合规', '电力系统', '新能源', 'emc'],
                },
                {
                    key: 'logistics',
                    title: '物流快递',
                    mode: 'balanced',
                    goals: ['structure', 'quantified', 'highlights', 'language', 'industry_fit'],
                    aliases: ['物流', '快递', '配送', '仓储', '供应链', '货运', '冷链', '跨境物流'],
                    seedKeywords: ['物流优化', '仓储管理', '配送效率', '成本控制', '路由规划', '冷链管理', '运力调度'],
                },
                {
                    key: 'civil_service',
                    title: '公务员/国企',
                    mode: 'balanced',
                    goals: ['structure', 'language', 'highlights', 'skill_match', 'industry_fit', 'certification'],
                    aliases: ['公务员', '国企', '体制内', '事业单位', '编制', '选调', '遴选', '央企'],
                    seedKeywords: ['政策执行', '组织协调', '公文写作', '群众工作', '党建', '纪检', '考核'],
                },
                {
                    key: 'automotive',
                    title: '汽车行业',
                    mode: 'balanced',
                    goals: ['skill_match', 'quantified', 'structure', 'language', 'industry_fit', 'tech_depth'],
                    aliases: ['汽车', 'automotive', '智能网联', '新能源车', '自动驾驶', '整车', '零部件', '车联网'],
                    seedKeywords: ['智能驾驶', '新能源', '车载系统', 'autosar', '功能安全', '三电系统', 'ota', 'adas'],
                },
                {
                    key: 'tourism',
                    title: '旅游酒店',
                    mode: 'balanced',
                    goals: ['highlights', 'language', 'structure', 'quantified', 'industry_fit', 'cross_cultural'],
                    aliases: ['旅游', '酒店', '民宿', '景区', '文旅', '餐饮', '度假', '会展'],
                    seedKeywords: ['客户体验', '收益管理', '运营管理', '品牌推广', '渠道管理', '服务标准', '复购率'],
                },
            ];
        },

        promptStrategyTemplateOptions() {
            const all = this.promptStrategyTemplateCatalog()
                .map((item) => ({
                    key: item.key,
                    title: item.title,
                    mode: item.mode,
                    goals: item.goals,
                    category: item.category || 'general',
                    applicable: item.applicable || '',
                    notApplicable: item.notApplicable || '',
                    riskTip: item.riskTip || '',
                    aliases: Array.isArray(item.aliases) ? item.aliases : [],
                    seedKeywords: Array.isArray(item.seedKeywords) ? item.seedKeywords : [],
                    locked: this.isPromptTemplateLocked(item.key),
                }));
            const cat = this._strategyCategoryFilter || 'all';
            if (cat === 'all') return all;
            return all.filter(t => t.category === cat);
        },

        strategyCategoryOptions() {
            return [
                { key: 'all', label: '全部' },
                { key: 'general', label: '通用' },
                { key: 'tech', label: '技术' },
                { key: 'product_design', label: '产品设计' },
                { key: 'business', label: '运营商务' },
                { key: 'functional', label: '职能' },
                { key: 'industry', label: '行业' },
            ];
        },

        currentPromptStrategyMeta() {
            const key = this.promptStrategyTemplate || 'general';
            return this.promptStrategyTemplateOptions().find((item) => item.key === key) || this.promptStrategyTemplateOptions()[0] || null;
        },

        promptStrategyTemplateTitle(templateKey) {
            const template = this.promptStrategyTemplateCatalog().find((item) => item.key === templateKey);
            return template?.title || '通用策略';
        },

        promptStrategyKeywordSeeds() {
            return this.promptStrategyTemplateCatalog()
                .reduce((acc, item) => {
                    acc[item.key] = Array.isArray(item.seedKeywords) ? item.seedKeywords : [];
                    return acc;
                }, {});
        },

        syncKeywordsForPromptTemplate(templateKey, maxCount = 10) {
            const key = templateKey || this.promptStrategyTemplate || 'general';
            const seeds = this.promptStrategyKeywordSeeds()[key] || [];
            const extracted = this.mergeKeywordConflicts(this.jdExtractedKeywords || []).merged;
            const normalizedExtracted = extracted.map((item) => this.normalizeKeyword(item));
            const selected = [];

            const pushUnique = (keyword) => {
                const normalized = this.normalizeKeyword(keyword);
                if (!normalized || selected.some((item) => this.normalizeKeyword(item) === normalized)) {
                    return;
                }
                selected.push(normalized);
            };

            seeds.forEach((seed) => {
                const normalizedSeed = this.normalizeKeyword(seed);
                if (normalizedExtracted.includes(normalizedSeed)) {
                    pushUnique(normalizedSeed);
                }
            });
            extracted.forEach((keyword) => pushUnique(keyword));
            this.selectedJdKeywords = selected.slice(0, maxCount);
            return this.selectedJdKeywords;
        },

        promptStrategyRecentGainRows(limit = 3) {
            const templateKey = this.promptStrategyTemplate || 'general';
            const serviceRows = Array.isArray(this.optimizeStrategyGainRows) ? this.optimizeStrategyGainRows : [];
            const matchedServiceRows = serviceRows
                .filter((row) => String(row?.template || '') === templateKey)
                .slice(0, limit)
                .map((row, idx) => ({
                    id: `service_${templateKey}_${idx}`,
                    fromScore: Number(row?.avg_delta || 0) <= 0 ? 0 : 0,
                    toScore: Number(row?.avg_delta || 0),
                    delta: Number(row?.avg_delta || 0),
                    time: row?.latest_at || '',
                }));
            if (matchedServiceRows.length > 0) {
                return matchedServiceRows;
            }
            const history = (Array.isArray(this.optimizeVersionHistory) ? this.optimizeVersionHistory : [])
                .map((row) => ({
                    ...row,
                    promptStrategyTemplate: row?.promptStrategyTemplate || 'general',
                    score: Number(row?.score || 0),
                    createdAt: Number(row?.createdAt || 0),
                }))
                .filter((row) => row.promptStrategyTemplate === templateKey)
                .sort((a, b) => a.createdAt - b.createdAt);

            if (history.length < 2) {
                return [];
            }

            const rows = [];
            for (let i = 1; i < history.length; i += 1) {
                rows.push({
                    id: `${history[i - 1].id || i - 1}_${history[i].id || i}`,
                    fromScore: history[i - 1].score,
                    toScore: history[i].score,
                    delta: history[i].score - history[i - 1].score,
                    time: new Date(history[i].createdAt || Date.now()).toLocaleTimeString(),
                });
            }
            return rows.slice(-limit).reverse();
        },

        applyPromptStrategyTemplate(templateKey) {
            const template = this.promptStrategyTemplateOptions().find((item) => item.key === templateKey);
            if (!template) {
                return;
            }
            if (this.isPromptTemplateLocked(template.key)) {
                this.notifyAdvancedFeatureLocked(`策略包「${template.title}」`);
                return;
            }
            this.promptStrategyTemplate = template.key;
            this.setOptimizeMode(template.mode, { silent: true });
            this.optimizeGoals = this.sanitizeOptimizeGoals(template.goals);
            const nextKeywords = this.syncKeywordsForPromptTemplate(template.key, 10);
            this.showToast(`已切换策略包：${template.title}（已联动 ${nextKeywords.length} 个关键词）`, 'success');
        },

        resolvePromptStrategyTemplateByText(text) {
            const normalizedText = String(text || '').toLowerCase();
            if (normalizedText.trim() === '') {
                return 'general';
            }
            const catalog = this.promptStrategyTemplateOptions();
            let best = { key: 'general', score: 0.1 };

            catalog.forEach((item) => {
                let score = item.key === 'general' ? 0.1 : 0;
                (item.aliases || []).forEach((alias) => {
                    if (normalizedText.includes(String(alias || '').toLowerCase())) {
                        score += 3;
                    }
                });
                (item.seedKeywords || []).forEach((keyword) => {
                    if (normalizedText.includes(String(keyword || '').toLowerCase())) {
                        score += 1;
                    }
                });
                if (score > best.score) {
                    best = { key: item.key, score };
                }
            });

            return best.key || 'general';
        },

        autoDetectPromptStrategyTemplate() {
            const merged = `${this.streamTargetJob || ''} ${this.streamTargetJobTitle || ''} ${this.streamTargetJobDescription || ''}`;
            const key = this.resolvePromptStrategyTemplateByText(merged);
            this.applyPromptStrategyTemplate(key);
            this.autoRecommendOptimizePreset(key, true);
        },
