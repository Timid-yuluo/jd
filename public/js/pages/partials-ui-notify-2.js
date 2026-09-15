(function () {
    if (window.__feedbackModalSubmitReady) {
        return;
    }

    var feedbackModalEl = document.getElementById('feedbackModal');
    var feedbackFormEl = document.getElementById('feedbackForm');

    if (!feedbackModalEl || !feedbackFormEl) {
        return;
    }

    window.__feedbackModalSubmitReady = true;

    var feedbackSubmitBtn = document.getElementById('feedbackSubmitBtn');
    var feedbackCloseControls = Array.prototype.slice.call(
        feedbackModalEl.querySelectorAll('[data-bs-dismiss="modal"], .btn-close')
    );
    var feedbackContextFields = {
        pageUrl: document.getElementById('feedbackPageUrl'),
        pageName: document.getElementById('feedbackPageName'),
        screen: document.getElementById('feedbackScreen'),
        language: document.getElementById('feedbackLang'),
        pageDisplay: document.getElementById('feedbackPageDisplay')
    };
    var feedbackInputFields = {
        category: feedbackFormEl.querySelector('[name="category"]'),
        title: feedbackFormEl.querySelector('[name="title"]'),
        content: feedbackFormEl.querySelector('[name="content"]')
    };
    var feedbackUiFields = {
        titleCounter: document.getElementById('feedbackTitleCounter'),
        contentCounter: document.getElementById('feedbackContentCounter'),
        draftHint: document.getElementById('feedbackDraftHint'),
        draftSwitcherWrap: document.getElementById('feedbackDraftSwitcherWrap'),
        draftSwitcher: document.getElementById('feedbackDraftSwitcher'),
        draftNewBtn: document.getElementById('feedbackDraftNewBtn'),
        draftDeleteBtn: document.getElementById('feedbackDraftDeleteBtn'),
        inlineSuccess: document.getElementById('feedbackInlineSuccess'),
        inlineSuccessText: document.getElementById('feedbackInlineSuccessText'),
        successLink: document.getElementById('feedbackSuccessLink'),
        copyIdBtn: document.getElementById('feedbackCopyIdBtn')
    };
    var feedbackState = {
        submitting: false,
        submitButtonHtml: feedbackSubmitBtn ? feedbackSubmitBtn.innerHTML : '',
        abortController: null,
        draftTimer: null,
        draftRestored: false,
        activeDraftKey: null,
        switchingDraft: false,
        lastSubmittedFeedback: null
    };
    var FEEDBACK_SUCCESS_CLOSE_DELAY_MS = 900;

    function notify(message, type) {
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, type || 'info');
            return;
        }

        console[type === 'error' ? 'error' : 'log'](message);
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getSubmitUrl() {
        return feedbackFormEl.getAttribute('action')
            || feedbackFormEl.getAttribute('data-route-feedback-store')
            || '';
    }

    function getFeedbackIndexUrl() {
        return feedbackFormEl.getAttribute('data-feedback-index-url') || '/feedback';
    }

    function populateFeedbackContext() {
        if (feedbackContextFields.pageUrl) {
            feedbackContextFields.pageUrl.value = window.location.href;
        }

        if (feedbackContextFields.pageName) {
            feedbackContextFields.pageName.value = document.title;
        }

        if (feedbackContextFields.screen) {
            feedbackContextFields.screen.value = window.screen.width + 'x' + window.screen.height;
        }

        if (feedbackContextFields.language) {
            feedbackContextFields.language.value = navigator.language || '';
        }

        if (feedbackContextFields.pageDisplay) {
            feedbackContextFields.pageDisplay.textContent = window.location.pathname + window.location.search;
        }
    }

    function pageDraftBucketKey() {
        return 'feedback-modal-drafts:' + window.location.pathname;
    }

    function normalizeDraftTitle(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .slice(0, 80);
    }

    function resolveDraftEntryKey(title) {
        var normalizedTitle = normalizeDraftTitle(title);

        return normalizedTitle !== '' ? 'title:' + normalizedTitle : 'title:__untitled__';
    }

    function loadDraftBucket() {
        try {
            var raw = localStorage.getItem(pageDraftBucketKey());
            var parsed = raw ? JSON.parse(raw) : null;

            if (!parsed || typeof parsed !== 'object') {
                return { latest_key: null, drafts: {} };
            }

            return {
                latest_key: typeof parsed.latest_key === 'string' ? parsed.latest_key : null,
                drafts: parsed.drafts && typeof parsed.drafts === 'object' ? parsed.drafts : {}
            };
        } catch (error) {
            console.error('feedback draft bucket load failed', error);
            return { latest_key: null, drafts: {} };
        }
    }

    function persistDraftBucket(bucket) {
        try {
            var draftEntries = Object.entries(bucket.drafts || {})
                .sort(function (left, right) {
                    return String(right[1]?.updated_at || '').localeCompare(String(left[1]?.updated_at || ''));
                })
                .slice(0, 5);

            localStorage.setItem(pageDraftBucketKey(), JSON.stringify({
                latest_key: bucket.latest_key || null,
                drafts: Object.fromEntries(draftEntries)
            }));
        } catch (error) {
            console.error('feedback draft bucket persist failed', error);
        }
    }

    function setDraftHint(message) {
        if (!feedbackUiFields.draftHint) {
            return;
        }

        feedbackUiFields.draftHint.textContent = message || '';
    }

    function feedbackSuccessIdentity(data) {
        if (!data || typeof data !== 'object') {
            return '';
        }

        var feedbackId = data.feedback_id ? '#' + data.feedback_id : '';
        var title = String(data.title || '').trim();

        if (feedbackId && title) {
            return '已提交反馈 ' + feedbackId + '《' + title + '》';
        }

        if (feedbackId) {
            return '已提交反馈 ' + feedbackId;
        }

        if (title) {
            return '已提交反馈《' + title + '》';
        }

        return '';
    }

    function setInlineSuccess(message, data) {
        if (!feedbackUiFields.inlineSuccess || !feedbackUiFields.inlineSuccessText) {
            return;
        }

        var identityText = feedbackSuccessIdentity(data);
        feedbackUiFields.inlineSuccessText.textContent = identityText !== ''
            ? identityText + '，正在关闭弹窗...'
            : (message || '反馈已提交，正在关闭弹窗...');
        feedbackUiFields.inlineSuccess.classList.remove('d-none');
    }

    function clearInlineSuccess() {
        if (!feedbackUiFields.inlineSuccess) {
            return;
        }

        feedbackUiFields.inlineSuccess.classList.add('d-none');
    }

    function updateCopyIdButton(data) {
        if (!feedbackUiFields.copyIdBtn) {
            return;
        }

        var feedbackId = data && typeof data === 'object' ? data.feedback_id : null;

        if (feedbackId) {
            feedbackUiFields.copyIdBtn.classList.remove('d-none');
            feedbackUiFields.copyIdBtn.dataset.feedbackId = String(feedbackId);
            feedbackUiFields.copyIdBtn.textContent = '复制反馈编号';
            return;
        }

        feedbackUiFields.copyIdBtn.classList.add('d-none');
        feedbackUiFields.copyIdBtn.dataset.feedbackId = '';
        feedbackUiFields.copyIdBtn.textContent = '复制反馈编号';
    }

    function updateSuccessLink(data) {
        if (!feedbackUiFields.successLink) {
            return;
        }

        var detailUrl = data && typeof data === 'object' ? data.detail_url : '';
        var listUrl = data && typeof data === 'object' && data.list_url ? data.list_url : getFeedbackIndexUrl();

        if (detailUrl) {
            feedbackUiFields.successLink.setAttribute('href', detailUrl);
            feedbackUiFields.successLink.textContent = '查看这条反馈';
            return;
        }

        feedbackUiFields.successLink.setAttribute('href', listUrl);
        feedbackUiFields.successLink.textContent = '去我的反馈查看';
    }

    function copyText(text) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                document.body.removeChild(textarea);
                resolve();
            } catch (error) {
                document.body.removeChild(textarea);
                reject(error);
            }
        });
    }

    function updateCounters() {
        if (feedbackUiFields.titleCounter && feedbackInputFields.title) {
            feedbackUiFields.titleCounter.textContent = feedbackInputFields.title.value.length + ' / 200';
        }

        if (feedbackUiFields.contentCounter && feedbackInputFields.content) {
            feedbackUiFields.contentCounter.textContent = feedbackInputFields.content.value.length + ' / 5000';
        }
    }

    function createDraftPayload() {
        return {
            category: feedbackInputFields.category ? feedbackInputFields.category.value : 'bug',
            title: feedbackInputFields.title ? feedbackInputFields.title.value : '',
            content: feedbackInputFields.content ? feedbackInputFields.content.value : '',
            updated_at: new Date().toISOString()
        };
    }

    function hasMeaningfulDraft(payload) {
        if (!payload) {
            return false;
        }

        return [payload.title, payload.content]
            .some(function (value) { return String(value || '').trim() !== ''; });
    }

    function sortedDraftEntries(bucket) {
        return Object.entries(bucket.drafts || {}).sort(function (left, right) {
            return String(right[1]?.updated_at || '').localeCompare(String(left[1]?.updated_at || ''));
        });
    }

    function draftLabel(payload, draftKey) {
        var title = String(payload?.title || '').trim();
        var time = payload?.updated_at
            ? new Date(payload.updated_at).toLocaleString('zh-CN', {
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            })
            : '';

        if (title !== '') {
            return title + (time ? ' · ' + time : '');
        }

        return '未命名草稿' + (draftKey === 'title:__untitled__' ? '' : '') + (time ? ' · ' + time : '');
    }

    function renderDraftSwitcher() {
        var switcherWrap = feedbackUiFields.draftSwitcherWrap;
        var switcher = feedbackUiFields.draftSwitcher;
        var deleteBtn = feedbackUiFields.draftDeleteBtn;

        if (!switcherWrap || !switcher) {
            return;
        }

        var bucket = loadDraftBucket();
        var entries = sortedDraftEntries(bucket);

        switcher.innerHTML = '';

        if (entries.length <= 1) {
            switcherWrap.classList.add('d-none');
            if (deleteBtn) {
                deleteBtn.disabled = !feedbackState.activeDraftKey;
            }
            return;
        }

        entries.forEach(function (entry) {
            var draftKey = entry[0];
            var payload = entry[1];
            var option = document.createElement('option');

            option.value = draftKey;
            option.textContent = draftLabel(payload, draftKey);
            option.selected = draftKey === feedbackState.activeDraftKey;
            switcher.appendChild(option);
        });

        switcherWrap.classList.remove('d-none');
        if (deleteBtn) {
            deleteBtn.disabled = !feedbackState.activeDraftKey;
        }
    }

    function applyDraftPayload(payload, draftKey, message) {
        if (!payload) {
            return;
        }

        feedbackState.switchingDraft = true;

        if (feedbackInputFields.category && payload.category) {
            feedbackInputFields.category.value = payload.category;
        }

        if (feedbackInputFields.title) {
            feedbackInputFields.title.value = payload.title || '';
        }

        if (feedbackInputFields.content) {
            feedbackInputFields.content.value = payload.content || '';
        }

        feedbackState.switchingDraft = false;
        feedbackState.draftRestored = true;
        feedbackState.activeDraftKey = draftKey || resolveDraftEntryKey(payload.title);
        updateCounters();
        setDraftHint(message || '已恢复草稿');
        renderDraftSwitcher();
    }

    function saveDraft() {
        try {
            var payload = createDraftPayload();
            var bucket = loadDraftBucket();
            var nextDraftKey = resolveDraftEntryKey(payload.title);

            if (!hasMeaningfulDraft(payload)) {
                if (feedbackState.activeDraftKey && bucket.drafts[feedbackState.activeDraftKey]) {
                    delete bucket.drafts[feedbackState.activeDraftKey];
                }

                bucket.latest_key = null;
                if (Object.keys(bucket.drafts).length === 0) {
                    localStorage.removeItem(pageDraftBucketKey());
                } else {
                    persistDraftBucket(bucket);
                }

                feedbackState.activeDraftKey = null;
                setDraftHint('');
                renderDraftSwitcher();
                return;
            }

            if (
                feedbackState.activeDraftKey
                && feedbackState.activeDraftKey !== nextDraftKey
                && bucket.drafts[feedbackState.activeDraftKey]
            ) {
                delete bucket.drafts[feedbackState.activeDraftKey];
            }

            bucket.drafts[nextDraftKey] = Object.assign({}, payload, {
                draft_key: nextDraftKey,
                page_path: window.location.pathname
            });
            bucket.latest_key = nextDraftKey;
            persistDraftBucket(bucket);
            feedbackState.activeDraftKey = nextDraftKey;
            setDraftHint('草稿已自动保存');
            renderDraftSwitcher();
        } catch (error) {
            console.error('feedback draft save failed', error);
        }
    }

    function scheduleDraftSave() {
        if (feedbackState.draftTimer) {
            window.clearTimeout(feedbackState.draftTimer);
        }

        feedbackState.draftTimer = window.setTimeout(function () {
            saveDraft();
            feedbackState.draftTimer = null;
        }, 300);
    }

    function clearDraft() {
        try {
            var bucket = loadDraftBucket();

            if (feedbackState.activeDraftKey && bucket.drafts[feedbackState.activeDraftKey]) {
                delete bucket.drafts[feedbackState.activeDraftKey];
            }

            if (bucket.latest_key === feedbackState.activeDraftKey) {
                bucket.latest_key = Object.keys(bucket.drafts)[0] || null;
            }

            if (Object.keys(bucket.drafts).length === 0) {
                localStorage.removeItem(pageDraftBucketKey());
            } else {
                persistDraftBucket(bucket);
            }
        } catch (error) {
            console.error('feedback draft clear failed', error);
        }

        feedbackState.activeDraftKey = null;
        setDraftHint('');
        renderDraftSwitcher();
    }

    function deleteDraftByKey(draftKey) {
        if (!draftKey) {
            return false;
        }

        try {
            var bucket = loadDraftBucket();

            if (!bucket.drafts[draftKey]) {
                return false;
            }

            delete bucket.drafts[draftKey];

            var remainingEntries = sortedDraftEntries(bucket);
            bucket.latest_key = remainingEntries[0] ? remainingEntries[0][0] : null;

            if (remainingEntries.length === 0) {
                localStorage.removeItem(pageDraftBucketKey());
            } else {
                persistDraftBucket(bucket);
            }

            feedbackState.activeDraftKey = bucket.latest_key;
            renderDraftSwitcher();

            if (bucket.latest_key && bucket.drafts[bucket.latest_key]) {
                applyDraftPayload(bucket.drafts[bucket.latest_key], bucket.latest_key, '已删除当前草稿，已切换到最近草稿');
            } else {
                feedbackState.activeDraftKey = null;
                feedbackState.draftRestored = false;
                resetFeedbackForm();
                setDraftHint('已删除当前草稿');
            }

            return true;
        } catch (error) {
            console.error('feedback draft delete failed', error);
            return false;
        }
    }

    function restoreDraft() {
        var bucket = loadDraftBucket();
        var currentTitleKey = resolveDraftEntryKey(feedbackInputFields.title ? feedbackInputFields.title.value : '');
        var targetKey = currentTitleKey !== 'title:__untitled__' && bucket.drafts[currentTitleKey]
            ? currentTitleKey
            : bucket.latest_key;
        var payload = targetKey && bucket.drafts[targetKey] ? bucket.drafts[targetKey] : null;

        if (!payload) {
            var fallbackKeys = Object.keys(bucket.drafts || {}).sort(function (left, right) {
                return String(bucket.drafts[right]?.updated_at || '').localeCompare(String(bucket.drafts[left]?.updated_at || ''));
            });
            targetKey = fallbackKeys[0] || null;
            payload = targetKey ? bucket.drafts[targetKey] : null;
        }

        if (!hasMeaningfulDraft(payload)) {
            feedbackState.draftRestored = false;
            feedbackState.activeDraftKey = null;
            setDraftHint('');
            updateCounters();
            renderDraftSwitcher();
            return;
        }

        if (
            feedbackInputFields.title
            && String(feedbackInputFields.title.value || '').trim() !== ''
            && currentTitleKey !== targetKey
            && bucket.drafts[currentTitleKey]
        ) {
            renderDraftSwitcher();
            return;
        }

        applyDraftPayload(payload, targetKey, '已恢复上次未提交的草稿');
    }

    function resetFeedbackForm() {
        feedbackFormEl.reset();
        populateFeedbackContext();
        updateCounters();
        feedbackState.draftRestored = false;
        feedbackState.lastSubmittedFeedback = null;
        clearInlineSuccess();
        updateCopyIdButton(null);
        updateSuccessLink(null);
    }

    function getFeedbackModalInstance() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }

        return bootstrap.Modal.getInstance(feedbackModalEl) || new bootstrap.Modal(feedbackModalEl);
    }

    function hideFeedbackModal() {
        var modal = getFeedbackModalInstance();

        if (modal) {
            modal.hide();
            return;
        }

        feedbackModalEl.classList.remove('show');
        feedbackModalEl.setAttribute('aria-hidden', 'true');
        feedbackModalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
    }

    function setSubmitting(isSubmitting) {
        feedbackState.submitting = isSubmitting;

        if (feedbackSubmitBtn) {
            feedbackSubmitBtn.disabled = isSubmitting;
            feedbackSubmitBtn.setAttribute('aria-disabled', isSubmitting ? 'true' : 'false');
            feedbackSubmitBtn.innerHTML = isSubmitting
                ? '<span class="spinner-border spinner-border-sm me-1"></span>提交中...'
                : feedbackState.submitButtonHtml;
        }

        feedbackCloseControls.forEach(function (control) {
            control.disabled = isSubmitting;
            if (isSubmitting) {
                control.setAttribute('aria-disabled', 'true');
            } else {
                control.removeAttribute('aria-disabled');
            }
        });
    }

    function setSubmitButtonSuccessState() {
        if (!feedbackSubmitBtn) {
            return;
        }

        feedbackSubmitBtn.innerHTML = '<i class="ti ti-check me-1"></i> 已提交';
    }

    function wait(milliseconds) {
        return new Promise(function (resolve) {
            window.setTimeout(resolve, milliseconds);
        });
    }

    function createPayload() {
        return Object.fromEntries(new FormData(feedbackFormEl));
    }

    function parseResponse(response) {
        return response.text().then(function (text) {
            var data = {};

            try {
                data = text ? JSON.parse(text) : {};
            } catch (error) {
                data = {};
            }

            return {
                ok: response.ok,
                status: response.status,
                data: data
            };
        });
    }

    function buildErrorMessage(result) {
        if (result.status === 419) {
            return '登录状态已过期，请刷新页面后重试';
        }

        if (result.status === 422) {
            return result.data.errors
                ? Object.values(result.data.errors).flat().join('\n')
                : (result.data.message || '提交内容校验失败，请检查后重试');
        }

        return result.data.message || ('提交失败（HTTP ' + result.status + '）');
    }

    function handleSubmitSuccess(result) {
        if ((result.data.success ?? true) !== true) {
            notify(result.data.message || '提交失败，请稍后重试', 'error');
            return Promise.resolve();
        }

        clearDraft();
        feedbackState.lastSubmittedFeedback = result.data.data || null;
        setSubmitButtonSuccessState();
        setInlineSuccess(result.data.message || '反馈已提交，正在关闭弹窗...', feedbackState.lastSubmittedFeedback);
        updateCopyIdButton(feedbackState.lastSubmittedFeedback);
        updateSuccessLink(feedbackState.lastSubmittedFeedback);

        return wait(FEEDBACK_SUCCESS_CLOSE_DELAY_MS).then(function () {
            try {
                resetFeedbackForm();
            } catch (error) {
                console.error('feedback reset after success failed', error);
            }

            try {
                hideFeedbackModal();
            } catch (error) {
                console.error('feedback modal hide after success failed', error);
            }

            try {
                notify(result.data.message || '感谢您的反馈，我们会尽快处理！', 'success');
            } catch (error) {
                console.error('feedback notify after success failed', error);
            }
        });
    }

    function handleSubmitError(error) {
        if (error && error.name === 'AbortError') {
            return;
        }

        console.error('feedback submit failed on frontend', error);
        notify(error && error.message ? error.message : '网络错误，请稍后重试', 'error');
    }

    function submitFeedback() {
        if (feedbackState.submitting) {
            notify('反馈正在提交中，请勿重复点击', 'warning');
            return Promise.resolve();
        }

        if (typeof feedbackFormEl.reportValidity === 'function' && !feedbackFormEl.reportValidity()) {
            return Promise.resolve();
        }

        var submitUrl = getSubmitUrl();
        if (!submitUrl) {
            notify('反馈提交地址缺失，请刷新页面后重试', 'error');
            return Promise.resolve();
        }

        var csrfToken = getCsrfToken();
        feedbackState.abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        setSubmitting(true);

        return fetch(submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(createPayload()),
            signal: feedbackState.abortController ? feedbackState.abortController.signal : undefined
        })
        .then(parseResponse)
        .then(function (result) {
            if (!result.ok || (result.data.success ?? true) !== true) {
                throw new Error(buildErrorMessage(result));
            }

            feedbackState.abortController = null;
            return handleSubmitSuccess(result);
        })
        .catch(handleSubmitError)
        .finally(function () {
            feedbackState.abortController = null;
            setSubmitting(false);
        });
    }

    feedbackModalEl.addEventListener('show.bs.modal', function () {
        populateFeedbackContext();
        clearInlineSuccess();
        updateCopyIdButton(null);
        updateSuccessLink(null);
        restoreDraft();
        updateCounters();
    });

    feedbackModalEl.addEventListener('hidden.bs.modal', function () {
        if (feedbackState.submitting && feedbackState.abortController) {
            feedbackState.abortController.abort();
        }

        if (feedbackState.draftTimer) {
            window.clearTimeout(feedbackState.draftTimer);
            feedbackState.draftTimer = null;
        }

        if (!feedbackState.submitting) {
            saveDraft();
        }

        setSubmitting(false);
        resetFeedbackForm();
        feedbackState.activeDraftKey = null;
        feedbackState.switchingDraft = false;
        renderDraftSwitcher();
    });

    feedbackFormEl.addEventListener('submit', function (event) {
        event.preventDefault();
        submitFeedback();
    });

    [feedbackInputFields.category, feedbackInputFields.title, feedbackInputFields.content].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('input', function () {
            if (feedbackState.switchingDraft) {
                return;
            }

            feedbackState.draftRestored = false;
            updateCounters();
            scheduleDraftSave();
        });

        field.addEventListener('change', function () {
            if (feedbackState.switchingDraft) {
                return;
            }

            feedbackState.draftRestored = false;
            updateCounters();
            scheduleDraftSave();
        });
    });

    if (feedbackUiFields.draftSwitcher) {
        feedbackUiFields.draftSwitcher.addEventListener('change', function () {
            var selectedDraftKey = this.value;
            var bucket = loadDraftBucket();
            var payload = bucket.drafts[selectedDraftKey] || null;

            if (!payload) {
                return;
            }

            applyDraftPayload(payload, selectedDraftKey, '已切换到所选草稿');
        });
    }

    if (feedbackUiFields.draftNewBtn) {
        feedbackUiFields.draftNewBtn.addEventListener('click', function () {
            if (feedbackState.draftTimer) {
                window.clearTimeout(feedbackState.draftTimer);
                feedbackState.draftTimer = null;
            }

            saveDraft();
            feedbackState.activeDraftKey = null;
            feedbackState.draftRestored = false;
            resetFeedbackForm();
            setDraftHint('已切换到空白草稿');
            renderDraftSwitcher();
            feedbackInputFields.title?.focus();
        });
    }

    if (feedbackUiFields.draftDeleteBtn) {
        feedbackUiFields.draftDeleteBtn.addEventListener('click', function () {
            if (!feedbackState.activeDraftKey) {
                setDraftHint('当前没有可删除的草稿');
                return;
            }

            deleteDraftByKey(feedbackState.activeDraftKey);
        });
    }

    if (feedbackUiFields.copyIdBtn) {
        feedbackUiFields.copyIdBtn.addEventListener('click', function () {
            var feedbackId = this.dataset.feedbackId || '';

            if (!feedbackId) {
                notify('当前没有可复制的反馈编号', 'warning');
                return;
            }

            copyText(feedbackId).then(function () {
                feedbackUiFields.copyIdBtn.textContent = '已复制 #' + feedbackId;
                notify('反馈编号 #' + feedbackId + ' 已复制', 'success');
            }).catch(function () {
                notify('复制反馈编号失败，请手动记录', 'error');
            });
        });
    }

    populateFeedbackContext();
    updateCounters();
    updateCopyIdButton(null);
    updateSuccessLink();
    renderDraftSwitcher();
})();
