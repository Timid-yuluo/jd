        async submitForm(event) {
            event.preventDefault();
            const form = event.target;
            const shouldRedirectToShow = event.redirect === 'show';
            if (this.isSubmitting) {
                return;
            }

            if (!event.forceSubmit && this.hasCriticalSaveIssues()) {
                const criticalMessages = this.buildSaveChecklist()
                    .filter((issue) => issue.severity === 'critical')
                    .slice(0, 3)
                    .map((issue) => `${issue.moduleLabel}：${issue.message}`);
                const confirmMessage = `当前还有关键项待处理：\n- ${criticalMessages.join('\n- ')}\n\n仍要继续保存吗？`;
                const shouldContinue = typeof window.appConfirm === 'function'
                    ? await window.appConfirm(confirmMessage, { title: '保存确认' })
                    : false;
                if (!shouldContinue) {
                    const firstCritical = this.buildSaveChecklist().find((issue) => issue.severity === 'critical');
                    if (firstCritical) {
                        this.focusChecklistItem(firstCritical);
                    }
                    return;
                }
            }

            this.isSubmitting = true;
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn?.innerHTML;
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-2"></i>保存中...'; }

            const data = new FormData(form);
            try {
                const r = await fetch(form.action, {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (r.redirected) {
                    window.location.href = r.url;
                    return;
                }
                const contentType = r.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    window.location.reload();
                    return;
                }
                const result = await r.json();
                if (result.success) {
                    if (Array.isArray(result.modules)) {
                        this.modules = result.modules.map((m, i) => ({
                            ...m,
                            _key: this.modules[i]?._key || String(m.id ?? ('saved-' + i)),
                            sort_order: m.sort_order ?? i,
                        }));
                    }
                    _state.lastAtsSignature = null;
                    _state.lastOptimizeSignature = null;
                    this.dirty = false;
                    // 自动保存成功时静默提示，手动保存时显示 toast
                    if (!event.forceSubmit) {
                        this.showToast('保存成功', 'success');
                    }
                    _state.serverSnapshotSignature = this.editorStateSignature();
                    this.draftSaveState = 'saved';
                    this.clearLocalDraft();
                    if (shouldRedirectToShow) {
                        window.location.href = _urls.showUrl;
                    }
                } else {
                    this.showToast(result.message || '保存失败', 'danger');
                }
            } catch (error) {
                if (!event.forceSubmit) {
                    this.showToast('保存失败，请重试', 'danger');
                }
                throw error;
            } finally {
                this.isSubmitting = false;
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            }
        },

        showToast(message, type = 'success') {
            if (typeof window.appNotify === 'function') {
                window.appNotify(message, type);
                return;
            }
            const container = document.getElementById('editor-toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show mb-2`;
            toast.style.cssText = 'min-width: 240px; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);';
            const msgSpan = document.createElement('span');
            msgSpan.textContent = message;
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            toast.appendChild(msgSpan);
            toast.appendChild(closeBtn);
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        },

        escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        escapeRegExp(text) {
            return String(text || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        async uploadAvatar(event, modIndex) {
            const file = event.target.files[0];
            if (!file) return;
            if (modIndex < 0 || modIndex >= this.modules.length) return;
            try {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                const res = await fetch(_urls.uploadAvatarUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success && data.url) {
                    this.modules[modIndex].data.avatar = data.url;
                    this.showToast('头像上传成功');
                } else {
                    this.showToast(data.message || '头像上传失败', 'danger');
                }
            } catch (e) {
                                this.showToast('头像上传失败：网络错误', 'danger');
            } finally {
                event.target.value = '';
            }
        },
