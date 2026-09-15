        computeOptimizationMetrics(rawText) {
            const text = (rawText || '').trim();
            if (text === '') {
                return {
                    score: 0,
                    details: { quantified: 0, structure: 0, ats: 0, clarity: 0, richness: 0 },
                };
            }

            const lines = text.split('\n').map((line) => line.trim()).filter(Boolean);
            const bulletLines = lines.filter((line) => line.startsWith('- '));
            const headingLines = lines.filter((line) => line.startsWith('## '));
            const quantifiedLines = bulletLines.filter((line) => /\d/.test(line));
            const focusKeywords = this.focusKeywordsForOptimize();
            const keywordHits = focusKeywords.filter((keyword) => text.toLowerCase().includes(String(keyword).toLowerCase())).length;
            const avgLineLength = lines.reduce((sum, line) => sum + line.length, 0) / Math.max(lines.length, 1);

            const quantified = Math.min(20, Math.round((quantifiedLines.length / Math.max(bulletLines.length, 1)) * 20));
            const structure = Math.min(20, headingLines.length * 4 + Math.min(12, bulletLines.length));
            const ats = Math.min(20, Math.round((keywordHits / Math.max(focusKeywords.length, 1)) * 20));
            const clarity = Math.max(6, 20 - Math.min(14, Math.abs(avgLineLength - 34) / 2));
            const richness = Math.min(20, Math.round(Math.min(text.length, 2400) / 120));
            const score = Math.round(quantified + structure + ats + clarity + richness);

            return {
                score: Math.max(0, Math.min(100, score)),
                details: { quantified, structure, ats, clarity, richness },
            };
        },

        keywordCoverageStats(rawText) {
            const text = String(rawText || '').toLowerCase();
            const keywords = this.focusKeywordsForOptimize();
            if (keywords.length === 0) {
                return {
                    total: 0,
                    hit: 0,
                    miss: 0,
                    rate: 0,
                    hitKeywords: [],
                    missedKeywords: [],
                };
            }

            const hitKeywords = [];
            const missedKeywords = [];
            keywords.forEach((keyword) => {
                const normalized = String(keyword || '').toLowerCase().trim();
                if (normalized === '') {
                    return;
                }
                if (text.includes(normalized)) {
                    hitKeywords.push(keyword);
                } else {
                    missedKeywords.push(keyword);
                }
            });

            const total = hitKeywords.length + missedKeywords.length;
            return {
                total,
                hit: hitKeywords.length,
                miss: missedKeywords.length,
                rate: total > 0 ? Math.round((hitKeywords.length / total) * 100) : 0,
                hitKeywords,
                missedKeywords,
            };
        },

        moduleKeywordCoverage(mod) {
            const keywords = this.focusKeywordsForOptimize();
            if (!Array.isArray(keywords) || keywords.length === 0) {
                return { total: 0, hit: 0, miss: 0, rate: 0, hitKeywords: [], missedKeywords: [] };
            }

            const text = this.moduleTextForAnalyze(mod).toLowerCase();
            const hitKeywords = [];
            const missedKeywords = [];
            keywords.forEach((keyword) => {
                const normalized = String(keyword || '').toLowerCase().trim();
                if (normalized === '') {
                    return;
                }
                if (text.includes(normalized)) {
                    hitKeywords.push(keyword);
                } else {
                    missedKeywords.push(keyword);
                }
            });

            const total = hitKeywords.length + missedKeywords.length;
            return {
                total,
                hit: hitKeywords.length,
                miss: missedKeywords.length,
                rate: total > 0 ? Math.round((hitKeywords.length / total) * 100) : 0,
                hitKeywords,
                missedKeywords,
            };
        },

        moduleStrategyOptions() {
            return [
                { key: 'balanced', label: '平衡优化' },
                { key: 'results', label: '结果导向' },
                { key: 'technical', label: '技术细节' },
            ];
        },

        moduleStrategyLabel(mod) {
            const key = String(this.moduleStrategies?.[mod?._key] || 'balanced');
            const row = this.moduleStrategyOptions().find((item) => item.key === key);
            return row?.label || '平衡优化';
        },

        setModuleStrategy(mod, strategy) {
            if (!mod?._key) {
                return;
            }
            const options = this.moduleStrategyOptions().map((item) => item.key);
            const next = options.includes(strategy) ? strategy : 'balanced';
            this.moduleStrategies = {
                ...(this.moduleStrategies || {}),
                [mod._key]: next,
            };
            this.showToast(`已切换为${this.moduleStrategyLabel(mod)}`, 'info');
        },

        weakWordDictionary() {
            return {
                '负责': '主导',
                '参与': '推动',
                '协助': '独立完成',
                '进行': '推进',
                '完成': '交付',
                '相关': '核心',
            };
        },

        moduleWeakWordHits(mod) {
            const dict = this.weakWordDictionary();
            const text = this.moduleTextForAnalyze(mod);
            if (!text) {
                return [];
            }
            return Object.keys(dict)
                .filter((word) => text.includes(word))
                .map((word) => ({
                    word,
                    suggestion: dict[word],
                }));
        },

        rewriteModuleWeakWords(index) {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }
            const hits = this.moduleWeakWordHits(mod);
            if (hits.length === 0) {
                this.showToast('当前模块未检测到明显弱词', 'info');
                return;
            }

            const dict = this.weakWordDictionary();
            const replaceText = (value) => {
                let result = String(value || '');
                Object.entries(dict).forEach(([from, to]) => {
                    result = result.split(from).join(to);
                });
                return result;
            };

            this.pushUndo();
            const next = JSON.parse(JSON.stringify(mod));
            if (typeof next.data?.content === 'string') {
                next.data.content = replaceText(next.data.content);
            }
            if (typeof next.data?.subtitle === 'string') {
                next.data.subtitle = replaceText(next.data.subtitle);
            }
            if (Array.isArray(next.data?.items)) {
                next.data.items = next.data.items.map((item) => replaceText(item));
            }
            this.modules.splice(index, 1, next);
            this.activeIndex = index;
            this.dirty = true;
            this.showToast(`已优化 ${hits.length} 个弱词表达`, 'success');
            this.scheduleInsightsRefresh(80);
        },

        moduleWeightByType(type) {
            return {
                personal: 16,
                objective: 14,
                experience: 22,
                project: 20,
                education: 10,
                skill: 10,
                certificate: 5,
                summary: 3,
            }[type] || 6;
        },

        modulePriorityScore(mod) {
            const base = this.moduleWeightByType(mod?.type);
            const completion = this.moduleCompletionPercent(mod);
            const coverage = this.moduleKeywordCoverage(mod);
            const weakPenalty = this.moduleWeakWordHits(mod).length * 2;
            const keywordBonus = coverage.total > 0 ? Math.round((coverage.rate / 100) * 12) : 0;
            return Math.max(0, Math.round(base + completion * 0.45 + keywordBonus - weakPenalty));
        },

        refreshRecommendedModuleOrder() {
            const rows = (this.modules || []).map((mod, index) => ({
                index,
                key: mod?._key,
                type: mod?.type || '',
                label: this.moduleTypeLabel(mod?.type || '模块'),
                score: this.modulePriorityScore(mod),
            }));
            this.recommendedModuleOrder = rows.sort((a, b) => Number(b.score || 0) - Number(a.score || 0));
        },

        recommendedModuleOrderText(limit = 4) {
            const rows = Array.isArray(this.recommendedModuleOrder) ? this.recommendedModuleOrder.slice(0, limit) : [];
            if (rows.length === 0) {
                return '暂无排序建议';
            }
            return rows.map((row, idx) => `${idx + 1}.${row.label}`).join(' -> ');
        },

        applyRecommendedModuleOrder() {
            if (!Array.isArray(this.recommendedModuleOrder) || this.recommendedModuleOrder.length === 0) {
                this.showToast('暂无可应用的排序建议', 'info');
                return;
            }
            const keyOrder = this.recommendedModuleOrder.map((row) => row.key).filter(Boolean);
            const mapping = new Map((this.modules || []).map((mod) => [mod._key, mod]));
            const nextModules = keyOrder.map((key) => mapping.get(key)).filter(Boolean);
            if (nextModules.length !== this.modules.length) {
                this.showToast('排序建议数据不完整，请刷新后重试', 'warning');
                return;
            }
            this.pushUndo();
            this.modules = nextModules.map((mod, idx) => ({ ...mod, sort_order: idx }));
            this.activeIndex = 0;
            this.dirty = true;
            this.showToast('已应用模块排序建议', 'success');
            this.$nextTick(() => this.syncPreviewModuleAction());
        },

        moduleExplainabilityTips(mod) {
            if (!mod) {
                return [];
            }

            const tips = [];
            const score = this.moduleCompletionPercent(mod);
            const coverage = this.moduleKeywordCoverage(mod);
            const itemsCount = this.filledItemsCount(mod?.data?.items);
            const contentLength = this.textLength(mod?.data?.content);

            if (score < 35) {
                tips.push('基础字段较少，优先补齐必填信息与关键条目。');
            } else if (score < 75) {
                tips.push('模块可读性已具备，建议补 1-2 条量化成果提升说服力。');
            } else {
                tips.push('结构完整度较高，可继续优化措辞和结果表达。');
            }

            if (['experience', 'project'].includes(mod?.type) && itemsCount < 2) {
                tips.push('建议至少保留 2 条职责/亮点，便于 ATS 和面试官快速判断价值。');
            }

            if (['experience', 'project'].includes(mod?.type) && itemsCount > 0) {
                const quantified = (mod.data.items || []).filter((item) => /\d/.test(String(item || ''))).length;
                if (quantified === 0) {
                    tips.push('当前缺少量化表达，可加入效率、成本、转化等数字结果。');
                }
            }

            if (mod?.type === 'summary' && contentLength < 80) {
                tips.push('自我评价建议 80 字以上，包含年限、技术栈和业务场景。');
            }

            if (coverage.total > 0) {
                if (coverage.rate < 50) {
                    tips.push(`ATS 关键词命中率 ${coverage.rate}% 偏低，建议补充岗位关键词。`);
                } else if (coverage.rate < 80) {
                    tips.push(`ATS 关键词命中率 ${coverage.rate}% ，可再补 1-2 个关键词。`);
                } else {
                    tips.push(`ATS 关键词命中率 ${coverage.rate}% ，匹配表现良好。`);
                }
            } else {
                tips.push('先填写岗位描述并提取关键词，可开启模块级 ATS 命中提示。');
            }

            return tips.slice(0, 4);
        },

        injectKeywordToModule(index, keyword) {
            const mod = this.modules[index];
            const token = String(keyword || '').trim();
            if (!mod || token === '') {
                return;
            }

            const normalizedToken = token.toLowerCase();
            const hasToken = this.moduleTextForAnalyze(mod).toLowerCase().includes(normalizedToken);
            if (hasToken) {
                this.showToast(`「${token}」已在当前模块中`, 'info');
                return;
            }

            this.pushUndo();
            const next = JSON.parse(JSON.stringify(mod));
            const data = next.data || {};

            if (Array.isArray(data.items) && ['experience', 'project', 'skill', 'certificate', 'education'].includes(next.type)) {
                data.items = [...data.items, `掌握/应用 ${token}`];
            } else if (typeof data.content === 'string') {
                data.content = data.content.trim() === '' ? `具备 ${token} 相关实践经验。` : `${data.content}\n- 掌握 ${token} 并应用于业务场景。`;
            } else {
                data.content = `具备 ${token} 相关实践经验。`;
            }

            next.data = data;
            this.modules.splice(index, 1, next);
            this.activeIndex = index;
            this.dirty = true;
            this.showToast(`已将关键词「${token}」补入当前模块`, 'success');
            this.scheduleInsightsRefresh(80);
        },

        @include('user.resumes.editor-partials.editor-script-domains.module-suggestions')

        moduleOverallScore(breakdown = null) {
            const rows = Array.isArray(breakdown) ? breakdown : this.moduleScoreBreakdown;
            if (!Array.isArray(rows) || rows.length === 0) {
                return 0;
            }
            const total = rows.reduce((sum, row) => sum + Number(row?.score || 0), 0);
            return Math.round(total / rows.length);
        },

        recordModuleScoreSnapshot(reason = 'auto') {
            const score = this.moduleOverallScore(this.moduleScoreBreakdown);
            const history = Array.isArray(this.moduleScoreHistory) ? [...this.moduleScoreHistory] : [];
            const now = Date.now();
            const last = history.length > 0 ? history[history.length - 1] : null;
            if (last) {
                const withinShortWindow = now - Number(last.ts || 0) < 15000;
                const tinyChange = Math.abs(score - Number(last.score || 0)) < 2;
                if (withinShortWindow && tinyChange) {
                    return;
                }
            }

            history.push({
                ts: now,
                score,
                reason,
                label: new Date(now).toLocaleTimeString('zh-CN', { hour12: false, hour: '2-digit', minute: '2-digit' }),
            });

            this.moduleScoreHistory = history.slice(-12);
        },

        moduleScoreTrendDelta() {
            if (!Array.isArray(this.moduleScoreHistory) || this.moduleScoreHistory.length < 2) {
                return 0;
            }
            const first = this.moduleScoreHistory[0]?.score || 0;
            const last = this.moduleScoreHistory[this.moduleScoreHistory.length - 1]?.score || 0;
            return Number(last) - Number(first);
        },

        @include('user.resumes.editor-partials.editor-script-domains.text-analysis')

        buildModuleScoreBreakdownByModules(modules) {
            const focusKeywords = this.focusKeywordsForOptimize();
            const sourceModules = Array.isArray(modules) ? modules : [];
            return sourceModules.map((mod) => {
                const completion = this.moduleCompletionPercent(mod);
                const text = this.moduleTextForAnalyze(mod);
                const type = mod?.type || 'other';
                const isProjectLike = ['experience', 'project'].includes(type);
                const items = Array.isArray(mod?.data?.items) ? mod.data.items.map((item) => String(item || '')) : [];
                const quantifiedRate = isProjectLike
                    ? (items.length > 0 ? Math.round((items.filter((item) => /\d/.test(item)).length / items.length) * 100) : 35)
                    : 55;
                const keywordRate = focusKeywords.length > 0
                    ? Math.round((focusKeywords.filter((kw) => text.toLowerCase().includes(String(kw).toLowerCase())).length / focusKeywords.length) * 100)
                    : 60;
                const score = Math.round(completion * 0.5 + quantifiedRate * 0.25 + keywordRate * 0.25);
                const weakReasons = [];
                if (completion < 55) weakReasons.push('完整度偏低');
                if (isProjectLike && quantifiedRate < 50) weakReasons.push('量化表达不足');
                if (focusKeywords.length > 0 && keywordRate < 50) weakReasons.push('关键词覆盖不足');

                return {
                    id: mod.id,
                    label: this.moduleTypeLabel(type),
                    score: Math.max(0, Math.min(100, score)),
                    completion,
                    quantifiedRate,
                    keywordRate,
                    weakReasons,
                };
            }).sort((a, b) => a.score - b.score);
        },

        buildModuleScoreBreakdown() {
            return this.buildModuleScoreBreakdownByModules(this.modules);
        },

        isKeywordCovered(keyword) {
            const kw = String(keyword || '').toLowerCase();
            if (!kw) return false;
            return this.modules.some(mod => {
                const text = this.moduleToText(mod).toLowerCase();
                return text.includes(kw);
            });
        },

        matchHeatmapData() {
            const targetJob = (this.streamTargetJob || '').trim().toLowerCase();
            const jdText = (this.streamTargetJobDescription || '').trim().toLowerCase();
            const keywords = (this.jdExtractedKeywords || []).map(k => String(k).toLowerCase());
            const combinedTarget = targetJob + ' ' + jdText;

            if (!combinedTarget.trim() || this.modules.length === 0) {
                return { modules: [], overall: 0, keywordCoverage: 0 };
            }

            const moduleTypeLabels = {
                personal: '个人信息', objective: '求职意向', education: '教育经历',
                experience: '实习经历', project: '项目经验', skill: '技能特长',
                certificate: '证书荣誉', summary: '个人简介',
            };

            let totalHit = 0;
            let totalKeywords = keywords.length || 1;

            const moduleScores = this.modules.map((mod, idx) => {
                const text = this.moduleToText(mod).toLowerCase();
                let score = 0;
                let reasons = [];

                // 关键词命中
                const hitKw = keywords.filter(kw => text.includes(kw));
                const missKw = keywords.filter(kw => !text.includes(kw));
                const kwScore = keywords.length > 0 ? Math.round((hitKw.length / keywords.length) * 40) : 0;
                score += kwScore;
                totalHit += hitKw.length;
                if (hitKw.length > 0) reasons.push(`命中 ${hitKw.length} 个关键词`);

                // 目标岗位相关性
                const targetWords = combinedTarget.split(/[\s,，。、；：]+/).filter(w => w.length >= 2);
                const hitTarget = targetWords.filter(w => text.includes(w));
                const relScore = targetWords.length > 0 ? Math.round((hitTarget.length / targetWords.length) * 30) : 0;
                score += relScore;

                // 内容丰富度
                const bulletCount = (text.match(/^- /gm) || []).length;
                const hasQuantify = /\d/.test(text);
                const richScore = Math.min(20, bulletCount * 3 + (hasQuantify ? 10 : 0));
                score += richScore;
                if (hasQuantify) reasons.push('含量化数据');

                // 模块完整度
                const completionScore = Math.min(10, Math.round(this.moduleCompletionPercent(mod) / 10));
                score += completionScore;

                score = Math.min(100, score);

                let level = 'low';
                if (score >= 70) level = 'high';
                else if (score >= 40) level = 'medium';

                return {
                    index: idx,
                    type: mod.type,
                    label: moduleTypeLabels[mod.type] || mod.type,
                    score,
                    level,
                    hitKeywords: hitKw.slice(0, 5),
                    missKeywords: missKw.slice(0, 5),
                    reasons,
                };
            });

            const overall = moduleScores.length > 0
                ? Math.round(moduleScores.reduce((s, m) => s + m.score, 0) / moduleScores.length)
                : 0;

            const keywordCoverage = totalKeywords > 0 ? Math.round((totalHit / totalKeywords) * 100) : 0;

            // 维度评分
            const totalModules = this.modules.length || 1;
            const modulesWithQuant = this.modules.filter(mod => /\d/.test(this.moduleToText(mod))).length;
            const avgCompletion = Math.round(this.modules.reduce((s, mod) => s + this.moduleCompletionPercent(mod), 0) / totalModules);
            const richnessScore = Math.min(100, Math.round((moduleScores.reduce((s, m) => s + (m.hitKeywords?.length || 0), 0) / totalModules) * 15 + (modulesWithQuant / totalModules) * 40));

            const dimensions = [
                { key: 'keyword', label: '关键词覆盖', score: keywordCoverage, icon: 'ti ti-key' },
                { key: 'relevance', label: '岗位相关性', score: overall, icon: 'ti ti-target' },
                { key: 'richness', label: '内容丰富度', score: richnessScore, icon: 'ti ti-chart-bar' },
                { key: 'completion', label: '模块完整度', score: avgCompletion, icon: 'ti ti-checklist' },
            ];

            return { modules: moduleScores, overall, keywordCoverage, dimensions };
        },

        refreshRegressionValidationSummary() {
            if (!this.optimizedContentForDiff || !this.optimizeMetricsCompare) {
                this.regressionValidationSummary = null;
                return;
            }

            const currentWeakCount = Array.isArray(this.moduleScoreBreakdown)
                ? this.moduleScoreBreakdown.filter((row) => Number(row.score || 0) < 70).length
                : 0;
            const optimizedModules = this.virtualModulesFromRawText(this.optimizedContentForDiff);
            const optimizedBreakdown = this.buildModuleScoreBreakdownByModules(optimizedModules);
            const optimizedWeakCount = optimizedBreakdown.filter((row) => Number(row.score || 0) < 70).length;
            const keywordRateCurrent = Number(this.keywordCoverageCurrent?.rate || 0);
            const keywordRateOptimized = Number(this.keywordCoverageOptimized?.rate || 0);
            const deltaScore = Number(this.optimizeMetricsCompare?.delta || 0);
            const deltaKeywordRate = keywordRateOptimized - keywordRateCurrent;
            const deltaWeakModules = optimizedWeakCount - currentWeakCount;
            const riskCount = Array.isArray(this.diffRiskTips) ? this.diffRiskTips.length : 0;

            this.regressionValidationSummary = {
                atsBefore: Number(this.resumeAtsScore ?? this.optimizeMetricsCompare?.before?.details?.ats ?? 0),
                atsAfter: Number(this.optimizeMetricsCompare?.after?.details?.ats ?? 0),
                keywordRateBefore: keywordRateCurrent,
                keywordRateAfter: keywordRateOptimized,
                deltaScore,
                deltaKeywordRate,
                weakModulesBefore: currentWeakCount,
                weakModulesAfter: optimizedWeakCount,
                deltaWeakModules,
                riskCount,
                status: (deltaScore >= 0 && deltaKeywordRate >= 0 && deltaWeakModules <= 0 && riskCount === 0) ? 'ok' : 'warn',
            };
        },

        refreshOptimizeInsights(recordHistory = true) {
            this.keywordCoverageCurrent = this.keywordCoverageStats(this.modulesToRawText());
            this.moduleOptimizeSuggestions = this.buildModuleOptimizeSuggestions();
            this.suggestionConflictTips = this.buildSuggestionConflictTips(this.moduleOptimizeSuggestions);
            this.moduleScoreBreakdown = this.buildModuleScoreBreakdown();
            this.moduleGroupedSuggestions = this.buildGroupedModuleSuggestions();
            this.currentModuleOverallScore = this.moduleOverallScore(this.moduleScoreBreakdown);
            this.refreshRecommendedModuleOrder();
            this.refreshRegressionValidationSummary();
            if (recordHistory) {
                this.recordModuleScoreSnapshot('refresh');
            }
        },

        optimizeButtonText() {
            if (!this.hasTargetJob()) {
                return '先填目标信息';
            }
            if (this.isOptimizeStale()) {
                return '重新优化';
            }
            if (this.optimizedContentForDiff !== '') {
                return '再次优化';
            }
            return '开始优化';
        },

        targetJobHintText() {
            if (!this.hasTargetJob()) {
                return '可填写目标岗位、目标公司、岗位名称或岗位描述后执行 AI 优化。';
            }
            if (this.isOptimizeStale()) {
                return '优化目标或内容已变化，建议重新优化。';
            }
            const targetParts = [];
            if ((this.streamTargetCompany || '').trim() !== '') {
                targetParts.push(this.streamTargetCompany.trim());
            }
            if ((this.streamTargetJobTitle || '').trim() !== '') {
                targetParts.push(this.streamTargetJobTitle.trim());
            }
            const mergedTarget = targetParts.join(' / ');
            if (mergedTarget !== '') {
                return `当前优化目标：${mergedTarget}`;
            }
            return `当前目标岗位：${(this.streamTargetJob || '').trim()}`;
        },

        moduleSummary(mod) {
            const data = mod?.data || {};
            if (mod?.type === 'personal') {
                return [data.name, data.phone, data.email].filter(Boolean).join(' / ') || '姓名、电话、邮箱等基础信息';
            }
            if (mod?.type === 'objective') {
                return data.target_job || data.content || '填写目标岗位与求职方向';
            }
            if (typeof data.subtitle === 'string' && data.subtitle.trim() !== '') {
                return data.subtitle.trim();
            }
            if (typeof data.content === 'string' && data.content.trim() !== '') {
                return data.content.trim();
            }
            if (Array.isArray(data.items) && data.items.length > 0) {
                const firstItem = data.items.find((item) => typeof item === 'string' && item.trim() !== '');
                if (firstItem) {
                    return firstItem.trim();
                }
            }
            return '点击展开编辑此模块内容';
        },
