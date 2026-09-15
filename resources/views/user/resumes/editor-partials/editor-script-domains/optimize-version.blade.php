        recordOptimizeVersion(text, source = 'stream') {
            const normalizedText = String(text || '').trim();
            if (normalizedText === '') {
                return;
            }
            const nextMetrics = this.computeOptimizationMetrics(normalizedText);
            const nextKey = JSON.stringify({
                text: normalizedText,
                mode: this.optimizeMode,
                goals: Array.isArray(this.optimizeGoals) ? [...this.optimizeGoals].sort() : [],
                promptStrategyTemplate: this.promptStrategyTemplate || 'general',
                keywords: this.focusKeywordsForOptimize(),
            });
            if (Array.isArray(this.optimizeVersionHistory) && this.optimizeVersionHistory.some((row) => row.key === nextKey)) {
                return;
            }

            const version = {
                id: `v_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
                key: nextKey,
                text: normalizedText,
                score: Number(nextMetrics?.score || 0),
                mode: this.optimizeMode,
                goals: Array.isArray(this.optimizeGoals) ? [...this.optimizeGoals] : [],
                promptStrategyTemplate: this.promptStrategyTemplate || 'general',
                gapActions: Array.isArray(this.optimizeGapActions) ? [...this.optimizeGapActions] : [],
                createdAt: Date.now(),
                source,
            };
            const nextHistory = [version, ...(Array.isArray(this.optimizeVersionHistory) ? this.optimizeVersionHistory : [])].slice(0, 8);
            this.optimizeVersionHistory = nextHistory;
            this.selectedOptimizeVersionA = this.selectedOptimizeVersionA || version.id;
            this.selectedOptimizeVersionB = this.selectedOptimizeVersionB || (nextHistory[1]?.id || version.id);
        },

        optimizeVersionOptions() {
            return (Array.isArray(this.optimizeVersionHistory) ? this.optimizeVersionHistory : []).map((row) => ({
                id: row.id,
                label: `${new Date(row.createdAt || Date.now()).toLocaleTimeString()} · ${row.score || 0}分 · ${row.mode || 'balanced'}`,
            }));
        },

        getOptimizeVersionById(id) {
            return (Array.isArray(this.optimizeVersionHistory) ? this.optimizeVersionHistory : []).find((row) => row.id === id) || null;
        },

        optimizeVersionCompareSummary() {
            const versionA = this.getOptimizeVersionById(this.selectedOptimizeVersionA);
            const versionB = this.getOptimizeVersionById(this.selectedOptimizeVersionB);
            if (!versionA || !versionB) {
                return null;
            }
            const deltaScore = Number(versionB.score || 0) - Number(versionA.score || 0);
            const deltaLength = String(versionB.text || '').length - String(versionA.text || '').length;
            const goalsA = Array.isArray(versionA.goals) ? versionA.goals : [];
            const goalsB = Array.isArray(versionB.goals) ? versionB.goals : [];
            const goalsChanged = JSON.stringify(goalsA.sort()) !== JSON.stringify(goalsB.sort());
            const modeChanged = (versionA.mode || '') !== (versionB.mode || '');
            return {
                versionA,
                versionB,
                deltaScore,
                deltaLength,
                goalsA,
                goalsB,
                goalsChanged,
                modeChanged,
            };
        },

        useOptimizeVersionAsCurrent(versionId) {
            const version = this.getOptimizeVersionById(versionId);
            if (!version) {
                return;
            }
            this.optimizedContentForDiff = version.text || '';
            const beforeMetrics = this.computeOptimizationMetrics(this.modulesToRawText());
            const afterMetrics = this.computeOptimizationMetrics(this.optimizedContentForDiff);
            this.optimizeMetricsCompare = {
                before: beforeMetrics,
                after: afterMetrics,
                delta: (afterMetrics.score || 0) - (beforeMetrics.score || 0),
            };
            this.keywordCoverageOptimized = this.keywordCoverageStats(this.optimizedContentForDiff);
            this.optimizeGapActions = Array.isArray(version.gapActions) ? version.gapActions : [];
            this.optimizeDiffModules = this.buildOptimizeModuleDiffs();
            this.refreshRegressionValidationSummary();
            this.showToast('已切换为选中优化版本，可继续对比或应用', 'success');
        },

        buildOptimizeModuleDiffs() {
            const beforeModules = this.modules || [];
            const afterText = this.optimizedContentForDiff || '';
            if (!afterText.trim()) return [];
            const afterModules = this.virtualModulesFromRawText(afterText);
            const result = [];
            for (let i = 0; i < Math.max(beforeModules.length, afterModules.length); i++) {
                const before = beforeModules[i];
                const after = afterModules[i];
                const beforeText = before ? this.moduleToText(before) : '';
                const afterText = after ? this.moduleToText(after) : '';
                const label = before ? (this.moduleLabelMap()[before.type] || before.type) : (after ? (this.moduleLabelMap()[after.type] || after.type) : `模块${i + 1}`);
                const changed = beforeText !== afterText;
                result.push({
                    index: i,
                    label,
                    changed,
                    beforeText,
                    afterText,
                    beforeLen: beforeText.length,
                    afterLen: afterText.length,
                    deltaLen: afterText.length - beforeText.length,
                });
            }
            return result;
        },

        async loadOptimizeStrategyGain() {
            if (typeof _urls.optimizeStrategyGainUrl !== 'string' || _urls.optimizeStrategyGainUrl.trim() === '') {
                this.optimizeStrategyGainRows = [];
                return;
            }
            try {
                const response = await fetch(`${_urls.optimizeStrategyGainUrl}?limit=30`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();
                if (response.ok && result?.success && Array.isArray(result.items)) {
                    this.optimizeStrategyGainRows = result.items;
                    return;
                }
            } catch (e) {
                // keep local history fallback
            }
            this.optimizeStrategyGainRows = [];
        },
