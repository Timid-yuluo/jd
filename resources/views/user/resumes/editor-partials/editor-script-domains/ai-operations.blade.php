        async streamOptimize() {
            const hasOptimizeTarget = this.hasTargetJob();
            if (!hasOptimizeTarget) {
                this.showToast('请至少填写目标岗位、目标公司、岗位名称或岗位描述之一', 'warning');
                return;
            }
            const optimizeSignature = this.buildOptimizeSignature();
            if (_state.lastOptimizeSignature === optimizeSignature && this.optimizedContentForDiff !== '') {
                this.showToast('内容和目标岗位未变化，无需重复优化', 'info');
                return;
            }
            if (!this.tryStartAiAction('streamOptimize', 1000)) {
                return;
            }
            const beforeMetrics = this.computeOptimizationMetrics(this.modulesToRawText());
            this.streamStatus = 'running';
            this.streamText = '';
            this.streamHighlights = [];
            this.optimizeChangesSummary = '';
            this.lastAiErrorMessage = '';
            this.lastAiAttemptedDrivers = [];
            this.currentAiDriver = '';
            this.fallbackFromDriver = '';
            this.startDeepProgressivePreview();
            try {
                const primaryChannel = String(_urls.optimizePrimaryChannel || 'session').trim();
                const canUseSessionChannel = _urls.optimizeSessionEnabled && !!_urls.optimizeSessionCreateUrl;

                if (primaryChannel !== 'session') {
                    console.warn('Resume optimize primary channel is forced to session on this page.', primaryChannel);
                }
                if (!canUseSessionChannel) {
                    throw new Error('当前环境未启用会话化优化，请联系管理员开启新流程。');
                }
                await this.runOptimizeViaSession(optimizeSignature, beforeMetrics);
            } catch (err) {
                if (err?.quotaHandled) {
                    this.streamStatus = 'idle';
                    this.stopDeepProgressivePreview();
                    return;
                }
                this.streamStatus = 'error';
                this.stopDeepProgressivePreview();
                this.showToast(err instanceof Error && err.message ? err.message : '请求失败，请检查网络后重试', 'danger');
            } finally {
                this.stopDeepProgressivePreview();
                this.finishAiAction('streamOptimize');
            }
        },

        async optimizeSingleModule(moduleIndex) {
            const mod = this.modules[moduleIndex];
            if (!mod) return;
            const typeLabels = {education:'教育经历',experience:'实习经历',project:'项目经验',skill:'技能特长',certificate:'证书荣誉',objective:'求职意向',summary:'个人简介',personal:'个人信息'};
            const label = typeLabels[mod.type] || mod.type;

            const confirmed = typeof window.appConfirm === 'function'
                ? await window.appConfirm(`确定要 AI 优化「${label}」模块？优化后会替换该模块内容。`, { title: '分段优化确认', showCancel: true })
                : false;
            if (!confirmed) return;

            const sectionUrl = _urls.optimizeSectionUrl || '';
            if (!sectionUrl) {
                this.showToast('分段优化接口未配置', 'error');
                return;
            }

            this.tryStartAiAction('optimize-section');
            try {
                const response = await fetch(sectionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        section_type: mod.type,
                        content: this.moduleToText(mod),
                        target_job: this.streamTargetJob || '',
                    }),
                });
                const result = await response.json();
                if (result?.success && result.optimized_text) {
                    const items = mod.data?.items;
                    if (Array.isArray(items) && items.length > 0) {
                        items[0].description = result.optimized_text;
                    } else {
                        mod.data.content = result.optimized_text;
                    }
                    this.markDirty();
                    this.showToast(`「${label}」已优化`, 'success');
                } else {
                    this.showToast(result?.message || '优化失败', 'error');
                }
            } catch (e) {
                this.showToast('优化请求失败', 'error');
            } finally {
                this.releaseAiAction('optimize-section');
            }
        },

        shouldUseDeepProgressivePreview() {
            return this.optimizeMode === 'deep' && _urls.deepProgressivePreviewEnabled === true;
        },

        startDeepProgressivePreview() {
            this.stopDeepProgressivePreview();
            if (!this.shouldUseDeepProgressivePreview()) {
                this.streamText = 'AI 已连接，正在实时生成...';
                return;
            }

            const stageRows = [
                ['阶段 1', '岗位与简历信号对齐中'],
                ['阶段 2', '经历结构深度重排中'],
                ['阶段 3', '量化/关键词细化与润色中'],
                ['阶段 4', '生成最终可回写版本中'],
            ];
            let stageIndex = 0;
            const render = () => {
                this.streamText = stageRows
                    .map((row, idx) => {
                        if (idx < stageIndex) {
                            return `- ${row[0]}：${row[1]}（完成）`;
                        }
                        if (idx === stageIndex) {
                            return `- ${row[0]}：${row[1]}...`;
                        }
                        return `- ${row[0]}：等待`;
                    })
                    .join('\n');
            };
            render();
            _state.deepProgressiveTimer = setInterval(() => {
                stageIndex = Math.min(stageRows.length - 1, stageIndex + 1);
                render();
            }, Math.max(600, Number(_urls.deepProgressivePreviewTickMs || 1200)));
        },

        stopDeepProgressivePreview() {
            if (_state.deepProgressiveTimer) {
                clearInterval(_state.deepProgressiveTimer);
                _state.deepProgressiveTimer = null;
            }
        },

        warmupCacheKey() {
            return `resume-editor-sse-warmup:${this.resumeId || 'unknown'}`;
        },

        isWarmupExpired(lastAtMs) {
            const ttlMs = Math.max(60, Number(_urls.optimizeStreamPrewarmTtlSeconds || 900)) * 1000;
            return !lastAtMs || (Date.now() - lastAtMs) > ttlMs;
        },

        async warmupOptimizeStreamChannel() {
            if (_urls.optimizeStreamPrewarmEnabled !== true) {
                return;
            }
            if (typeof _urls.optimizeStreamPrewarmUrl !== 'string' || _urls.optimizeStreamPrewarmUrl.trim() === '') {
                return;
            }

            const cacheKey = this.warmupCacheKey();
            const lastAt = Number(window.sessionStorage.getItem(cacheKey) || 0);
            if (!this.isWarmupExpired(lastAt)) {
                return;
            }

            try {
                const response = await fetch(_urls.optimizeStreamPrewarmUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    quotaIntercept: false,
                });
                if (response.ok) {
                    window.sessionStorage.setItem(cacheKey, String(Date.now()));
                }
                // 403/429 quota errors are silently ignored — warmup is best-effort only
            } catch (e) {
                // warmup is best-effort only
            }
        },

        isStreamDegradeModeAllowed(mode = null) {
            const normalized = String(mode || this.optimizeMode || '').trim();
            return normalized === 'quick' || normalized === 'balanced';
        },

        applyStreamFallbackPayload(payload = {}) {
            if (!payload || typeof payload !== 'object') {
                return;
            }

            if (typeof payload.target_job === 'string') {
                this.streamTargetJob = payload.target_job;
            }
            if (typeof payload.target_company === 'string') {
                this.streamTargetCompany = payload.target_company;
            }
            if (typeof payload.target_job_title === 'string') {
                this.streamTargetJobTitle = payload.target_job_title;
            }
            if (typeof payload.target_job_description === 'string') {
                this.streamTargetJobDescription = payload.target_job_description;
            }

            const fallbackMode = String(payload.optimize_mode || '').trim();
            if (this.isStreamDegradeModeAllowed(fallbackMode)) {
                this.optimizeMode = fallbackMode;
            }

            if (typeof payload.prompt_strategy_template === 'string' && payload.prompt_strategy_template.trim() !== '') {
                this.promptStrategyTemplate = payload.prompt_strategy_template.trim();
            }

            if (Array.isArray(payload.optimize_goals)) {
                this.optimizeGoals = typeof this.sanitizeOptimizeGoals === 'function'
                    ? this.sanitizeOptimizeGoals(payload.optimize_goals)
                    : payload.optimize_goals.filter(Boolean);
            }

            if (Array.isArray(payload.focus_keywords)) {
                const keywords = payload.focus_keywords
                    .map((item) => String(item || '').trim())
                    .filter(Boolean);
                this.jdExtractedKeywords = keywords;
                this.selectedJdKeywords = keywords;
            }

            if (payload.module_strategies && typeof payload.module_strategies === 'object') {
                this.moduleStrategies = { ...payload.module_strategies };
            }
        },

        optimizeRequestPayload(overrides = {}) {
            const payload = {
                target_job: this.streamTargetJob,
                target_company: this.streamTargetCompany,
                target_job_title: this.streamTargetJobTitle,
                target_job_description: this.streamTargetJobDescription,
                optimize_goals: this.optimizeGoals,
                optimize_mode: this.optimizeMode,
                prompt_strategy_template: this.promptStrategyTemplate || 'general',
                focus_keywords: this.focusKeywordsForOptimize(),
                module_strategies: this.moduleStrategies || {},
                resume_profile: this.resumeProfileForOptimize(),
            };

            if (!overrides || typeof overrides !== 'object') {
                return payload;
            }

            return {
                ...payload,
                ...overrides,
            };
        },

        extractStructuredErrorMessage(payload) {
            if (!payload || typeof payload !== 'object') {
                return '';
            }

            if (typeof payload.error === 'string' && payload.error.trim() !== '') {
                return payload.error.trim();
            }

            if (typeof payload.message === 'string' && payload.message.trim() !== '') {
                const message = payload.message.trim();
                const retryAfter = Number(payload.retry_after ?? payload.retryAfter ?? 0);
                if (retryAfter > 0 && /过于频繁|稍后再试|too many requests/i.test(message)) {
                    return `${message} 请在 ${retryAfter} 秒后重试。`;
                }

                return message;
            }

            if (payload.errors && typeof payload.errors === 'object') {
                const firstKey = Object.keys(payload.errors)[0];
                const firstError = firstKey ? payload.errors[firstKey] : null;
                if (Array.isArray(firstError) && firstError.length > 0) {
                    return String(firstError[0] || '').trim();
                }
            }

            return '';
        },

        extractSseErrorMessage(errorText) {
            const raw = String(errorText || '').trim();
            if (raw === '') {
                return '';
            }

            const jsonMatch = raw.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                try {
                    const parsed = JSON.parse(jsonMatch[0]);
                    const structuredMessage = this.extractStructuredErrorMessage(parsed);
                    if (structuredMessage !== '') {
                        return structuredMessage;
                    }
                } catch (_error) {
                    // fall through to text parsing
                }
            }

            const dataLines = raw
                .split('\n')
                .map((line) => line.trim())
                .filter((line) => line.startsWith('data:'));

            for (const line of dataLines) {
                const payload = line.replace(/^data:\s*/, '').trim();
                if (!payload) {
                    continue;
                }
                try {
                    const parsed = JSON.parse(payload);
                    const structuredMessage = this.extractStructuredErrorMessage(parsed);
                    if (structuredMessage !== '') {
                        return structuredMessage;
                    }
                } catch (_error) {
                    if (payload !== '') {
                        return payload;
                    }
                }
            }

            return raw;
        },

        tryHandleQuotaJsonText(errorText, requestInit) {
            const raw = String(errorText || '').trim();
            if (raw === '' || !window.quotaInterceptor?.processQuotaPayload) {
                return false;
            }

            let parsed = null;
            try {
                parsed = JSON.parse(raw);
            } catch (_error) {
                return false;
            }

            if (!parsed || typeof parsed !== 'object' || typeof parsed.code !== 'string') {
                return false;
            }

            const handled = window.quotaInterceptor.processQuotaPayload(
                parsed,
                window.quotaInterceptor.makeRetryRequest(_urls.optimizeStreamUrl, requestInit, { preserveAccept: true }),
                () => {}
            );

            return handled?.handled === true;
        },

        async quotaAwareFetch(url, requestInit, retryOptions = {}) {
            const quotaFetch = window.quotaInterceptor?.fetch;
            let response = quotaFetch
                ? await quotaFetch(url, requestInit)
                : await fetch(url, requestInit);

            if ((response.status === 403 || response.status === 429) && window.quotaInterceptor?.handleManualResponse) {
                response = await window.quotaInterceptor.handleManualResponse(
                    response,
                    window.quotaInterceptor.makeRetryRequest(url, requestInit, retryOptions)
                );
            }

            return response;
        },

        async runOptimizeViaSse(optimizeSignature, beforeMetrics) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const controller = new AbortController();
            let firstSseEventArrived = false;
            let timedOutBeforeFirstEvent = false;
            const requestInit = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify({
                    ...this.optimizeRequestPayload(),
                    modules: this.modulesForSubmit(),
                }),
                signal: controller.signal,
            };

            const firstChunkTimer = setTimeout(() => {
                if (!firstSseEventArrived) {
                    timedOutBeforeFirstEvent = true;
                    controller.abort();
                }
            }, 6500);

            try {
                this.showToast('已切换到实时流式优化通道...', 'info');
                const response = await this.quotaAwareFetch(_urls.optimizeStreamUrl, requestInit, { preserveAccept: true });

                if (!response.ok || !response.body) {
                    const errorText = await response.text();
                    if (this.tryHandleQuotaJsonText(errorText, requestInit)) {
                        throw { quotaHandled: true };
                    }
                    const resolvedMessage = this.extractSseErrorMessage(errorText);
                    if (resolvedMessage !== '') {
                        throw new Error(resolvedMessage);
                    }
                    return false;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let sseBuffer = '';
                let donePayload = null;

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) {
                        break;
                    }
                    sseBuffer += decoder.decode(value, { stream: true });

                    const frames = sseBuffer.split('\n\n');
                    sseBuffer = frames.pop() || '';

                    for (const frame of frames) {
                        const parsed = this.parseSseFrame(frame);
                        if (!parsed) {
                            continue;
                        }
                        const eventName = String(parsed.event || '').trim();
                        const data = parsed.data;

                        if (eventName === 'start') {
                            firstSseEventArrived = true;
                            clearTimeout(firstChunkTimer);
                            if (!this.shouldUseDeepProgressivePreview()) {
                                this.streamText = 'AI 已连接，正在实时生成...';
                            }
                            continue;
                        }
                        if (eventName === 'chunk') {
                            firstSseEventArrived = true;
                            clearTimeout(firstChunkTimer);
                            continue;
                        }
                        if (eventName === 'fallback') {
                            const nextDriver = String(data?.next_driver || '').trim();
                            const failedDriver = String(data?.failed_driver || '').trim();
                            this.currentAiDriver = nextDriver;
                            this.fallbackFromDriver = failedDriver;
                            const fallbackMessage = String(data?.message || '').trim();
                            if (fallbackMessage !== '') {
                                this.showToast(fallbackMessage, 'warning');
                            }
                            continue;
                        }
                        if (eventName === 'retry') {
                            const retryDriver = String(data?.driver || '').trim();
                            if (retryDriver !== '') {
                                this.currentAiDriver = retryDriver;
                            }
                            const retryMessage = String(data?.message || '').trim();
                            if (retryMessage !== '') {
                                this.showToast(retryMessage, 'warning');
                            }
                            continue;
                        }
                        if (eventName === 'done') {
                            donePayload = data;
                            break;
                        }
                        if (eventName === 'error') {
                            const msg = String(data?.error || 'AI 优化失败，请稍后重试');
                            this.lastAiErrorMessage = msg;
                            this.lastAiAttemptedDrivers = Array.isArray(data?.attempted_drivers)
                                ? data.attempted_drivers.map((item) => String(item || '').trim()).filter(Boolean)
                                : [];
                            this.currentAiDriver = String(data?.driver || this.currentAiDriver || '').trim();
                            throw new Error(msg);
                        }
                    }

                    if (donePayload) {
                        break;
                    }
                }

                clearTimeout(firstChunkTimer);
                if (!donePayload || !donePayload.success) {
                    return false;
                }

                const optimizedText = String(donePayload.optimized_text || '').trim();
                if (optimizedText === '') {
                    return false;
                }

                this.streamStatus = 'done';
                this.stopDeepProgressivePreview();
                this.streamText = optimizedText;
                this.optimizedContentForDiff = optimizedText;
                this.streamHighlights = Array.isArray(donePayload.highlights) ? donePayload.highlights : [];
                this.optimizeChangesSummary = String(donePayload.changes_summary || '');
                this.optimizeGapActions = Array.isArray(donePayload.gap_actions) ? donePayload.gap_actions : [];
                this.lastAiErrorMessage = '';
                this.lastAiAttemptedDrivers = [];
                this.currentAiDriver = String(donePayload.driver || '').trim();
                this.fallbackFromDriver = String(donePayload.fallback_from || '').trim();
                this.recordOptimizeVersion(optimizedText, 'stream');
                _state.lastOptimizeSignature = optimizeSignature;

                const afterMetrics = this.computeOptimizationMetrics(optimizedText);
                this.optimizeMetricsCompare = {
                    before: beforeMetrics,
                    after: afterMetrics,
                    delta: (afterMetrics.score || 0) - (beforeMetrics.score || 0),
                };
                this.keywordCoverageOptimized = this.keywordCoverageStats(optimizedText);
                this.optimizeDiffModules = this.buildOptimizeModuleDiffs();
                this.refreshRegressionValidationSummary();
                const usedDriverLabel = this.normalizeAiDriverLabel(donePayload.driver);
                const fallbackFromLabel = this.normalizeAiDriverLabel(donePayload.fallback_from);
                const successMessage = donePayload.fallback_from
                    ? `AI 实时优化完成，已从 ${fallbackFromLabel} 自动切换到 ${usedDriverLabel}。`
                    : `AI 实时优化完成，当前使用 ${usedDriverLabel}。`;
                this.showToast(successMessage, 'success');
                return true;
            } catch (error) {
                clearTimeout(firstChunkTimer);
                if (timedOutBeforeFirstEvent) {
                    this.stopDeepProgressivePreview();
                    this.streamText = '';
                    this.lastAiErrorMessage = '实时通道响应较慢，请稍后重试。';
                    this.lastAiAttemptedDrivers = [];
                    this.showToast('实时通道响应较慢，请稍后重试。', 'warning');
                    return false;
                }
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return false;
                }
                if (error?.quotaHandled) {
                    throw error;
                }
                throw error;
            }
        },

        parseSseFrame(frame) {
            const raw = String(frame || '').trim();
            if (raw === '') {
                return null;
            }

            const lines = raw.split('\n');
            let eventName = 'message';
            const dataLines = [];
            for (const line of lines) {
                if (line.startsWith('event:')) {
                    eventName = line.slice(6).trim();
                    continue;
                }
                if (line.startsWith('data:')) {
                    dataLines.push(line.slice(5).trim());
                }
            }

            const joinedData = dataLines.join('\n').trim();
            if (joinedData === '') {
                return { event: eventName, data: {} };
            }
            try {
                return { event: eventName, data: JSON.parse(joinedData) };
            } catch (e) {
                return { event: eventName, data: {} };
            }
        },

        normalizeAiDriverLabel(driver) {
            const normalized = String(driver || '').trim().toLowerCase();
            const labels = {
                zhipu: '智谱',
                deepseek: 'DeepSeek',
                volcano: '火山引擎',
            };

            return labels[normalized] || normalized || 'AI';
        },

        async runOptimizeViaSession(optimizeSignature, beforeMetrics) {
            const idempotencyKey = `optimize-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            const requestInit = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    idempotency_key: idempotencyKey,
                    ...this.optimizeRequestPayload(),
                    modules_snapshot: this.modulesForSubmit(),
                }),
            };
            const response = await this.quotaAwareFetch(_urls.optimizeSessionCreateUrl, requestInit);
            const result = await response.json();
            if (!response.ok) {
                let errorMessage = '优化请求过于频繁，请稍后重试';
                if (result && typeof result.message === 'string' && result.message.trim() !== '') {
                    errorMessage = result.message.trim();
                }
                throw new Error(errorMessage);
            }
            if (!result?.success || !result?.poll_url) {
                throw new Error(result?.message || '创建优化会话失败，请稍后重试');
            }
            _state.activeOptimizeSessionId = result.session_id || null;
            this.showToast('已创建优化任务，正在处理...', 'info');
            await this.pollOptimizeSessionStatus(result.poll_url, result.compare_url, optimizeSignature, beforeMetrics);
        },

        async pollOptimizeSessionStatus(pollUrl, compareUrl, optimizeSignature, beforeMetrics) {
            const maxAttempts = this.canUseAdvancedModel() ? 80 : 60;
            let delay = 1000;
            _state.optimizePollAbort = new AbortController();
            for (let attempt = 0; attempt < maxAttempts; attempt++) {
                this.optimizePollProgress = { current: attempt + 1, total: maxAttempts };
                await new Promise((resolve) => setTimeout(resolve, delay));
                delay = Math.min(3000, delay + 500);

                if (_state.optimizePollAbort.signal.aborted) {
                    this.streamStatus = 'error';
                    throw new Error('优化任务已取消');
                }

                const response = await fetch(pollUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    signal: _state.optimizePollAbort.signal,
                });
                const result = await response.json();
                if (!response.ok || !result?.success) {
                    throw new Error(result?.message || '查询优化状态失败，请稍后重试');
                }

                const status = result.status || '';
                if (status === 'succeeded' || status === 'applied') {
                    this.streamStatus = 'done';
                    this.optimizePollProgress = null;
                    _state.lastOptimizeSignature = optimizeSignature;
                    const afterMetrics = this.computeOptimizationMetrics(this.optimizedContentForDiff || this.modulesToRawText());
                    this.optimizeMetricsCompare = {
                        before: beforeMetrics,
                        after: afterMetrics,
                        delta: (afterMetrics.score || 0) - (beforeMetrics.score || 0),
                    };
                    this.showToast('AI 优化完成，正在进入对比页面...', 'success');
                    window.location.href = compareUrl || result.compare_url;
                    return;
                }
                if (status === 'failed' || status === 'canceled') {
                    // 自动重试一次
                    if (!this._optimizeAutoRetried && /timeout|超时|500|502|503|504/i.test(result.error_message || '')) {
                        this._optimizeAutoRetried = true;
                        this.showToast('优化失败，正在自动重试...', 'warning');
                        try {
                            const retryResp = await fetch(retryUrl, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}});
                            const retryData = await retryResp.json();
                            if (retryData.success && retryData.data?.session_id) {
                                this.showToast('已重新提交优化任务', 'info');
                                return; // 退出当前轮询，上层会重新触发
                            }
                        } catch(retryErr) {}
                    }
                    this.streamStatus = 'error';
                    this.optimizePollProgress = null;
                    throw new Error(result.error_message || '优化失败，请调整配置后重试');
                }
            }
            this.streamStatus = 'idle';
            this.optimizePollProgress = null;
            this.showToast('优化任务超过前端等待时间，已转入后台继续处理。你可以在“历史版本”里查看实时状态、失败原因或直接重试。', 'warning');
            this.openOptimizeHistoryModal();
        },

        optimizeStageIndex() {
            if (!this.optimizePollProgress) return 0;
            const pct = this.optimizePollProgress.current / Math.max(1, this.optimizePollProgress.total);
            if (pct < 0.25) return 0;
            if (pct < 0.5) return 1;
            if (pct < 0.75) return 2;
            return 3;
        },

        optimizeStageLabel() {
            const stages = ['岗位与简历信号对齐中...', '经历结构重排中...', '关键词嵌入与润色中...', '生成最终版本中...'];
            return stages[this.optimizeStageIndex()] || '处理中...';
        },

        cancelOptimizePoll() {
            if (_state.optimizePollAbort && !_state.optimizePollAbort.signal.aborted) {
                _state.optimizePollAbort.abort();
                this.streamStatus = 'error';
                this.optimizePollProgress = null;
                this.showToast('已取消优化任务', 'info');
            }
        },

        async doAtsScore() {
            const atsSignature = this.buildModulesSignature();
            if (_state.lastAtsSignature === atsSignature && this.resumeAtsScore !== null) {
                this.showToast('当前内容未变化，无需重复进行 ATS 评分', 'info');
                return;
            }
            if (!this.tryStartAiAction('atsScore', 1000)) {
                return;
            }
            this.atsScoring = true;
            try {
                const response = await fetch(_urls.atsScoreUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ modules: this.modulesForSubmit() }),
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    this.resumeAtsScore = result.ats_score;
                    _state.lastAtsSignature = atsSignature;
                    const level = result.level || '';
                    const summary = result.summary || '';
                    let msg = `ATS 评分完成：${result.ats_score} 分`;
                    if (level) msg += `（${level}）`;
                    if (summary) msg += ` — ${summary}`;
                    this.showToast(msg, result.ats_score >= 72 ? 'success' : (result.ats_score >= 60 ? 'warning' : 'danger'));
                } else {
                    this.showToast(result.message || 'ATS 评分失败，请稍后重试', 'danger');
                }
            } catch (err) {
                if (err?.quotaHandled) {
                    return;
                }
                this.showToast(err instanceof Error && err.message ? err.message : 'ATS 评分失败，请重试', 'danger');
            } finally {
                this.atsScoring = false;
                this.finishAiAction('atsScore');
            }
        },

        async applyOptimized() {
            const selectedTypes = Array.isArray(this.applySelectedModuleTypes) ? this.applySelectedModuleTypes.filter(Boolean) : [];
            const selectedTypeLabels = selectedTypes
                .map((type) => this.moduleTypeLabel(type))
                .join('、');
            const applyScopeHint = selectedTypes.length > 0
                ? `仅回写模块：${selectedTypeLabels}；未选模块将保持原样。`
                : '将回写全部模块。';
            const confirmed = typeof window.appConfirm === 'function'
                ? await window.appConfirm(
                    `应用后会按优化版本回写简历内容并重置 ATS 评分。\n${applyScopeHint}\n是否继续？`,
                    { title: '应用确认', showCancel: true }
                )
                : false;
            if (!confirmed) {
                return;
            }
            if (!this.tryStartAiAction('applyOptimized', 1200)) {
                return;
            }
            try {
                const response = await fetch(_urls.applyOptimizedUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        redirect_to: 'editor',
                        rebuild_modules: true,
                        selected_module_types: selectedTypes,
                    }),
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    _state.lastAtsSignature = null;
                    _state.lastOptimizeSignature = null;
                    this.optimizeMetricsCompare = null;
                    this.keywordCoverageOptimized = null;
                    this.regressionValidationSummary = null;
                    this.applySelectedModuleTypes = [];
                    this.showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(result.message || '应用失败，请稍后重试', 'danger');
                }
            } catch (err) {
                if (err?.quotaHandled) {
                    return;
                }
                this.showToast(err instanceof Error && err.message ? err.message : '应用失败，请重试', 'danger');
            } finally {
                this.finishAiAction('applyOptimized');
            }
        },

        applyModuleTypeOptions() {
            const seen = new Set();
            const ordered = [];
            const modules = Array.isArray(this.modules) ? this.modules : [];
            modules.forEach((mod) => {
                const type = String(mod?.type || '').trim();
                if (type === '' || seen.has(type)) {
                    return;
                }
                seen.add(type);
                ordered.push({
                    type,
                    label: this.moduleTypeLabel(type),
                });
            });
            return ordered;
        },

        toggleApplyModuleType(type) {
            if (!type) {
                return;
            }
            const current = Array.isArray(this.applySelectedModuleTypes) ? [...this.applySelectedModuleTypes] : [];
            const idx = current.indexOf(type);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(type);
            }
            this.applySelectedModuleTypes = current;
        },

        selectAllApplyModuleTypes() {
            this.applySelectedModuleTypes = this.applyModuleTypeOptions().map((item) => item.type);
        },

        clearApplyModuleTypes() {
            this.applySelectedModuleTypes = [];
        },

        @include('user.resumes.editor-partials.editor-script-domains.optimize-version')
