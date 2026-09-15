        extractKeywordsFromText(text, maxCount = 18) {
            const source = (text || '').toLowerCase();
            if (source.trim() === '') {
                return [];
            }
            const stopwords = new Set([
                '负责', '进行', '以及', '相关', '优先', '经验', '能力', '岗位', '公司', '工作', '描述', '要求',
                '熟悉', '掌握', '能够', '具备', '以上', '以下', '我们', '你将', '如果', '作为', '参与', '完成',
                'and', 'the', 'for', 'with', 'from', 'that', 'this', 'you', 'will', 'have',
            ]);

            const tokens = source
                .replace(/[\r\n\t]/g, ' ')
                .replace(/[，。；：、,.!?(){}\[\]<>《》""'']/g, ' ')
                .split(/\s+/)
                .map((item) => item.trim())
                .filter((item) => item.length >= 2 && item.length <= 30 && !stopwords.has(item));

            const keywordWeights = {
                laravel: 3.2,
                php: 2.8,
                mysql: 2.6,
                redis: 2.6,
                docker: 2.4,
                kubernetes: 2.5,
                java: 2.3,
                spring: 2.3,
                vue: 2.2,
                react: 2.2,
                python: 2.2,
                golang: 2.2,
                linux: 2.1,
                kafka: 2.1,
                rabbitmq: 2.0,
                elasticsearch: 2.1,
                微服务: 2.4,
                高并发: 2.4,
                rest: 1.8,
                api: 1.8,
                ats: 2.0,
            };
            const titleText = String(this.streamTargetJobTitle || this.streamTargetJob || '').toLowerCase();
            const scores = {};
            tokens.forEach((token) => {
                const normalized = this.normalizeKeyword(token);
                const baseWeight = keywordWeights[normalized] || 1;
                const titleBoost = titleText.includes(normalized) ? 1.4 : 1;
                scores[token] = (scores[token] || 0) + baseWeight * titleBoost;
            });

            return Object.entries(scores)
                .sort((a, b) => b[1] - a[1])
                .slice(0, maxCount)
                .map(([token]) => token);
        },

        keywordSynonymMap() {
            return {
                'laravel框架': 'laravel',
                'laravel framework': 'laravel',
                'php语言': 'php',
                'mysql数据库': 'mysql',
                'redis缓存': 'redis',
                'docker容器': 'docker',
                'k8s': 'kubernetes',
                'ks8': 'kubernetes',
                '微服务架构': '微服务',
                '高并发系统': '高并发',
                'restful': 'rest api',
                'restful api': 'rest api',
            };
        },

        normalizeKeyword(keyword) {
            return String(keyword || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        },

        mergeKeywordConflicts(keywords) {
            const synonymMap = this.keywordSynonymMap();
            const canonicalBucket = {};
            const tips = [];
            (Array.isArray(keywords) ? keywords : []).forEach((keyword) => {
                const normalized = this.normalizeKeyword(keyword);
                if (normalized === '') {
                    return;
                }
                const canonical = synonymMap[normalized] || normalized;
                if (!canonicalBucket[canonical]) {
                    canonicalBucket[canonical] = [];
                }
                canonicalBucket[canonical].push(String(keyword).trim());
            });

            const merged = Object.keys(canonicalBucket);
            Object.entries(canonicalBucket).forEach(([canonical, list]) => {
                const uniq = Array.from(new Set(list.filter(Boolean)));
                if (uniq.length > 1) {
                    tips.push(`关键词"${uniq.join(' / ')}"已合并为"${canonical}"`);
                }
            });

            return {
                merged,
                tips: tips.slice(0, 6),
            };
        },

        async refreshJdKeywordsFromDescription(selectRecommended = true) {
            const mergedText = [
                this.streamTargetJobDescription || '',
                this.streamTargetJobTitle || '',
                this.streamTargetJob || '',
            ].join(' ');
            let extracted = [];

            // 缓存检查
            const cacheKey = 'kw_cache:' + mergedText.trim().substring(0, 200);
            const cached = localStorage.getItem(cacheKey);
            if (cached) {
                try {
                    const parsed = JSON.parse(cached);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        this.jdExtractedKeywords = parsed;
                        if (selectRecommended) this.syncKeywordsForPromptTemplate();
                        return;
                    }
                } catch(e) {}
            }

            const canUseRemote = typeof _urls.keywordExtractUrl === 'string' && _urls.keywordExtractUrl.trim() !== '';
            const isAutoCall = !selectRecommended;
            if (canUseRemote && !isAutoCall) {
                try {
                    let response = await fetch(_urls.keywordExtractUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            text: mergedText,
                            title: String(this.streamTargetJobTitle || this.streamTargetJob || '').trim(),
                            max_count: 20,
                        }),
                    });
                    if (response.status === 403 || response.status === 429) {
                        // 用户手动触发，交给 quotaInterceptor 处理
                        if (window.quotaInterceptor?.handleManualResponse) {
                            response = await window.quotaInterceptor.handleManualResponse(
                                response,
                                window.quotaInterceptor.makeRetryRequest(_urls.keywordExtractUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        text: mergedText,
                                        title: String(this.streamTargetJobTitle || this.streamTargetJob || '').trim(),
                                        max_count: 20,
                                    }),
                                })
                            );
                        }
                    }
                    if (response.ok) {
                        const result = await response.json();
                        if (result?.success && Array.isArray(result.keywords)) {
                            extracted = result.keywords.map((item) => String(item || '').trim()).filter(Boolean);
                        }
                    }
                } catch (e) {
                    if (e?.quotaHandled) {
                        return;
                    }
                    // fallback to local extractor
                }
            }
            if (!Array.isArray(extracted) || extracted.length === 0) {
                extracted = this.extractKeywordsFromText(mergedText, 20);
            }
            const mergedResult = this.mergeKeywordConflicts(extracted);
            this.jdExtractedKeywords = mergedResult.merged;
            this.keywordConflictTips = mergedResult.tips;
            // 缓存结果
            try { localStorage.setItem(cacheKey, JSON.stringify(mergedResult.merged)); } catch(e) {}
            if (selectRecommended && Array.isArray(mergedResult.merged) && mergedResult.merged.length > 0) {
                this.syncKeywordsForPromptTemplate(this.promptStrategyTemplate || 'general', 10);
            }
            if (mergedResult.merged.length === 0) {
                this.showToast('未提取到明显关键词，请补充更完整的岗位描述', 'info');
            } else {
                this.showToast(`已提取 ${mergedResult.merged.length} 个关键词`, 'success');
            }
        },

        toggleJdKeyword(keyword) {
            if (!keyword) {
                return;
            }
            const normalizedKeyword = this.normalizeKeyword(keyword);
            const current = Array.isArray(this.selectedJdKeywords) ? [...this.selectedJdKeywords] : [];
            const idx = current.findIndex((item) => this.normalizeKeyword(item) === normalizedKeyword);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(normalizedKeyword);
            }
            const mergedResult = this.mergeKeywordConflicts(current);
            this.selectedJdKeywords = mergedResult.merged;
            this.keywordConflictTips = mergedResult.tips;
        },

        selectAllJdKeywords() {
            const mergedResult = this.mergeKeywordConflicts(this.jdExtractedKeywords);
            this.selectedJdKeywords = mergedResult.merged;
            this.keywordConflictTips = mergedResult.tips;
        },

        clearSelectedJdKeywords() {
            this.selectedJdKeywords = [];
            this.keywordConflictTips = [];
        },

        focusKeywordsForOptimize() {
            const selected = Array.isArray(this.selectedJdKeywords) ? this.selectedJdKeywords.filter(Boolean) : [];
            if (selected.length > 0) {
                return this.mergeKeywordConflicts(selected).merged;
            }
            const extracted = Array.isArray(this.jdExtractedKeywords) ? this.jdExtractedKeywords.filter(Boolean) : [];
            return this.mergeKeywordConflicts(extracted.slice(0, 8)).merged;
        },

        resumeProfileForOptimize(rawText = null) {
            const text = String(rawText ?? this.modulesToRawText() ?? '');
            const lines = text.split('\n').map((line) => line.trim()).filter(Boolean);
            const bulletLines = lines.filter((line) => line.startsWith('- '));
            const quantifiedBullets = bulletLines.filter((line) => /\d/.test(line));
            const headingCount = lines.filter((line) => line.startsWith('## ')).length;

            const metrics = this.computeOptimizationMetrics(text);
            const coverage = this.keywordCoverageStats(text);
            const keywords = this.focusKeywordsForOptimize();
            const moduleRows = (Array.isArray(this.moduleScoreBreakdown) && this.moduleScoreBreakdown.length > 0)
                ? this.moduleScoreBreakdown
                : this.buildModuleScoreBreakdown();
            const weakModules = moduleRows
                .filter((row) => Number(row?.score || 0) < 70)
                .sort((a, b) => Number(a?.score || 0) - Number(b?.score || 0))
                .slice(0, 4)
                .map((row) => String(row?.label || row?.id || '').trim())
                .filter(Boolean);

            return {
                profile_version: 'v1',
                prompt_strategy_template: this.promptStrategyTemplate || 'general',
                optimize_mode: this.optimizeMode || 'balanced',
                optimize_goals: Array.isArray(this.optimizeGoals) ? Array.from(new Set(this.optimizeGoals)) : [],
                module_count: Array.isArray(this.modules) ? this.modules.length : 0,
                heading_count: headingCount,
                bullet_count: bulletLines.length,
                quantified_bullet_count: quantifiedBullets.length,
                quantified_ratio: bulletLines.length > 0 ? Math.round((quantifiedBullets.length / bulletLines.length) * 100) : 0,
                keyword_total: keywords.length,
                keyword_hit: Number(coverage?.hit || 0),
                keyword_miss: Number(coverage?.miss || 0),
                keyword_missing: Array.isArray(coverage?.missedKeywords) ? coverage.missedKeywords.slice(0, 8) : [],
                weak_modules: weakModules,
                module_strategies: this.moduleStrategies || {},
                module_overall_score: this.moduleOverallScore(moduleRows),
                estimated_optimize_score: Number(metrics?.score || 0),
            };
        },

        jdParseResult: null,
        jdParseLoading: false,

        async parseJdStructure() {
            const jdText = this.streamTargetJobDescription || '';
            if (jdText.trim().length < 20) {
                this.showToast('请先粘贴至少 20 字的岗位描述', 'warning');
                return;
            }
            this.jdParseLoading = true;
            try {
                const resp = await fetch(_urls.keywordParseJdUrl || '/user/resumes/keywords/parse-jd', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ text: jdText }),
                });
                const data = await resp.json();
                if (data?.success) {
                    this.jdParseResult = data;
                }
            } catch (e) {
                this.jdParseResult = null;
            } finally {
                this.jdParseLoading = false;
            }
        },
