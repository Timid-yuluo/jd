        draftStorageKey() {
            return `resume-editor-draft:${this.resumeId}`;
        },

        draftMaxAgeMs: 7 * 24 * 60 * 60 * 1000, // 7 days

        cleanExpiredDrafts() {
            try {
                const now = Date.now();
                const keysToRemove = [];
                for (let i = 0; i < window.localStorage.length; i++) {
                    const key = window.localStorage.key(i);
                    if (!key || !key.startsWith('resume-editor-draft:')) continue;
                    try {
                        const raw = window.localStorage.getItem(key);
                        if (!raw) continue;
                        const parsed = JSON.parse(raw);
                        const savedAt = parsed?.savedAt ? new Date(parsed.savedAt).getTime() : 0;
                        if (savedAt && (now - savedAt) > this.draftMaxAgeMs) {
                            keysToRemove.push(key);
                        }
                    } catch (_) { /* skip malformed entries */ }
                }
                keysToRemove.forEach(key => window.localStorage.removeItem(key));
            } catch (_) { /* localStorage unavailable */ }
        },

        resolvedResumeStorageScope() {
            const direct = String(this.resumeId ?? '').trim();
            if (direct !== '' && direct !== 'null' && direct !== 'undefined') {
                return direct;
            }

            const configId = String(window.resumeEditorConfig?.resumeId ?? '').trim();
            if (configId !== '' && configId !== 'null' && configId !== 'undefined') {
                return configId;
            }

            const match = String(window.location?.pathname || '').match(/\/resumes\/(\d+)\/editor/);
            if (Array.isArray(match) && match[1]) {
                return match[1];
            }

            return 'default';
        },

        editorStatePayload() {
            return {
                modules: this.modulesForSubmit(),
                template: this.template,
                theme: this.theme,
                fontFamily: this.fontFamily,
                fontSize: this.fontSize,
                lineHeight: this.lineHeight,
                streamTargetJob: this.streamTargetJob,
                streamTargetCompany: this.streamTargetCompany,
                streamTargetJobTitle: this.streamTargetJobTitle,
                streamTargetJobDescription: this.streamTargetJobDescription,
                optimizeGoals: this.optimizeGoals,
                optimizeMode: this.optimizeMode,
                promptStrategyTemplate: this.promptStrategyTemplate,
                selectedJdKeywords: this.selectedJdKeywords,
                moduleScoreHistory: this.moduleScoreHistory,
                currentModuleOverallScore: this.currentModuleOverallScore,
                optimizeVersionHistory: this.optimizeVersionHistory,
                selectedOptimizeVersionA: this.selectedOptimizeVersionA,
                selectedOptimizeVersionB: this.selectedOptimizeVersionB,
                savedAt: new Date().toISOString(),
            };
        },

        editorStateSignature(payload = null) {
            const state = payload || this.editorStatePayload();
            return JSON.stringify({
                modules: state.modules || [],
                template: state.template || 'classic',
                theme: state.theme || 'blue',
                fontFamily: state.fontFamily || '',
                fontSize: state.fontSize || '',
                lineHeight: state.lineHeight || '',
                streamTargetJob: state.streamTargetJob || '',
                streamTargetCompany: state.streamTargetCompany || '',
                streamTargetJobTitle: state.streamTargetJobTitle || '',
                streamTargetJobDescription: state.streamTargetJobDescription || '',
                optimizeGoals: Array.isArray(state.optimizeGoals) ? [...state.optimizeGoals].sort() : [],
                optimizeMode: state.optimizeMode || 'balanced',
                promptStrategyTemplate: state.promptStrategyTemplate || 'general',
                selectedJdKeywords: Array.isArray(state.selectedJdKeywords) ? [...state.selectedJdKeywords].sort() : [],
                moduleScoreHistory: Array.isArray(state.moduleScoreHistory)
                    ? state.moduleScoreHistory.map((item) => ({
                        ts: Number(item?.ts || 0),
                        score: Number(item?.score || 0),
                        reason: String(item?.reason || ''),
                        label: String(item?.label || ''),
                    }))
                    : [],
                currentModuleOverallScore: Number(state.currentModuleOverallScore || 0),
                optimizeVersionHistory: Array.isArray(state.optimizeVersionHistory)
                    ? state.optimizeVersionHistory.map((item) => ({
                        id: String(item?.id || ''),
                        key: String(item?.key || ''),
                        text: String(item?.text || ''),
                        score: Number(item?.score || 0),
                        mode: String(item?.mode || ''),
                        goals: Array.isArray(item?.goals) ? item.goals.map((goal) => String(goal || '')) : [],
                        promptStrategyTemplate: String(item?.promptStrategyTemplate || 'general'),
                        createdAt: Number(item?.createdAt || 0),
                        source: String(item?.source || 'draft'),
                    })).filter((item) => item.id !== '' && item.text.trim() !== '').slice(0, 8)
                    : [],
                selectedOptimizeVersionA: String(state.selectedOptimizeVersionA || ''),
                selectedOptimizeVersionB: String(state.selectedOptimizeVersionB || ''),
            });
        },

        loadDraftPayload() {
            try {
                const raw = window.localStorage.getItem(this.draftStorageKey());
                if (!raw) {
                    return null;
                }

                const parsed = JSON.parse(raw);
                if (!parsed || !Array.isArray(parsed.modules)) {
                    return null;
                }

                return parsed;
            } catch (error) {
                                return null;
            }
        },

        checkRestorableDraft() {
            this.cleanExpiredDrafts();
            const draft = this.loadDraftPayload();
            if (!draft) {
                return;
            }

            const draftSignature = this.editorStateSignature(draft);
            if (draftSignature === _state.serverSnapshotSignature) {
                this.clearLocalDraft(false);
                return;
            }

            this.draftPayload = draft;
            this.draftAvailable = true;
            this.lastDraftSavedAt = draft.savedAt || null;
        },

        scheduleDraftPersist() {
            if (_state.draftTimer) {
                clearTimeout(_state.draftTimer);
            }

            this.draftSaveState = 'saving';
            _state.draftTimer = setTimeout(() => this.persistDraft(), 900);
        },

        persistDraft() {
            try {
                const payload = this.editorStatePayload();
                const signature = this.editorStateSignature(payload);
                if (signature === _state.serverSnapshotSignature && !this.dirty) {
                    return;
                }
                if (signature === _state.lastPersistedDraftSignature) {
                    return;
                }

                const serialized = JSON.stringify(payload);
                if (serialized.length > 2 * 1024 * 1024) {
                                        this.draftSaveState = 'error';
                    return;
                }

                window.localStorage.setItem(this.draftStorageKey(), serialized);
                _state.lastPersistedDraftSignature = signature;
                this.lastDraftSavedAt = payload.savedAt;
                this.draftSaveState = 'saved';
            } catch (error) {
                if (error instanceof DOMException && error.name === 'QuotaExceededError') {
                                        this.cleanExpiredDrafts();
                }
                                this.draftSaveState = 'error';
            }
        },

        clearLocalDraft(showMessage = false) {
            if (_state.draftTimer) {
                clearTimeout(_state.draftTimer);
                _state.draftTimer = null;
            }

            try {
                window.localStorage.removeItem(this.draftStorageKey());
            } catch (error) {
                            }

            this.draftAvailable = false;
            this.draftPayload = null;
            this.lastDraftSavedAt = null;
            _state.lastPersistedDraftSignature = null;
            this.draftSaveState = this.dirty ? 'dirty' : 'idle';

            if (showMessage) {
                this.showToast('本地草稿已清除', 'info');
            }
        },

        hydrateModules(rawModules) {
            return (rawModules || []).map((m, i) => ({
                id: m.id ?? null,
                _key: m._key ? String(m._key) : `${Date.now()}_${i}_${Math.random().toString(36).slice(2)}`,
                type: m.type || 'summary',
                data: {
                    ...(m.data || {}),
                    items: Array.isArray(m?.data?.items) ? m.data.items : [],
                },
                sort_order: m.sort_order ?? i,
            }));
        },

        restoreLocalDraft() {
            if (!this.draftPayload || !Array.isArray(this.draftPayload.modules)) {
                return;
            }

            this.pushUndo();
            this.modules = this.hydrateModules(this.draftPayload.modules);
            this.template = this.draftPayload.template || this.template;
            this.theme = this.draftPayload.theme || this.theme;
            this.fontFamily = this.draftPayload.fontFamily || this.fontFamily;
            this.fontSize = this.draftPayload.fontSize || this.fontSize;
            this.lineHeight = this.draftPayload.lineHeight || this.lineHeight;
            this.streamTargetJob = this.draftPayload.streamTargetJob || this.streamTargetJob;
            this.streamTargetCompany = this.draftPayload.streamTargetCompany || this.streamTargetCompany;
            this.streamTargetJobTitle = this.draftPayload.streamTargetJobTitle || this.streamTargetJobTitle;
            this.streamTargetJobDescription = this.draftPayload.streamTargetJobDescription || this.streamTargetJobDescription;
            this.optimizeGoals = Array.isArray(this.draftPayload.optimizeGoals) ? this.draftPayload.optimizeGoals : this.optimizeGoals;
            this.optimizeMode = this.draftPayload.optimizeMode || this.optimizeMode;
            this.promptStrategyTemplate = this.draftPayload.promptStrategyTemplate || this.promptStrategyTemplate || 'general';
            this.enforcePlanFeatureConstraints(true);
            this.selectedJdKeywords = Array.isArray(this.draftPayload.selectedJdKeywords) ? this.draftPayload.selectedJdKeywords : this.selectedJdKeywords;
            this.moduleScoreHistory = Array.isArray(this.draftPayload.moduleScoreHistory) ? this.draftPayload.moduleScoreHistory : this.moduleScoreHistory;
            this.currentModuleOverallScore = Number(this.draftPayload.currentModuleOverallScore || this.currentModuleOverallScore || 0);
            this.optimizeVersionHistory = Array.isArray(this.draftPayload.optimizeVersionHistory)
                ? this.draftPayload.optimizeVersionHistory.slice(0, 8)
                : this.optimizeVersionHistory;
            this.selectedOptimizeVersionA = this.draftPayload.selectedOptimizeVersionA || this.selectedOptimizeVersionA;
            this.selectedOptimizeVersionB = this.draftPayload.selectedOptimizeVersionB || this.selectedOptimizeVersionB;
            this.refreshJdKeywordsFromDescription(false);
            this.refreshOptimizeInsights(false);
            this.activeIndex = 0;
            this.collapsedModules = new Set();
            this.dirty = true;
            this.lastDraftSavedAt = this.draftPayload.savedAt || this.lastDraftSavedAt;
            this.draftAvailable = false;
            this.draftSaveState = 'dirty';
            this.showToast('已恢复本地草稿', 'success');
        },

        discardLocalDraft() {
            this.clearLocalDraft();
            this.showToast('已忽略本地草稿', 'info');
        },

        draftSavedText() {
            if (!this.lastDraftSavedAt) {
                return '自动保存草稿未启动';
            }

            const date = new Date(this.lastDraftSavedAt);
            if (Number.isNaN(date.getTime())) {
                return '草稿已自动保存在本地';
            }

            return `本地草稿自动保存于 ${date.toLocaleString('zh-CN', { hour12: false })}`;
        },

        draftRecoverText() {
            if (!this.draftPayload?.savedAt) {
                return '检测到当前浏览器存在较新的未保存编辑内容。';
            }

            const date = new Date(this.draftPayload.savedAt);
            if (Number.isNaN(date.getTime())) {
                return '检测到当前浏览器存在较新的未保存编辑内容。';
            }

            return `草稿时间：${date.toLocaleString('zh-CN', { hour12: false })}`;
        },

        draftStatusText() {
            const map = {
                idle: '尚未产生本地草稿',
                dirty: '当前改动待自动保存',
                saving: '正在保存草稿...',
                saved: '草稿已自动保存',
                error: '草稿保存失败',
            };

            return map[this.draftSaveState] || '草稿状态未知';
        },

        draftStatusIconClass() {
            const map = {
                idle: 'ti ti-database',
                dirty: 'ti ti-edit-circle',
                saving: 'ti ti-loader-2 ti-spin',
                saved: 'ti ti-check',
                error: 'ti ti-alert-circle',
            };

            return map[this.draftSaveState] || 'ti ti-database';
        },
