(function initClearForm() {
    var clearBtn = document.getElementById('clearFormBtn');
    if (!clearBtn) {
        return;
    }

    clearBtn.addEventListener('click', function() {
        var form = document.getElementById('analyzeForm');
        if (!form) {
            return;
        }

        form.reset();

        var textarea = form.querySelector('textarea[name="job_description"]');
        var lengthEl = document.getElementById('jobDescriptionLength');
        var clientErrorEl = document.getElementById('jobDescriptionClientError');

        if (textarea && lengthEl) {
            lengthEl.textContent = String((textarea.value || '').trim().length);
        }

        if (textarea) {
            textarea.classList.remove('is-invalid');
        }

        if (clientErrorEl) {
            clientErrorEl.classList.add('d-none');
        }
    });
})();

(function initDeleteHistoryConfirm() {
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form.classList.contains('delete-history-form')) {
            return;
        }
        if (!confirm('确认删除这条历史分析记录吗？')) {
            e.preventDefault();
        }
    });
})();

(function initBatchDelete() {
    var checks = document.querySelectorAll('.history-check');
    var batchBtn = document.getElementById('batchDeleteBtn');
    var countEl = document.getElementById('batchDeleteCount');
    if (!checks.length || !batchBtn) return;

    function updateBatchBtn() {
        var selected = document.querySelectorAll('.history-check:checked');
        countEl.textContent = selected.length;
        batchBtn.classList.toggle('d-none', selected.length === 0);
    }

    checks.forEach(function(cb) {
        cb.addEventListener('change', updateBatchBtn);
    });

    batchBtn.addEventListener('click', function() {
        var selected = document.querySelectorAll('.history-check:checked');
        if (selected.length === 0) return;
        if (!confirm('确认删除选中的 ' + selected.length + ' 条记录吗？')) return;

        var ids = Array.from(selected).map(function(cb) { return cb.dataset.id; });
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        Promise.allSettled(ids.map(function(id) {
            return fetch('/user/jobs/analyze/history/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
        })).then(function(results) {
            var failed = results.filter(function(r) { return r.status === 'rejected' || (r.value && !r.value.ok); });
            if (failed.length > 0) {
                alert(failed.length + ' 条删除失败，请刷新重试');
            }
            window.location.reload();
        });
    });
})();

(function initJobsAnalyzeForm() {
    const form = document.getElementById('analyzeForm');
    if (!form) {
        return;
    }

    const textarea = form.querySelector('textarea[name="job_description"]');
    const submitBtn = document.getElementById('analyzeBtn');
    const submitBtnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
    const clientErrorEl = document.getElementById('jobDescriptionClientError');
    const lengthEl = document.getElementById('jobDescriptionLength');
    const progressHintEl = document.getElementById('analyzeProgressHint');
    const shouldAutoAnalyze = form.dataset.autoAnalyze === '1';
    const minLength = 50;
    const maxLength = textarea && Number.isFinite(textarea.maxLength) && textarea.maxLength > 0 ? textarea.maxLength : 5000;
    const progressHintDelayMs = 4000;
    let progressHintTimerId = null;
    let submitting = false;

    const updateLength = () => {
        if (!textarea || !lengthEl) {
            return;
        }
        lengthEl.textContent = String((textarea.value || '').trim().length);
    };

    const setSubmittingState = (active) => {
        submitting = active;
        if (submitBtn) {
            submitBtn.disabled = active;
            if (submitBtnText) {
                submitBtnText.textContent = active ? '分析中...' : (submitBtn.dataset.defaultText || '开始分析');
            }
        }

        if (progressHintEl) {
            progressHintEl.classList.add('d-none');
        }

        if (progressHintTimerId !== null) {
            window.clearTimeout(progressHintTimerId);
            progressHintTimerId = null;
        }

        if (active && progressHintEl) {
            progressHintTimerId = window.setTimeout(() => {
                progressHintEl.classList.remove('d-none');
            }, progressHintDelayMs);
        }
    };

    const showServerValidation = (validationErrors) => {
        if (!validationErrors || typeof validationErrors !== 'object') {
            return false;
        }

        const firstField = Object.keys(validationErrors)[0];
        const firstMessages = firstField ? validationErrors[firstField] : null;
        const firstMessage = Array.isArray(firstMessages) ? firstMessages[0] : '';
        if (firstField === 'job_description') {
            textarea?.classList.add('is-invalid');
            if (clientErrorEl) {
                clientErrorEl.textContent = firstMessage || '岗位描述长度不符合要求，请调整后再分析。';
                clientErrorEl.classList.remove('d-none');
            }
        }

        if (firstMessage && typeof window.appNotify === 'function') {
            window.appNotify(firstMessage, 'warning');
        }

        return !!firstMessage;
    };

    updateLength();

    if (textarea) {
        function autoResize() {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 600) + 'px';
        }
        autoResize();
        textarea.addEventListener('input', () => {
            updateLength();
            autoResize();
            const textLength = (textarea.value || '').trim().length;
            if (textLength >= minLength && textLength <= maxLength) {
                textarea.classList.remove('is-invalid');
                if (clientErrorEl) {
                    clientErrorEl.classList.add('d-none');
                }
            }
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        if (!textarea) {
            return;
        }

        const textLength = (textarea.value || '').trim().length;
        if (textLength < minLength) {
            textarea.classList.add('is-invalid');
            if (clientErrorEl) {
                clientErrorEl.textContent = '岗位描述至少需要 50 字，请补充后再分析。';
                clientErrorEl.classList.remove('d-none');
            }
            return;
        }

        if (textLength > maxLength) {
            textarea.classList.add('is-invalid');
            if (clientErrorEl) {
                clientErrorEl.textContent = '岗位描述最多支持 ' + maxLength + ' 字，请精简后再分析。';
                clientErrorEl.classList.remove('d-none');
            }
            return;
        }

        setSubmittingState(true);

        const submitPromise = window.quotaInterceptor && typeof window.quotaInterceptor.submitForm === 'function'
            ? window.quotaInterceptor.submitForm(form)
            : Promise.reject(new Error('缺少配额拦截器'));

        submitPromise.then((result) => {
            console.log('[job-analyze] result:', JSON.stringify(result).substring(0, 500));
            if (result && result.redirected) {
                return;
            }

            if (result && result.ok) {
                const redirectUrl = result?.data?.data?.redirect_url || result?.data?.redirect_url || result?.url;
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }

                if (result?.data?.message && typeof window.appNotify === 'function') {
                    window.appNotify(result.data.message, 'success');
                }

                window.location.reload();
                return;
            }

            const handledValidation = showServerValidation(result?.validationErrors || null);
            if (!handledValidation && typeof window.appNotify === 'function') {
                window.appNotify(result?.message || '分析提交失败，请稍后重试', 'error');
            }
            setSubmittingState(false);
        }).catch((error) => {
            console.error('[job-analyze] submit error:', error);
            if (error && error.quotaHandled) {
                setSubmittingState(false);
                return;
            }

            if (typeof window.appNotify === 'function') {
                window.appNotify(error?.message || '分析提交失败，请稍后重试', 'error');
            }
            setSubmittingState(false);
        });
    });

    window.addEventListener('beforeunload', () => {
        if (progressHintTimerId !== null) {
            window.clearTimeout(progressHintTimerId);
        }
    });

    if (shouldAutoAnalyze && textarea && submitBtn) {
        const textLength = (textarea.value || '').trim().length;
        if (textLength >= minLength) {
            window.setTimeout(() => {
                if (!submitBtn.disabled) {
                    form.requestSubmit();
                }
            }, 300);
        }
    }

    var useCreditSwitch = document.getElementById('useCreditSwitch');
    if (useCreditSwitch) {
        var creditGroup = document.getElementById('creditSelectGroup');
        var creditSelect = document.getElementById('creditSelect');
        useCreditSwitch.addEventListener('change', function() {
            creditGroup.classList.toggle('d-none', !this.checked);
            if (creditSelect) {
                creditSelect.disabled = !this.checked;
            }
        });
    }

    var bookmarkBtn = document.getElementById('bookmarkJdBtn');
    if (bookmarkBtn && textarea) {
        bookmarkBtn.addEventListener('click', function() {
            var jd = (textarea.value || '').trim();
            if (jd.length < 10) {
                if (typeof window.appNotify === 'function') window.appNotify('请先填写岗位描述再收藏', 'warning');
                return;
            }
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            var title = prompt('岗位名称（选填）') || '';
            var company = prompt('公司名称（选填）') || '';
            fetch('/user/jobs/bookmarks', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ title: title, company: company, job_description: jd })
            }).then(function(resp) { return resp.json(); }).then(function(data) {
                if (data.success) {
                    if (typeof window.appNotify === 'function') window.appNotify('岗位已收藏', 'success');
                    else alert('已收藏');
                } else {
                    if (typeof window.appNotify === 'function') window.appNotify(data.message || '收藏失败', 'error');
                }
            }).catch(function() {
                if (typeof window.appNotify === 'function') window.appNotify('收藏失败', 'error');
            });
        });
    }
})();

(function initAnalyzeMainTabs() {
    const tabContainer = document.getElementById('analyze-main-tabs');
    if (!tabContainer) {
        return;
    }

    const tabButtons = Array.from(tabContainer.querySelectorAll('[data-analyze-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-analyze-panel]'));
    if (tabButtons.length === 0 || panels.length === 0) {
        return;
    }

    const storageKey = 'jobs.analyze.activeTab';
    const availableTabs = new Set(tabButtons.map((btn) => btn.getAttribute('data-analyze-tab') || ''));

    const switchTab = (tabName, persist = true) => {
        const targetTab = availableTabs.has(tabName) ? tabName : 'manual';
        panels.forEach((panel) => {
            const active = panel.getAttribute('data-analyze-panel') === targetTab;
            panel.classList.toggle('d-none', !active);
        });
        tabButtons.forEach((btn) => {
            const active = btn.getAttribute('data-analyze-tab') === targetTab;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (persist) {
            try {
                window.localStorage.setItem(storageKey, targetTab);
            } catch (_e) {
                // 忽略本地存储异常，避免影响主流程
            }
        }
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            switchTab(btn.getAttribute('data-analyze-tab') || 'manual', true);
        });
    });

    let initialTab = 'manual';
    const params = new URLSearchParams(window.location.search);
    if (params.has('from_history')) {
        initialTab = 'history';
    } else {
        try {
            const storedTab = window.localStorage.getItem(storageKey);
            if (storedTab && availableTabs.has(storedTab)) {
                initialTab = storedTab;
            }
        } catch (_e) {
            // 忽略本地存储异常，保持默认行为
        }
    }

    switchTab(initialTab, false);
})();

(function initAnalyzeHelpTabs() {
    const tabContainer = document.getElementById('analyze-help-tabs');
    if (!tabContainer) {
        return;
    }

    const tabButtons = Array.from(tabContainer.querySelectorAll('[data-help-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-help-panel]'));
    if (tabButtons.length === 0 || panels.length === 0) {
        return;
    }

    const switchTab = (tabName) => {
        panels.forEach((panel) => {
            const active = panel.getAttribute('data-help-panel') === tabName;
            panel.classList.toggle('d-none', !active);
        });
        tabButtons.forEach((btn) => {
            const active = btn.getAttribute('data-help-tab') === tabName;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            switchTab(btn.getAttribute('data-help-tab') || 'guide');
        });
    });

    switchTab('guide');
})();
