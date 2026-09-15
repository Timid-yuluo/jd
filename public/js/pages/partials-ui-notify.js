(function () {
    if (window.__appNotifyReady) return;
    window.__appNotifyReady = true;

    const wrap = document.getElementById('app-toast-wrap');
    const iconMap = {
        success: 'ti ti-circle-check',
        error: 'ti ti-alert-circle',
        warning: 'ti ti-alert-triangle',
        info: 'ti ti-info-circle',
    };

    function normalizeType(type, message) {
        const raw = String(type || '').toLowerCase();
        if (raw === 'danger' || raw === 'error') return 'error';
        if (raw === 'warn' || raw === 'warning') return 'warning';
        if (['success', 'error', 'warning', 'info'].includes(raw)) return raw;
        const msg = String(message || '');
        if (/失败|错误|error|fail/i.test(msg)) return 'error';
        if (/警告|warning|注意/i.test(msg)) return 'warning';
        if (/成功|success|完成/i.test(msg)) return 'success';
        return 'info';
    }

    function closeToast(toast) {
        if (!toast) return;
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 220);
    }

    window.appNotify = function (message, type = 'info', options = {}) {
        if (!wrap) return;
        const safeMessage = String(message || '').trim();
        if (!safeMessage) return;

        const normalizedType = normalizeType(type, safeMessage);
        const duration = Math.max(1200, Math.min(10000, Number(options.duration || 3200)));
        const title = options.title || (
            normalizedType === 'success' ? '成功' :
            normalizedType === 'error' ? '错误' :
            normalizedType === 'warning' ? '提醒' : '通知'
        );

        const toast = document.createElement('div');
        toast.className = 'app-toast app-toast--' + normalizedType;
        toast.innerHTML = `
            <div class="app-toast-head">
                <i class="${iconMap[normalizedType]}"></i>
                <span>${title}</span>
                <button type="button" class="app-toast-close" aria-label="关闭">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="app-toast-body"></div>
            <div class="app-toast-progress" style="animation-duration:${duration}ms;"></div>
        `;
        toast.querySelector('.app-toast-body').textContent = safeMessage;
        toast.querySelector('.app-toast-close').addEventListener('click', () => closeToast(toast));
        wrap.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => closeToast(toast), duration);
    };

    // 可撤销操作通知：显示 toast 并带"撤销"按钮
    window.appNotifyUndo = function (message, onUndo, options = {}) {
        if (!wrap) return;
        const safeMessage = String(message || '').trim();
        if (!safeMessage) return;

        const duration = Math.max(3000, Math.min(8000, Number(options.duration || 5000)));
        const toast = document.createElement('div');
        toast.className = 'app-toast app-toast--info';
        toast.innerHTML = `
            <div class="app-toast-head">
                <i class="ti ti-info-circle"></i>
                <span>操作提示</span>
                <button type="button" class="app-toast-close" aria-label="关闭"><i class="ti ti-x"></i></button>
            </div>
            <div class="app-toast-body"></div>
            <div style="padding:0 12px 8px;display:flex;gap:6px;justify-content:flex-end;">
                <button type="button" class="btn btn-sm btn-outline-warning app-toast-undo-btn">撤销</button>
            </div>
            <div class="app-toast-progress" style="animation-duration:${duration}ms;"></div>
        `;
        toast.querySelector('.app-toast-body').textContent = safeMessage;
        toast.querySelector('.app-toast-close').addEventListener('click', () => closeToast(toast));
        toast.querySelector('.app-toast-undo-btn').addEventListener('click', () => {
            closeToast(toast);
            if (typeof onUndo === 'function') onUndo();
        });
        wrap.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => closeToast(toast), duration);
    };

    // Replace native alert globally with styled toast.
    window.alert = function (message) {
        window.appNotify(message, 'info');
    };

    function getConfirmNodes() {
        return {
            mask: document.getElementById('app-confirm-mask'),
            title: document.getElementById('app-confirm-title'),
            message: document.getElementById('app-confirm-message'),
            ok: document.getElementById('app-confirm-ok'),
            cancel: document.getElementById('app-confirm-cancel'),
        };
    }

    function getPromptNodes() {
        return {
            mask: document.getElementById('app-prompt-mask'),
            title: document.getElementById('app-prompt-title'),
            message: document.getElementById('app-prompt-message'),
            input: document.getElementById('app-prompt-input'),
            textarea: document.getElementById('app-prompt-textarea'),
            hint: document.getElementById('app-prompt-hint'),
            counter: document.getElementById('app-prompt-counter'),
            ok: document.getElementById('app-prompt-ok'),
            cancel: document.getElementById('app-prompt-cancel'),
        };
    }

    window.appConfirm = function (message, options = {}) {
        const nodes = getConfirmNodes();
        if (!nodes.mask) return Promise.resolve(false);

        return new Promise((resolve) => {
            const cleanup = () => {
                nodes.mask.style.display = 'none';
                nodes.ok.removeEventListener('click', onOk);
                nodes.cancel.removeEventListener('click', onCancel);
                nodes.mask.removeEventListener('click', onMaskClick);
                document.removeEventListener('keydown', onEsc);
            };
            const onOk = () => { cleanup(); resolve(true); };
            const onCancel = () => { cleanup(); resolve(false); };
            const onMaskClick = (event) => {
                if (event.target === nodes.mask) onCancel();
            };
            const onEsc = (event) => {
                if (event.key === 'Escape') onCancel();
            };

            nodes.title.textContent = String(options.title || '请确认');
            nodes.message.textContent = String(message || '确定继续吗？');
            nodes.mask.style.display = 'block';

            nodes.ok.addEventListener('click', onOk);
            nodes.cancel.addEventListener('click', onCancel);
            nodes.mask.addEventListener('click', onMaskClick);
            document.addEventListener('keydown', onEsc);
        });
    };

    window.appPrompt = function (message, defaultValue = '', options = {}) {
        const nodes = getPromptNodes();
        if (!nodes.mask) return Promise.resolve(null);

        return new Promise((resolve) => {
            const useTextarea = options.inputType === 'textarea' || options.multiline === true;
            const inputType = options.inputType === 'number' ? 'number' : 'text';
            const required = options.required === true;
            const trimInput = options.trim !== false;
            const min = options.min;
            const max = options.max;
            const step = options.step;
            const rows = Number.isInteger(options.rows) ? Math.max(2, options.rows) : 5;
            const maxLength = Number.isInteger(options.maxLength) ? Math.max(1, options.maxLength) : null;
            const placeholder = options.placeholder;
            const pattern = typeof options.pattern === 'string' && options.pattern.trim() !== '' ? options.pattern.trim() : null;
            const patternMessage = options.patternMessage || '输入格式不正确';
            let patternRegex = null;
            let activeField = nodes.input;
            if (pattern !== null) {
                try {
                    patternRegex = new RegExp(pattern);
                } catch (error) {
                    patternRegex = null;
                }
            }

            const setHint = (text) => {
                const safeText = String(text || '').trim();
                if (!nodes.hint) return;
                if (safeText === '') {
                    nodes.hint.style.display = 'none';
                    nodes.hint.textContent = '';
                    return;
                }
                nodes.hint.style.display = 'block';
                nodes.hint.textContent = safeText;
            };

            const setCounter = (valueText) => {
                if (!nodes.counter) return;
                if (typeof maxLength !== 'number') {
                    nodes.counter.style.display = 'none';
                    nodes.counter.textContent = '';
                    return;
                }
                const currentLength = String(valueText || '').length;
                nodes.counter.style.display = 'block';
                nodes.counter.textContent = `${currentLength} / ${maxLength}`;
            };

            const cleanup = () => {
                nodes.mask.style.display = 'none';
                nodes.ok.removeEventListener('click', onOk);
                nodes.cancel.removeEventListener('click', onCancel);
                nodes.mask.removeEventListener('click', onMaskClick);
                nodes.input.removeEventListener('keydown', onEnterOrEsc);
                nodes.textarea.removeEventListener('keydown', onEnterOrEsc);
                nodes.input.removeEventListener('input', onFieldInput);
                nodes.textarea.removeEventListener('input', onFieldInput);
                document.removeEventListener('keydown', onEsc);
            };
            const onOk = () => {
                const rawValue = String(activeField.value ?? '');
                const normalizedValue = trimInput ? rawValue.trim() : rawValue;

                if (required && normalizedValue === '') {
                    setHint(options.requiredMessage || '请输入内容');
                    return;
                }

                if (inputType === 'number' && normalizedValue !== '') {
                    const numericValue = Number(normalizedValue);
                    if (!Number.isFinite(numericValue)) {
                        setHint(options.invalidMessage || '请输入有效数字');
                        return;
                    }
                    if (typeof min === 'number' && numericValue < min) {
                        setHint(options.minMessage || `输入值不能小于 ${min}`);
                        return;
                    }
                    if (typeof max === 'number' && numericValue > max) {
                        setHint(options.maxMessage || `输入值不能大于 ${max}`);
                        return;
                    }
                }
                if (patternRegex && normalizedValue !== '' && !patternRegex.test(normalizedValue)) {
                    setHint(patternMessage);
                    return;
                }

                cleanup();
                resolve(normalizedValue === '' ? null : normalizedValue);
            };
            const onCancel = () => { cleanup(); resolve(null); };
            const onMaskClick = (event) => {
                if (event.target === nodes.mask) onCancel();
            };
            const onEsc = (event) => {
                if (event.key === 'Escape') onCancel();
            };
            const onEnterOrEsc = (event) => {
                if (event.key === 'Enter' && (!useTextarea || event.ctrlKey || event.metaKey)) onOk();
                if (event.key === 'Escape') onCancel();
            };
            const onFieldInput = () => {
                setHint('');
                setCounter(activeField.value);
            };

            nodes.title.textContent = String(options.title || '请输入');
            nodes.message.textContent = String(message || '');
            nodes.input.style.display = useTextarea ? 'none' : '';
            nodes.textarea.style.display = useTextarea ? '' : 'none';
            activeField = useTextarea ? nodes.textarea : nodes.input;

            if (useTextarea) {
                nodes.textarea.rows = rows;
                nodes.input.removeAttribute('min');
                nodes.input.removeAttribute('max');
                nodes.input.removeAttribute('step');
            } else {
                nodes.input.type = inputType;
                if (inputType === 'number') {
                    nodes.input.inputMode = 'numeric';
                    if (typeof min === 'number') nodes.input.min = String(min);
                    else nodes.input.removeAttribute('min');
                    if (typeof max === 'number') nodes.input.max = String(max);
                    else nodes.input.removeAttribute('max');
                    if (typeof step === 'number') nodes.input.step = String(step);
                    else nodes.input.removeAttribute('step');
                } else {
                    nodes.input.inputMode = 'text';
                    nodes.input.removeAttribute('min');
                    nodes.input.removeAttribute('max');
                    nodes.input.removeAttribute('step');
                }
            }
            if (typeof placeholder === 'string') {
                activeField.placeholder = placeholder;
            } else {
                nodes.input.removeAttribute('placeholder');
                nodes.textarea.removeAttribute('placeholder');
            }
            if (typeof maxLength === 'number') {
                activeField.maxLength = maxLength;
            } else {
                activeField.removeAttribute('maxLength');
            }
            activeField.value = String(defaultValue ?? '');
            if (activeField !== nodes.input) nodes.input.value = '';
            if (activeField !== nodes.textarea) nodes.textarea.value = '';
            setHint('');
            setCounter(activeField.value);
            nodes.mask.style.display = 'block';

            nodes.ok.addEventListener('click', onOk);
            nodes.cancel.addEventListener('click', onCancel);
            nodes.mask.addEventListener('click', onMaskClick);
            nodes.input.addEventListener('keydown', onEnterOrEsc);
            nodes.textarea.addEventListener('keydown', onEnterOrEsc);
            nodes.input.addEventListener('input', onFieldInput);
            nodes.textarea.addEventListener('input', onFieldInput);
            document.addEventListener('keydown', onEsc);

            setTimeout(() => {
                activeField.focus();
                if (!useTextarea) {
                    activeField.select();
                }
            }, 0);
        });
    };

    // Bridge declarative/inline confirm handlers to appConfirm.
    function bridgeInlineConfirm() {
        const selector = '[data-app-confirm], [onsubmit*="confirm("], [onclick*="confirm("]';
        const elements = document.querySelectorAll(selector);
        const confirmRegex = /return\s+confirm\((["'`])([\s\S]*?)\1\)\s*;?/i;

        elements.forEach((element) => {
            const declarativeMessage = element.getAttribute('data-app-confirm');
            if (declarativeMessage && declarativeMessage.trim() !== '') {
                if (element.tagName === 'FORM') {
                    element.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        const ok = await window.appConfirm(declarativeMessage);
                        if (ok) {
                            HTMLFormElement.prototype.submit.call(element);
                        }
                    });
                    return;
                }

                element.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const ok = await window.appConfirm(declarativeMessage);
                    if (!ok) return;
                    if (element.tagName === 'A' && element.href) {
                        window.location.href = element.href;
                        return;
                    }
                    const form = element.closest('form');
                    if (form) {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
                return;
            }

            const submitCode = element.getAttribute('onsubmit');
            const clickCode = element.getAttribute('onclick');
            const code = submitCode || clickCode || '';
            const match = code.match(confirmRegex);
            if (!match) return;

            const message = match[2];
            if (submitCode) {
                element.removeAttribute('onsubmit');
                element.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const ok = await window.appConfirm(message);
                    if (ok) {
                        HTMLFormElement.prototype.submit.call(element);
                    }
                });
            } else if (clickCode) {
                element.removeAttribute('onclick');
                element.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const ok = await window.appConfirm(message);
                    if (!ok) return;
                    if (element.tagName === 'A' && element.href) {
                        window.location.href = element.href;
                        return;
                    }
                    const form = element.closest('form');
                    if (form) {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bridgeInlineConfirm);
    } else {
        bridgeInlineConfirm();
    }

    /**
     * 统一解析 fetch 错误响应并展示通知。
     *
     * 支持的后端错误格式：
     * - Laravel ValidationException: { message, errors: { field: [msg] } }
     * - 自定义 API 错误: { code, message, data?, errors? }
     * - 配额超限: { code: 'QUOTA_EXCEEDED...', message, data }
     * - 纯文本/HTML 错误页
     *
     * @param {Response} response - fetch 返回的 Response 对象
     * @param {object} options - { silent?: boolean, fallbackMessage?: string }
     * @returns {Promise<{status: number, message: string, errors: object|null, data: object|null}>}
     */
    window.appFetchError = async function (response, options = {}) {
        const fallback = options.fallbackMessage || '请求失败，请稍后重试';
        const result = {
            status: response.status,
            message: fallback,
            errors: null,
            data: null,
        };

        // 尝试解析 JSON 响应体
        const contentType = response.headers.get('Content-Type') || '';
        if (contentType.includes('application/json')) {
            try {
                const payload = await response.json();
                result.data = payload;

                // 提取主消息
                if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
                    result.message = payload.message.trim();
                }

                // 提取验证错误
                if (payload && payload.errors && typeof payload.errors === 'object') {
                    result.errors = payload.errors;
                    // 如果没有主消息，取第一个验证错误作为主消息
                    if (result.message === fallback) {
                        const firstKey = Object.keys(payload.errors)[0];
                        const firstError = firstKey ? payload.errors[firstKey] : null;
                        if (Array.isArray(firstError) && firstError.length > 0) {
                            result.message = String(firstError[0] || '').trim() || fallback;
                        }
                    }
                }

                // 配额超限特殊处理：附带升级引导
                if (payload && payload.code && String(payload.code).startsWith('QUOTA_EXCEEDED')) {
                    const plan = payload.data?.current_plan ? `（${payload.data.current_plan}）` : '';
                    const reset = payload.data?.quota_resets_at ? `，${payload.data.quota_resets_at} 重置` : '';
                    result.message = `${result.message}${plan}${reset}`;
                }
            } catch (_parseError) {
                // JSON 解析失败，使用 fallback
            }
        } else if (response.redirected && response.url) {
            // 重定向响应（如 403 重定向到登录页）
            result.message = '操作未授权，请重新登录';
        }

        // 根据状态码调整默认消息
        if (result.message === fallback) {
            switch (response.status) {
                case 401:
                    result.message = '登录已过期，请重新登录';
                    break;
                case 403:
                    result.message = '无权执行此操作';
                    break;
                case 404:
                    result.message = '请求的资源不存在';
                    break;
                case 422:
                    result.message = '提交的数据有误，请检查后重试';
                    break;
                case 429:
                    result.message = '操作过于频繁，请稍后再试';
                    break;
                case 500:
                case 502:
                case 503:
                    result.message = '服务器暂时不可用，请稍后重试';
                    break;
            }
        }

        // 展示通知（除非 silent 模式）
        if (!options.silent && result.message) {
            const type = response.status >= 500 ? 'error' : 'warning';
            window.appNotify(result.message, type);
        }

        return result;
    };

    /**
     * 统一处理 fetch Promise 链中的网络错误（非 HTTP 错误）。
     * 用于 catch 块中处理 fetch 抛出的异常（网络中断、DNS 失败等）。
     *
     * @param {Error} error - fetch 抛出的 Error 对象
     * @param {object} options - { silent?: boolean, fallbackMessage?: string }
     * @returns {{status: number, message: string, errors: null, data: null}}
     */
    window.appFetchCatch = function (error, options = {}) {
        const fallback = options.fallbackMessage || '网络连接异常，请检查网络后重试';
        const result = {
            status: 0,
            message: fallback,
            errors: null,
            data: null,
        };

        // 区分超时和其他网络错误
        if (error && error.name === 'AbortError') {
            result.message = '请求超时，请稍后重试';
        } else if (error && error.message) {
            // 保留原始错误消息（如果有意义的话）
            const msg = String(error.message).trim();
            if (msg && msg !== 'Failed to fetch' && msg !== 'NetworkError when attempting to fetch resource.') {
                result.message = msg;
            }
        }

        if (!options.silent && result.message) {
            window.appNotify(result.message, 'error');
        }

        return result;
    };
})();
