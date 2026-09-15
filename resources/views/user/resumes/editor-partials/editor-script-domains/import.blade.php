        @include('user.resumes.editor-partials.editor-script-domains.import-preview')

        openImportPreviewModal() {
            const modalEl = document.getElementById('import-preview-modal');
            if (!modalEl || !window.bootstrap) {
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        closeImportPreviewModal(clearPending = false) {
            const modalEl = document.getElementById('import-preview-modal');
            if (modalEl && window.bootstrap) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            }

            if (clearPending) {
                _state.pendingImportFile = null;
                this.importPreviewModules = [];
                this.importPreviewRawText = '';
                this.importPreviewTargetJob = '';
                this.importPreviewModuleStats = {};
                this.importPreviewFileName = '';
                this.importPreviewMeta = {};
            }
        },

        async requestImportDocument(file, previewOnly = false) {
            const formData = new FormData();
            formData.append('document', file);
            formData.append('preview_only', previewOnly ? '1' : '0');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

            const res = await fetch(_urls.importDocumentUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            const data = await res.json();

            return {
                ok: res.ok,
                data,
            };
        },

        applyImportedModules(data) {
            this.modules = (data.modules || []).map((m, i) => ({ ...m, _key: m.id ?? `mod-${i}-${Date.now()}` }));
            this.rawContentForDiff = data.content_raw || '';
            this.streamTargetJob = data.target_job || '';
            this.streamTargetCompany = data.target_company || this.streamTargetCompany;
            this.streamTargetJobTitle = data.target_job_title || this.streamTargetJobTitle;
            this.streamTargetJobDescription = data.target_job_description || this.streamTargetJobDescription;
            this.optimizeGoals = Array.isArray(data.optimize_goals) ? data.optimize_goals : this.optimizeGoals;
            this.optimizedContentForDiff = '';
            this.optimizeChangesSummary = '';
            this.optimizeMetricsCompare = null;
            this.keywordCoverageOptimized = null;
            this.resumeAtsScore = data.ats_score ?? null;
            _state.lastAtsSignature = null;
            _state.lastOptimizeSignature = null;
            this.activeIndex = 0;
            this.collapsedModules = new Set();
            this.dirty = true;
            this.refreshOptimizeInsights();
        },

        async importDocument(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.importing = true;
            try {
                const { ok, data } = await this.requestImportDocument(file, true);
                if (ok && data.success) {
                    _state.pendingImportFile = file;
                    this.importPreviewModules = Array.isArray(data.modules) ? data.modules : [];
                    this.importPreviewRawText = data.content_raw || '';
                    this.importPreviewTargetJob = data.target_job || '';
                    this.importPreviewModuleStats = data.module_stats || {};
                    this.importPreviewFileName = file.name || '';
                    this.importPreviewMeta = data.import_meta || {};
                    this.openImportPreviewModal();
                } else {
                    this.showToast(data.message || '导入失败', 'danger');
                }
            } catch (e) {
                if (!e?.quotaHandled) {
                    this.showToast('导入失败：网络错误', 'danger');
                }
            } finally {
                this.importing = false;
                event.target.value = '';
            }
        },

        async confirmImportDocument() {
            if (!_state.pendingImportFile) {
                this.showToast('当前没有可确认导入的文件', 'warning');
                return;
            }

            if (this.modules.length > 0) {
                const confirmed = typeof window.appConfirm === 'function'
                    ? await window.appConfirm('确认用预览识别结果覆盖当前所有模块吗？', { title: '覆盖确认' })
                    : false;
                if (!confirmed) {
                    return;
                }
            }

            this.pushUndo();
            this.importing = true;
            try {
                const { ok, data } = await this.requestImportDocument(_state.pendingImportFile, false);
                if (ok && data.success) {
                    this.applyImportedModules(data);
                    this.closeImportPreviewModal(true);
                    this.showToast('导入成功，已解析为可视化模块。');
                } else {
                    this.showToast(data.message || '导入失败', 'danger');
                }
            } catch (e) {
                if (!e?.quotaHandled) {
                    this.showToast('导入失败：网络错误', 'danger');
                }
            } finally {
                this.importing = false;
            }
        },
