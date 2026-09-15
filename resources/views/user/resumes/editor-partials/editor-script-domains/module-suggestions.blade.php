        buildModuleOptimizeSuggestions() {
            const suggestions = [];
            const pushSuggestion = (id, text, action = null) => {
                if (suggestions.some((item) => item.id === id)) {
                    return;
                }
                const resolvedAction = action || id;
                suggestions.push({
                    id,
                    text,
                    action: resolvedAction,
                    impact: this.estimateSuggestionImpact(resolvedAction),
                });
            };
            const focusKeywords = this.focusKeywordsForOptimize();
            const currentCoverage = this.keywordCoverageStats(this.modulesToRawText());
            const lowCompletionModules = this.modules.filter((mod) => this.moduleCompletionPercent(mod) < 55);

            if (focusKeywords.length > 0 && currentCoverage.miss > 0) {
                pushSuggestion('missing-keywords', `补齐缺失关键词：${currentCoverage.missedKeywords.slice(0, 6).join('、')}`, 'apply_missing_keywords');
            }
            if (lowCompletionModules.length > 0) {
                const labels = lowCompletionModules
                    .slice(0, 3)
                    .map((mod) => this.moduleTypeLabel(mod?.type || ''));
                pushSuggestion('low-completion', `优先完善模块：${labels.join('、')}`, 'improve_low_completion');
            }

            const projectLike = this.modules.filter((mod) => ['experience', 'project'].includes(mod.type));
            const quantifiedWeak = projectLike.some((mod) => {
                const items = Array.isArray(mod.data?.items) ? mod.data.items : [];
                return items.length > 0 && items.every((item) => !/\d/.test(String(item || '')));
            });
            if (quantifiedWeak) {
                pushSuggestion('quantified-weak', '经历/项目条目补充可量化数据（如增长率、覆盖规模、耗时优化）', 'enable_quantified');
            }

            if (projectLike.length > 0 && !this.isOptimizeGoalSelected('highlights')) {
                pushSuggestion('enable-highlights', '建议开启"亮点提炼优化"，突出关键成果与业务价值', 'enable_highlights');
            }

            if (!this.isOptimizeGoalSelected('structure') || !this.isOptimizeGoalSelected('language')) {
                pushSuggestion('structure-language', '建议同时开启"结构优化 + 语言表达优化"，提升可读性与通过率', 'enable_structure_language');
            }

            return suggestions.slice(0, 5);
        },

        estimateSuggestionImpact(action) {
            const currentOverall = this.moduleOverallScore(this.moduleScoreBreakdown);
            const coverage = this.keywordCoverageCurrent || this.keywordCoverageStats(this.modulesToRawText());
            const lowCompletionCount = this.modules.filter((mod) => this.moduleCompletionPercent(mod) < 55).length;
            const projectLike = this.modules.filter((mod) => ['experience', 'project'].includes(mod.type));
            const quantifiedWeakCount = projectLike.filter((mod) => {
                const items = Array.isArray(mod.data?.items) ? mod.data.items : [];
                return items.length > 0 && items.every((item) => !/\d/.test(String(item || '')));
            }).length;

            let delta = 2;
            let nextMode = null;
            const addGoals = [];

            if (action === 'apply_missing_keywords') {
                delta = Math.min(12, 4 + Math.max(0, coverage.miss) * 2);
                addGoals.push('ats_keywords', 'skill_match');
            } else if (action === 'improve_low_completion') {
                delta = Math.min(14, 5 + lowCompletionCount * 2);
                addGoals.push('structure', 'language');
                nextMode = this.canUseAdvancedModel() ? 'deep' : 'balanced';
            } else if (action === 'enable_quantified') {
                delta = Math.min(12, 4 + quantifiedWeakCount * 3);
                addGoals.push('quantified', 'highlights');
            } else if (action === 'enable_highlights') {
                delta = 3;
                addGoals.push('highlights');
            } else if (action === 'enable_structure_language') {
                delta = 5;
                addGoals.push('structure', 'language');
            }

            return {
                delta,
                current: currentOverall,
                projected: Math.max(0, Math.min(100, currentOverall + delta)),
                addGoals: Array.from(new Set(addGoals)),
                nextMode,
            };
        },

        applyOptimizeSuggestion(action) {
            const goals = new Set(Array.isArray(this.optimizeGoals) ? this.optimizeGoals : []);
            if (action === 'apply_missing_keywords') {
                goals.add('ats_keywords');
                goals.add('skill_match');
                if ((!Array.isArray(this.selectedJdKeywords) || this.selectedJdKeywords.length === 0) && Array.isArray(this.jdExtractedKeywords)) {
                    this.selectedJdKeywords = this.jdExtractedKeywords.slice(0, 10);
                }
            } else if (action === 'improve_low_completion') {
                goals.add('structure');
                goals.add('language');
                this.setOptimizeMode('deep');
            } else if (action === 'enable_quantified') {
                goals.add('quantified');
                goals.add('highlights');
            } else if (action === 'enable_highlights') {
                goals.add('highlights');
            } else if (action === 'enable_structure_language') {
                goals.add('structure');
                goals.add('language');
            }

            this.optimizeGoals = Array.from(goals);
            this.showToast('已应用建议策略，可直接重新执行优化', 'success');
            this.refreshOptimizeInsights();
        },

        suggestionActionLabel(action) {
            const map = {
                apply_missing_keywords: '补齐关键词匹配',
                improve_low_completion: '完善低完成度模块',
                enable_quantified: '强化量化表达',
                enable_highlights: '开启亮点提炼',
                enable_structure_language: '优化结构与语言',
            };
            return map[action] || '应用建议策略';
        },

        previewApplyOptimizeSuggestion(tip) {
            if (!tip || !tip.action) {
                return;
            }
            const impact = tip.impact || this.estimateSuggestionImpact(tip.action);
            this.suggestionConfirmPayload = {
                id: tip.id || tip.action,
                text: tip.text || this.suggestionActionLabel(tip.action),
                action: tip.action,
                impact,
            };
        },

        cancelApplyOptimizeSuggestion() {
            this.suggestionConfirmPayload = null;
        },

        confirmApplyOptimizeSuggestion() {
            if (!this.suggestionConfirmPayload?.action) {
                return;
            }
            const action = this.suggestionConfirmPayload.action;
            this.suggestionConfirmPayload = null;
            this.applyOptimizeSuggestion(action);
        },

        buildGroupedModuleSuggestions() {
            const weakModules = Array.isArray(this.moduleScoreBreakdown)
                ? this.moduleScoreBreakdown.filter((row) => Number(row.score || 0) < 70).slice(0, 6)
                : [];

            const grouped = {};
            weakModules.forEach((row) => {
                const group = row.label || '未分类模块';
                const tips = [];
                if (Array.isArray(row.weakReasons) && row.weakReasons.length > 0) {
                    row.weakReasons.forEach((reason) => {
                        if (reason === '完整度偏低') {
                            tips.push('补充时间、角色、成果等关键字段');
                        } else if (reason === '量化表达不足') {
                            tips.push('增加可量化结果，如增长率、规模、时延优化');
                        } else if (reason === '关键词覆盖不足') {
                            tips.push('补充与 JD 一致的技能关键词与业务词');
                        }
                    });
                }
                if (tips.length === 0) {
                    tips.push('优化语言表达，突出业务价值与个人贡献');
                }
                grouped[group] = Array.from(new Set([...(grouped[group] || []), ...tips])).slice(0, 3);
            });

            return Object.keys(grouped).map((group) => ({
                group,
                tips: grouped[group],
            }));
        },

        buildSuggestionConflictTips(suggestions = null) {
            const rows = Array.isArray(suggestions) ? suggestions : this.moduleOptimizeSuggestions;
            if (!Array.isArray(rows) || rows.length === 0) {
                return [];
            }

            const tips = [];
            const seenGoalOwners = {};
            rows.forEach((tip) => {
                const action = tip?.action;
                const impact = tip?.impact || this.estimateSuggestionImpact(action);
                const goals = Array.isArray(impact?.addGoals) ? impact.addGoals : [];
                goals.forEach((goal) => {
                    if (seenGoalOwners[goal]) {
                        tips.push(`"${tip.text}"与"${seenGoalOwners[goal]}"都指向 ${goal}，可只应用一个避免重复。`);
                    } else {
                        seenGoalOwners[goal] = tip.text;
                    }
                });
            });

            const hasMissingKeywordsAction = rows.some((tip) => tip?.action === 'apply_missing_keywords');
            const hasKeywords = Array.isArray(this.focusKeywordsForOptimize()) && this.focusKeywordsForOptimize().length > 0;
            if (hasMissingKeywordsAction && !hasKeywords) {
                tips.push('当前无可用关键词，建议先提取 JD 关键词后再应用"补齐关键词匹配"。');
            }

            const hasQuantifiedAction = rows.some((tip) => tip?.action === 'enable_quantified');
            const hasProjectLike = this.modules.some((mod) => ['experience', 'project'].includes(mod.type));
            if (hasQuantifiedAction && !hasProjectLike) {
                tips.push('当前缺少经历/项目模块，"强化量化表达"收益可能受限。');
            }

            return Array.from(new Set(tips)).slice(0, 5);
        },
