        aiPanelModalConfig(panel) {
            const panelMap = {
                analysis: {
                    id: 'ai-analysis-modal',
                },
                suggestion: {
                    id: 'ai-suggestion-modal',
                },
                result: {
                    id: 'ai-result-modal',
                    requiresResult: true,
                    emptyMessage: '当前暂无优化结果，请先执行 AI 优化。',
                },
            };
            return panelMap[panel] || null;
        },

        isModalShownById(modalId) {
            const modalEl = document.getElementById(String(modalId || ''));
            if (!modalEl) {
                return false;
            }
            const style = window.getComputedStyle(modalEl);
            return modalEl.classList.contains('show') && style.display !== 'none';
        },

        openAiWorkbenchModal() {
            const modalEl = document.getElementById('ai-workbench-modal');
            if (!modalEl) {
                return;
            }
            this.aiPanelReturnToWorkbench = false;
            if (!window.bootstrap) {
                this.openAiPanelModalFallback(modalEl);
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        closeAiWorkbenchModal() {
            this.closeFreshGraduatePresetModal();
            const modalEl = document.getElementById('ai-workbench-modal');
            if (!modalEl) {
                this.cleanupDanglingModalBackdrop();
                return;
            }
            if (!window.bootstrap) {
                this.closeAiPanelModalFallback(modalEl);
                this.cleanupDanglingModalBackdrop();
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
            // In some race conditions the backdrop remains until next tick.
            setTimeout(() => this.cleanupDanglingModalBackdrop(), 220);
        },

        openFreshGraduatePresetModal() {
            const modalEl = document.getElementById('ai-fresh-graduate-preset-modal');
            if (!modalEl) {
                return;
            }
            if (!window.bootstrap) {
                const tips = this.freshGraduateGuideTips();
                const message = [
                    '将应用"应届生一键预设"，并执行以下优化方向：',
                    ...tips.map((tip, idx) => `${idx + 1}. ${tip}`),
                ].join('<br>');
                window.appConfirm(message, { title: '应届生一键预设' }).then(ok => {
                    if (ok) this.applyFreshGraduatePreset();
                });
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        closeFreshGraduatePresetModal() {
            const modalEl = document.getElementById('ai-fresh-graduate-preset-modal');
            if (!modalEl) {
                return;
            }
            if (!window.bootstrap) {
                this.closeAiPanelModalFallback(modalEl);
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
        },

        async confirmFreshGraduatePresetFromModal() {
            const readiness = this.freshGraduateReadinessSummary();
            if (readiness.level === 'low') {
                const missing = readiness.missing.map((item) => item.label).join(' / ');
                const message = [
                    `当前仅满足 ${readiness.passedCount}/${readiness.total} 项核心模块。`,
                    `建议先补齐：${missing || '教育背景 / 项目经历 / 实习经历 / 技能模块'}`,
                    '仍要继续应用应届生预设吗？',
                ].join('<br>');
                let confirmed = false;
                if (typeof window.appConfirm === 'function') {
                    confirmed = await window.appConfirm(message, { title: '完整度较低，二次确认' });
                } else {
                    confirmed = window.confirm(`当前仅满足 ${readiness.passedCount}/${readiness.total} 项核心模块，建议先补齐后再优化。是否继续应用？`);
                }
                if (!confirmed) {
                    return;
                }
            }
            if (this.freshGraduateGuideDontShowAgain) {
                this.markFreshGraduateGuideSkipped();
            }
            this.closeFreshGraduatePresetModal();
            this.applyFreshGraduatePreset();
        },

        async openOptimizeHistoryModal() {
            await this.fetchOptimizeSessionHistory();
            const modalEl = document.getElementById('ai-optimize-history-modal');
            if (!modalEl) {
                return;
            }
            if (!window.bootstrap) {
                this.openAiPanelModalFallback(modalEl);
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        closeOptimizeHistoryModal() {
            const modalEl = document.getElementById('ai-optimize-history-modal');
            if (!modalEl) {
                return;
            }
            if (!window.bootstrap) {
                this.closeAiPanelModalFallback(modalEl);
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
        },

        async fetchOptimizeSessionHistory() {
            if (!_urls.optimizeSessionHistoryUrl) {
                this.optimizeSessionHistory = [];
                return;
            }
            this.optimizeSessionHistoryLoading = true;
            try {
                const response = await fetch(_urls.optimizeSessionHistoryUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();
                if (!response.ok || !result?.success) {
                    throw new Error(result?.message || '获取历史版本失败');
                }
                this.optimizeSessionHistory = Array.isArray(result.items) ? result.items : [];
            } catch (error) {
                this.optimizeSessionHistory = [];
                this.showToast(error instanceof Error ? error.message : '获取历史版本失败，请稍后重试', 'danger');
            } finally {
                this.optimizeSessionHistoryLoading = false;
            }
        },

        optimizeSessionStatusMeta(status) {
            const normalized = String(status || '').trim();
            switch (normalized) {
                case 'queued':
                    return { label: '排队中', badge: 'bg-secondary-lt text-secondary' };
                case 'running':
                    return { label: '处理中', badge: 'bg-primary-lt text-primary' };
                case 'succeeded':
                    return { label: '已完成', badge: 'bg-success-lt text-success' };
                case 'applied':
                    return { label: '已应用', badge: 'bg-success-lt text-success' };
                case 'failed':
                    return { label: '已失败', badge: 'bg-danger-lt text-danger' };
                case 'canceled':
                    return { label: '已取消', badge: 'bg-warning-lt text-warning' };
                default:
                    return { label: '未知状态', badge: 'bg-secondary-lt text-secondary' };
            }
        },

        async retryOptimizeSession(item) {
            const retryUrl = String(item?.retry_url || '').trim();
            if (retryUrl === '') {
                this.showToast('当前任务暂不支持重试', 'warning');
                return;
            }

            try {
                const response = await fetch(retryUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();
                if (!response.ok || !result?.success || !result?.poll_url) {
                    throw new Error(result?.message || '重新提交优化任务失败，请稍后重试');
                }

                _state.activeOptimizeSessionId = result.session_id || null;
                this.streamStatus = 'running';
                this.optimizePollProgress = null;
                this.closeOptimizeHistoryModal();
                this.showToast(result.message || '已重新提交优化任务，正在后台处理。', 'info');
                await this.fetchOptimizeSessionHistory();
                await this.pollOptimizeSessionStatus(result.poll_url, result.compare_url || '', this.buildModulesSignature(), this.computeOptimizationMetrics(this.modulesToRawText()));
            } catch (error) {
                this.showToast(error instanceof Error ? error.message : '重新提交优化任务失败，请稍后重试', 'danger');
            }
        },

        applyOptimizeHistoryVersion(item) {
            const content = String(item?.after_raw || '').trim();
            if (!content) {
                this.showToast('该历史版本暂无可加载内容', 'warning');
                return;
            }
            this.optimizedContentForDiff = content;
            this.streamStatus = 'done';
            this.recordOptimizeVersion(content, 'history');
            this.closeOptimizeHistoryModal();
            this.showToast('已加载历史版本，可继续查看差异或应用', 'success');
        },

        goToOptimizeHistoryCompare(item) {
            const compareUrl = String(item?.compare_url || '').trim();
            if (compareUrl === '') {
                this.showToast('该历史版本暂无对比页面', 'warning');
                return;
            }
            window.location.href = compareUrl;
        },

        openAiPanelModal(panel) {
            const target = this.aiPanelModalConfig(panel);
            if (!target) {
                return;
            }
            if (target.requiresResult && !this.optimizedContentForDiff) {
                this.showToast(target.emptyMessage || '当前暂无可查看内容', 'info');
                return;
            }
            const modalEl = document.getElementById(target.id);
            if (!modalEl) {
                return;
            }
            const isWorkbenchOpen = this.isModalShownById('ai-workbench-modal');
            if (isWorkbenchOpen) {
                this.aiPanelReturnToWorkbench = true;
                this.closeAiWorkbenchModal();
                setTimeout(() => this.openAiPanelModal(panel), 220);
                return;
            }
            if (!window.bootstrap) {
                this.openAiPanelModalFallback(modalEl);
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },

        closeAiPanelModal(panel, options = {}) {
            const target = this.aiPanelModalConfig(panel);
            if (!target) {
                return;
            }
            const modalEl = document.getElementById(target.id);
            if (!modalEl) {
                return;
            }
            const shouldReturnToWorkbench = options?.returnToWorkbench !== false
                && this.aiPanelReturnToWorkbench === true;
            if (!window.bootstrap) {
                this.closeAiPanelModalFallback(modalEl);
                if (shouldReturnToWorkbench) {
                    this.aiPanelReturnToWorkbench = false;
                    setTimeout(() => this.openAiWorkbenchModal(), 220);
                }
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.hide();
            if (shouldReturnToWorkbench) {
                this.aiPanelReturnToWorkbench = false;
                setTimeout(() => this.openAiWorkbenchModal(), 220);
            }
        },

        switchAiPanelModal(currentPanel, nextPanel) {
            if (!nextPanel || currentPanel === nextPanel) {
                return;
            }
            const nextTarget = this.aiPanelModalConfig(nextPanel);
            if (!nextTarget) {
                return;
            }
            if (nextTarget.requiresResult && !this.optimizedContentForDiff) {
                this.showToast(nextTarget.emptyMessage || '当前暂无可查看内容', 'info');
                return;
            }
            this.closeAiPanelModal(currentPanel, { returnToWorkbench: false });
            setTimeout(() => this.openAiPanelModal(nextPanel), 180);
        },

        openAiPanelModalFallback(modalEl) {
            this.ensureAiModalBackdrop();
            modalEl.style.display = 'block';
            modalEl.removeAttribute('aria-hidden');
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        },

        closeAiPanelModalFallback(modalEl) {
            modalEl.classList.remove('show');
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            modalEl.style.display = 'none';
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.querySelectorAll('.modal-backdrop.ai-panel-fallback-backdrop').forEach((el) => el.remove());
            }
        },

        ensureAiModalBackdrop() {
            if (document.querySelector('.modal-backdrop.ai-panel-fallback-backdrop')) {
                return;
            }
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show ai-panel-fallback-backdrop';
            document.body.appendChild(backdrop);
        },

        cleanupDanglingModalBackdrop() {
            const hasVisibleModal = Array.from(document.querySelectorAll('.modal'))
                .some((el) => {
                    const style = window.getComputedStyle(el);
                    return el.classList.contains('show') && style.display !== 'none';
                });
            if (hasVisibleModal) {
                return;
            }
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
        },
