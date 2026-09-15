        initModules() {
            let rawModules = this.config.defaultModules;
            if (!Array.isArray(rawModules)) {
                rawModules = [];
            }
            this.modules = rawModules.map((m, i) => ({
                id: m.id ?? null,
                _key: (m._key ? String(m._key) : null) || (Date.now() + '_' + i + '_' + Math.random().toString(36).slice(2)),
                type: m.type || 'summary',
                data: m.data || {},
                sort_order: m.sort_order ?? i,
            }));
            this.modules.forEach((m) => {
                if (!m.data.items) m.data.items = [];
                if (m.type === 'personal') {
                    m.data.gender = String(m.data.gender || '');
                    m.data.birthday = String(m.data.birthday || '');
                    m.data.wechat = String(m.data.wechat || '');
                    m.data.github = String(m.data.github || '');
                    m.data.website = String(m.data.website || '');
                    m.data.content = String(m.data.content || '');
                    m.data.custom_fields = this.normalizePersonalCustomFields(m.data.custom_fields || []);
                }
                if (['skill', 'certificate'].includes(String(m.type || ''))) {
                    m.data.content = String(m.data.content || '');
                    m.data._content_mode = ['text', 'items'].includes(String(m.data._content_mode || ''))
                        ? String(m.data._content_mode)
                        : 'items';
                    if (!Array.isArray(m.data.items)) {
                        const parsed = this.parseModuleItemsFromText(m.data.content || '');
                        m.data.items = parsed.length > 0 ? parsed : [];
                    }
                }
                if (['education', 'experience', 'project'].includes(String(m.type || ''))) {
                    m.data.content = String(m.data.content || '');
                    m.data.content_raw = String(m.data.content_raw || '');
                    m.data._detail_mode = ['structured', 'raw'].includes(String(m.data._detail_mode || ''))
                        ? String(m.data._detail_mode)
                        : 'structured';
                }
            });
            const initialModulesSignature = this.buildModulesSignature();
            const initialOptimizeSignature = this.buildOptimizeSignature();
            if (this.resumeAtsScore !== null) {
                _state.lastAtsSignature = initialModulesSignature;
            }
            if (this.optimizedContentForDiff !== '') {
                _state.lastOptimizeSignature = initialOptimizeSignature;
                this.recordOptimizeVersion(this.optimizedContentForDiff, 'existing');
            }
        },

        initSortable() {
            this.$nextTick(() => {
                const listEl = document.getElementById('module-list');
                if (listEl && typeof Sortable !== 'undefined') {
                    new Sortable(listEl, {
                        animation: 150,
                        handle: '.ti-grip-vertical',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onStart: () => {
                            listEl.classList.add('is-sorting');
                        },
                        onEnd: (evt) => {
                            listEl.classList.remove('is-sorting');
                            if (evt.oldIndex === evt.newIndex) return;
                            this.pushUndo();
                            const arr = [...this.modules];
                            const [moved] = arr.splice(evt.oldIndex, 1);
                            arr.splice(evt.newIndex, 0, moved);
                            this.modules = arr;
                            this.activeIndex = evt.newIndex;
                            this.$nextTick(() => this.animateModuleCard(arr[evt.newIndex]?._key, 'module-reordered'));
                            this.showToast('模块顺序已更新', 'success');
                        },
                    });
                }
            });
        },

        initKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    if (this.dirty) {
                        const form = document.querySelector('form[action]');
                        if (form) this.submitForm({ target: form, preventDefault() {} });
                    } else {
                        this.showToast('当前无需保存的更改', 'info');
                    }
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                }
                if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                    e.preventDefault();
                    this.redo();
                }
                if (e.key === '?' && !e.ctrlKey && !e.metaKey && !['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)) {
                    e.preventDefault();
                    this.showShortcuts = !this.showShortcuts;
                }
            });
        },

        /**
         * 服务端自动保存：当 dirty 状态持续超过阈值时，自动提交表单到服务端。
         * - 间隔检查：每 15 秒检查一次 dirty 状态
         * - 触发条件：dirty 持续 60 秒且不在提交中
         * - 页面隐藏时不触发，避免干扰
         * - 失败时静默（本地草稿仍作为兜底）
         */
        initAutoSave() {
            let dirtySince = null;
            const checkIntervalMs = 15000;
            const autoSaveDelayMs = 60000;

            _state.autoSaveTimer = setInterval(() => {
                // 页面隐藏或正在提交时跳过
                if (document.hidden || this.isSubmitting) {
                    return;
                }
                // 非 dirty 状态重置计时
                if (!this.dirty) {
                    dirtySince = null;
                    return;
                }
                // 首次发现 dirty，记录时间
                if (dirtySince === null) {
                    dirtySince = Date.now();
                    return;
                }
                // dirty 持续时间不足阈值，等待
                if (Date.now() - dirtySince < autoSaveDelayMs) {
                    return;
                }
                // 触发自动保存
                const form = document.querySelector('form[action]');
                if (!form) {
                    return;
                }
                this.submitForm({ target: form, preventDefault() {}, forceSubmit: true })
                    .then(() => {
                        dirtySince = null;
                    })
                    .catch(() => {
                        // 自动保存失败时静默，本地草稿仍作为兜底
                        dirtySince = Date.now();
                    });
            }, checkIntervalMs);
        },

        initWatchers() {
            this.$watch('modules', () => {
                this.dirty = true;
                this.draftSaveState = 'dirty';
                this.scheduleDraftPersist();
                this.handleCompletionAnimations();
                this.scheduleInsightsRefresh();
                this.$nextTick(() => {
                    this.syncPreviewModuleAction();
                    this.measureA4Overflow();
                });
            });
            // Style properties share the same side-effect — combine via loop
            ['template', 'theme', 'fontFamily', 'fontSize', 'lineHeight', 'headingFontSize', 'sectionSpacing', 'leftColumnRatio'].forEach(prop => {
                this.$watch(prop, () => {
                    this.onboardingPreviewTouched = true;
                    this.dirty = true;
                    this.draftSaveState = 'dirty';
                    this.scheduleDraftPersist();
                    this.measureA4Overflow();
                });
            });
            this.$watch('activeIndex', () => this.syncPreviewModuleAction());
            this.$watch('previewScale', () => {
                this.syncPreviewModuleAction();
                this.measureA4Overflow();
            });
            this.$watch('template', () => this.syncPreviewModuleAction());
            this.$watch('a4AutoFitEnabled', () => this.measureA4Overflow());
            // AI config properties share the same draft-persist side-effect
            ['streamTargetJob', 'streamTargetCompany', 'streamTargetJobTitle', 'streamTargetJobDescription', 'optimizeGoals', 'optimizeMode', 'promptStrategyTemplate'].forEach(prop => {
                this.$watch(prop, () => {
                    this.draftSaveState = 'dirty';
                    this.scheduleDraftPersist();
                });
            });
            this.$watch('selectedJdKeywords', () => {
                this.draftSaveState = 'dirty';
                this.scheduleDraftPersist();
                this.scheduleInsightsRefresh();
            });

            this.$watch('keywordHighlightEnabled', () => {
                this.$nextTick(() => this.applyKeywordHighlights());
            });

            this.$watch('jdExtractedKeywords', () => {
                if (this.keywordHighlightEnabled) {
                    this.$nextTick(() => this.applyKeywordHighlights());
                }
            });

            // 自动预热：填写目标岗位后延迟 3 秒触发
            let prewarmTimer = null;
            this.$watch('streamTargetJob', (val) => {
                if (prewarmTimer) clearTimeout(prewarmTimer);
                if (val && val.trim().length >= 2) {
                    prewarmTimer = setTimeout(() => this.warmupOptimizeStreamChannel(), 3000);
                }
            });
        },

        initEventListeners() {
            document.getElementById('import-preview-modal')?.addEventListener('hidden.bs.modal', () => {
                if (!this.importing) {
                    _state.pendingImportFile = null;
                    this.importPreviewModules = [];
                    this.importPreviewRawText = '';
                    this.importPreviewTargetJob = '';
                    this.importPreviewModuleStats = {};
                    this.importPreviewFileName = '';
                    this.importPreviewMeta = {};
                }
            });

            document.getElementById('ai-workbench-modal')?.addEventListener('hidden.bs.modal', () => {
                this.closeFreshGraduatePresetModal();
                this.cleanupDanglingModalBackdrop();
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
            });

            document.getElementById('resume-preview')?.addEventListener('focusin', (e) => {
                const modEl = e.target.closest('[data-mod-index]');
                if (modEl) {
                    const idx = parseInt(modEl.dataset.modIndex, 10);
                    if (!isNaN(idx) && idx !== this.activeIndex) {
                        this.activeIndex = idx;
                    }
                    _state.previewActionPrimed = true;
                    _state.previewActionIndex = idx;
                    this.syncPreviewModuleAction(idx, { muted: true });
                }
            });

            document.querySelector('.editor-preview-scroll')?.addEventListener('scroll', () => {
                this.syncPreviewModuleAction();
            });

            window.addEventListener('resize', () => {
                const scroller = document.querySelector('.editor-preview-scroll');
                if (scroller && scroller.scrollLeft !== 0) {
                    scroller.scrollLeft = 0;
                }
                this.syncPreviewModuleAction();
                this.measureA4Overflow();
            });

            document.addEventListener('fullscreenchange', () => {
                const previewArea = document.getElementById('editor-preview-area');
                this.isPreviewFullscreen = document.fullscreenElement === previewArea;
            });

            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.saveModules();
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                    e.preventDefault();
                    if (typeof this.undoLastAction === 'function') this.undoLastAction();
                }
                if (e.key === 'Delete' && e.shiftKey && this.activeIndex >= 0) {
                    e.preventDefault();
                    this.removeModule(this.activeIndex);
                }
            });

            this.$nextTick(() => {
                const scroller = document.querySelector('.editor-preview-scroll');
                if (scroller) {
                    scroller.scrollLeft = 0;
                    scroller.scrollTop = 0;
                }
                this.measureA4Overflow();
                this.handleDimensionHash();
            });
        },

        handleDimensionHash() {
            const hash = window.location.hash;
            if (!hash || !hash.startsWith('#dim-')) return;
            const dimension = hash.replace('#dim-', '');
            const dimensionToType = {
                keyword_match: ['objective', 'skill', 'experience'],
                quantified_results: ['experience', 'project'],
                star_structure: ['experience', 'project'],
                professional_format: ['personal'],
                relevance_focus: ['experience', 'project'],
                competitive_edge: ['project', 'certificate'],
            };
            const types = dimensionToType[dimension];
            if (!types) return;
            for (const type of types) {
                const idx = this.modules.findIndex(m => m.type === type);
                if (idx !== -1) {
                    this.activeIndex = idx;
                    this.$nextTick(() => {
                        const listEl = document.getElementById('module-list');
                        const cards = listEl?.querySelectorAll('.card');
                        const target = cards?.[idx];
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            target.classList.add('module-added');
                            setTimeout(() => target.classList.remove('module-added'), 1500);
                        }
                    });
                    break;
                }
            }
            history.replaceState(null, '', window.location.pathname + window.location.search);
        },
