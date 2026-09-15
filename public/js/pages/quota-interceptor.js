/**
 * Quota Check Interceptor
 * 拦截 AI 操作的配额限制，弹出次卡确认/升级引导弹窗，并在用户确认后自动重试原请求。
 */
(function() {
    'use strict';

    var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var UPGRADE_URL = '/user/pricing';
    var CREDIT_PACKS_URL = '/user/credit-packs';
    var originalFetch = typeof window.fetch === 'function' ? window.fetch.bind(window) : null;

    if (!originalFetch) {
        return;
    }

    function notify(message, level) {
        if (!message) {
            return;
        }
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, level || 'warning');
            return;
        }
        window.alert(message);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getHeaderValue(headers, name) {
        if (!headers || !name) {
            return '';
        }
        if (typeof headers.get === 'function') {
            return headers.get(name) || headers.get(name.toLowerCase()) || '';
        }
        if (typeof headers === 'object') {
            return headers[name] || headers[name.toLowerCase()] || '';
        }
        return '';
    }

    function appendBodyValue(container, key, value) {
        if (value === undefined || value === null) {
            return;
        }
        var normalized = typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
        if (container instanceof FormData) {
            container.set(key, normalized);
            return;
        }
        if (container instanceof URLSearchParams) {
            container.set(key, normalized);
        }
    }

    function cloneBody(body) {
        if (body == null) {
            return null;
        }
        if (body instanceof FormData) {
            var formData = new FormData();
            body.forEach(function(value, key) {
                formData.append(key, value);
            });
            return formData;
        }
        if (body instanceof URLSearchParams) {
            return new URLSearchParams(body.toString());
        }
        if (typeof Blob !== 'undefined' && body instanceof Blob) {
            return body.slice(0, body.size, body.type);
        }
        if (typeof ArrayBuffer !== 'undefined' && body instanceof ArrayBuffer) {
            return body.slice(0);
        }
        if (typeof body === 'string') {
            return body;
        }
        if (typeof body === 'object') {
            try {
                return JSON.parse(JSON.stringify(body));
            } catch (_error) {
                return Object.assign({}, body);
            }
        }
        return body;
    }

    function mergeBody(body, extraParams) {
        var extras = extraParams || {};
        var extraKeys = Object.keys(extras);
        var clonedBody = cloneBody(body);

        if (clonedBody instanceof FormData || clonedBody instanceof URLSearchParams) {
            extraKeys.forEach(function(key) {
                appendBodyValue(clonedBody, key, extras[key]);
            });
            return clonedBody;
        }

        if (typeof clonedBody === 'string') {
            try {
                var parsed = JSON.parse(clonedBody);
                return JSON.stringify(Object.assign({}, parsed, extras));
            } catch (_error) {
                var params = new URLSearchParams(clonedBody);
                extraKeys.forEach(function(key) {
                    appendBodyValue(params, key, extras[key]);
                });
                return params.toString();
            }
        }

        if (clonedBody && typeof clonedBody === 'object') {
            return JSON.stringify(Object.assign({}, clonedBody, extras));
        }

        if (extraKeys.length > 0) {
            return JSON.stringify(extras);
        }

        return clonedBody;
    }

    function buildRetryInit(init, extraParams, options) {
        var originalInit = init || {};
        var retryOptions = options || {};
        var headers = new Headers(originalInit.headers || {});
        var body = mergeBody(originalInit.body, extraParams);
        var nextInit = Object.assign({}, originalInit, {
            headers: headers,
            credentials: originalInit.credentials || 'same-origin'
        });
        var originalAccept = getHeaderValue(originalInit.headers, 'Accept');

        if (retryOptions.preserveAccept === true && originalAccept) {
            headers.set('Accept', originalAccept);
        } else {
            headers.set('Accept', 'application/json');
        }
        headers.set('X-Requested-With', 'XMLHttpRequest');

        if (CSRF_TOKEN && !headers.has('X-CSRF-TOKEN')) {
            headers.set('X-CSRF-TOKEN', CSRF_TOKEN);
        }

        if (body instanceof FormData) {
            headers.delete('Content-Type');
            nextInit.body = body;
            return nextInit;
        }

        if (body instanceof URLSearchParams) {
            headers.set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            nextInit.body = body.toString();
            return nextInit;
        }

        if (body === null || body === undefined) {
            delete nextInit.body;
            return nextInit;
        }

        if (typeof body === 'string') {
            if (body.trim().startsWith('{') || body.trim().startsWith('[')) {
                headers.set('Content-Type', 'application/json');
            } else if (!headers.has('Content-Type')) {
                headers.set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            }
            nextInit.body = body;
            return nextInit;
        }

        nextInit.body = body;
        return nextInit;
    }

    function buildFormRequestInit(form, extraParams) {
        var formData = new FormData(form);
        Object.keys(extraParams || {}).forEach(function(key) {
            appendBodyValue(formData, key, extraParams[key]);
        });

        var headers = new Headers({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        });

        if (CSRF_TOKEN && !formData.has('_token')) {
            headers.set('X-CSRF-TOKEN', CSRF_TOKEN);
        }

        return {
            method: (form.getAttribute('method') || 'POST').toUpperCase(),
            headers: headers,
            body: formData,
            credentials: 'same-origin'
        };
    }

    function quotaLabel(quotaKey) {
        switch (String(quotaKey || '')) {
            case 'optimize_full':
                return 'AI 岗位定向优化';
            case 'optimize_section':
                return '分段优化';
            case 'keywords_extract':
                return '关键词提取';
            case 'import_document':
                return 'AI 导入简历';
            case 'interview_sessions':
                return 'AI 面试';
            case 'interview_evaluation':
                return '面试评估';
            case 'job_match':
                return '岗位匹配';
            default:
                return '当前功能';
        }
    }

    function creditUsageText(credit) {
        if (credit && typeof credit.usage_text === 'string' && credit.usage_text.trim() !== '') {
            return credit.usage_text.trim();
        }
        if (credit && credit.is_universal) {
            return '可用于所有 AI 功能';
        }

        return '仅可用于' + quotaLabel(credit && credit.quota_key ? credit.quota_key : '') + '';
    }

    function creditDisplayName(credit) {
        if (!credit || typeof credit !== 'object') {
            return '次卡';
        }

        if (typeof credit.name === 'string' && credit.name.trim() !== '') {
            return credit.name.trim();
        }

        return credit.is_universal ? '通用次卡' : '专用次卡';
    }

    function creditRecommendationText(quotaKey, credits) {
        var availableCredits = Array.isArray(credits) ? credits : [];
        if (availableCredits.length === 0) {
            return '';
        }

        var recommended = availableCredits[0] || null;
        if (!recommended) {
            return '';
        }

        var recommendedName = creditDisplayName(recommended);
        var expiryText = recommended.expires_at ? ('，到期日 ' + recommended.expires_at) : '';

        if (String(quotaKey || '') === 'job_match') {
            return '本次岗位匹配建议优先使用「' + recommendedName + '」' + expiryText + '。确认后将扣减 1 次，并继续当前分析。';
        }

        if (String(quotaKey || '') === 'interview_sessions') {
            return '本次 AI 面试建议优先使用「' + recommendedName + '」' + expiryText + '。确认后将扣减 1 次，并立即开始当前面试。';
        }

        if (String(quotaKey || '') === 'interview_evaluation') {
            return '本次面试评估建议优先使用「' + recommendedName + '」' + expiryText + '。确认后将扣减 1 次，并继续提交当前回答。';
        }

        return '建议优先使用「' + recommendedName + '」' + expiryText + '。确认后将扣减 1 次并继续当前操作。';
    }

    function creditPriorityReason(credit, quotaKey) {
        if (!credit || typeof credit !== 'object') {
            return '';
        }

        var reasons = [];
        if (!credit.is_universal && credit.quota_key && String(credit.quota_key) === String(quotaKey || '')) {
            reasons.push('匹配当前功能的专用次卡');
        } else if (credit.is_universal) {
            reasons.push('通用次卡');
        }

        if (credit.expires_at) {
            reasons.push('临近到期优先使用');
        } else {
            reasons.push('永久有效');
        }

        return reasons.join('，');
    }

    function creditPriorityRulesHtml(quotaKey) {
        var rules = '';
        if (String(quotaKey || '') === 'job_match') {
            rules = '<div>1. 岗位匹配专用次卡优先</div><div>2. 同类型次卡按先到期先使用</div><div>3. 通用次卡最后使用</div>';
        } else if (String(quotaKey || '') === 'interview_sessions') {
            rules = '<div>1. AI 面试专用次卡优先</div><div>2. 同类型次卡按先到期先使用</div><div>3. 通用次卡最后使用</div>';
        } else if (String(quotaKey || '') === 'interview_evaluation') {
            rules = '<div>1. 面试评估专用次卡优先</div><div>2. 同类型次卡按先到期先使用</div><div>3. 通用次卡最后使用</div>';
        }
        if (!rules) { return ''; }
        return '<div class="quota-info-block quota-info-block--gray"><div class="quota-info-block__title">本次推荐规则</div>' + rules + '</div>';
    }

    function creditConfirmActionText(quotaKey) {
        if (String(quotaKey || '') === 'job_match') {
            return '确认后将消耗 1 次所选次卡额度，并自动继续当前分析。';
        }

        if (String(quotaKey || '') === 'interview_sessions') {
            return '确认后将消耗 1 次所选次卡额度，并自动创建本场 AI 面试。';
        }

        if (String(quotaKey || '') === 'interview_evaluation') {
            return '确认后将消耗 1 次所选次卡额度，并自动继续提交当前回答与评估。';
        }

        return '确认后将消耗 1 次所选次卡额度，并自动继续当前操作。';
    }

    function creditConfirmButtonText(quotaKey) {
        if (String(quotaKey || '') === 'job_match') {
            return '确认并继续分析';
        }

        if (String(quotaKey || '') === 'interview_sessions') {
            return '确认并开始面试';
        }

        if (String(quotaKey || '') === 'interview_evaluation') {
            return '确认并继续评估';
        }

        return '使用次卡';
    }

    function lockBodyScroll() {
        if (!document.body) {
            return;
        }
        if (document.body.dataset.quotaModalLocked === '1') {
            return;
        }
        document.body.dataset.quotaModalLocked = '1';
        document.body.dataset.quotaModalOverflow = document.body.style.overflow || '';
        document.body.style.overflow = 'hidden';
    }

    function unlockBodyScroll() {
        if (!document.body) {
            return;
        }
        if (document.getElementById('quota-credit-modal') || document.getElementById('quota-exceeded-modal')) {
            return;
        }
        if (document.body.dataset.quotaModalLocked === '1') {
            document.body.style.overflow = document.body.dataset.quotaModalOverflow || '';
            delete document.body.dataset.quotaModalLocked;
            delete document.body.dataset.quotaModalOverflow;
        }
    }

    function showCreditConfirmModal(data, retryFn, cancelFn) {
        var confirmed = false;
        var existing = document.getElementById('quota-credit-modal');
        if (existing) {
            existing.remove();
        }

        var creditCount = Array.isArray(data.credits) ? data.credits.length : 0;
        var featureLabel = quotaLabel(data.quota_key);
        var recommendationText = creditRecommendationText(data.quota_key, data.credits);
        var confirmActionText = creditConfirmActionText(data.quota_key);
        var confirmButtonText = creditConfirmButtonText(data.quota_key);
        var priorityRulesHtml = creditPriorityRulesHtml(data.quota_key);
        var creditsHint = creditCount > 0
            ? '<p class="text-primary small mb-3"><i class="ti ti-check me-1"></i>已检测到 ' + creditCount + ' 张可用次卡，选择后即可继续当前操作。</p>'
            : '<p class="text-warning small mb-3"><i class="ti ti-alert-circle me-1"></i>当前没有可直接用于该功能的次卡，请确认你持有的是对应功能次卡或通用次卡。</p>';
        var recommendationHtml = recommendationText
            ? '<div class="quota-info-block quota-info-block--yellow"><i class="ti ti-bulb me-1"></i>' + escapeHtml(recommendationText) + '</div>'
            : '';
        var creditsHtml = '';
        if (data.credits && data.credits.length > 0) {
            creditsHtml = data.credits.map(function(c, index) {
                var scope = c.is_universal ? '通用' : '专用';
                var expiry = c.expires_at ? '（到期 ' + c.expires_at + '）' : '（永久）';
                var title = c.name ? (c.name + ' · ') : '';
                var labelText = escapeHtml(title + scope + '次卡 · 剩余 ' + c.remaining + ' 次' + expiry);
                var usageText = escapeHtml(creditUsageText(c));
                var priorityReason = escapeHtml(creditPriorityReason(c, data.quota_key));
                var recommendedBadge = index === 0
                    ? '<span class="quota-recommend-badge">推荐</span>'
                    : '';
                return '<div class="form-check mb-2">' +
                    '<input type="radio" name="credit_id" class="form-check-input" value="' + c.id + '" id="credit-' + c.id + '"' + (index === 0 ? ' checked' : '') + '>' +
                    '<label class="form-check-label d-block" for="credit-' + c.id + '">' +
                    '<span class="d-block">' + labelText + recommendedBadge + '</span>' +
                    '<span class="d-block text-secondary small">' + usageText + '</span>' +
                    (priorityReason ? '<span class="d-block small quota-priority-reason">推荐原因：' + priorityReason + '</span>' : '') +
                    '</label></div>';
            }).join('');
        }

        if (creditsHtml === '') {
            creditsHtml = '<div class="alert alert-warning mb-0">未查询到可用次卡，请刷新页面后重试或直接购买次卡。</div>';
        }

        var modal = document.createElement('div');
        modal.id = 'quota-credit-modal';
        modal.className = 'quota-modal fade show';
        modal.setAttribute('role', 'dialog');
        modal.innerHTML =
            '<div class="quota-modal-backdrop fade show"></div>' +
            '<div class="quota-modal-card quota-modal-card--md">' +
                '<div class="quota-modal-header">' +
                    '<h5 class="quota-modal-title"><i class="ti ti-ticket text-primary"></i><span>可使用次卡继续</span></h5>' +
                    '<button type="button" class="btn-close" data-dismiss="modal"></button>' +
                '</div>' +
                '<div class="quota-modal-body" style="padding-bottom:12px;">' +
                    '<p style="margin:0 0 8px 0;">' + featureLabel + '本月免费次数已用完（已用 ' + (data.monthly_used ?? 0) + '/' + (data.monthly_limit ?? 0) + '）。</p>' +
                    creditsHint +
                    recommendationHtml +
                    priorityRulesHtml +
                    '<div class="quota-info-block quota-info-block--blue"><i class="ti ti-ticket me-1"></i>' + escapeHtml(confirmActionText) + '</div>' +
                    '<div id="credit-options" class="quota-credit-options">' + creditsHtml + '</div>' +
                '</div>' +
                '<div class="quota-modal-footer">' +
                    '<a href="' + (data.upgrade_url || UPGRADE_URL) + '" class="btn btn-outline-primary"><i class="ti ti-arrow-up me-1"></i>升级套餐</a>' +
                    '<div class="quota-modal-actions">' +
                        '<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">取消</button>' +
                        '<button type="button" class="btn btn-primary" id="credit-confirm-btn"><i class="ti ti-check me-1"></i>' + escapeHtml(confirmButtonText) + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);
        document.dispatchEvent(new CustomEvent('quota:modal-open', { bubbles: true }));

        function closeModal(triggerCancel) {
            document.removeEventListener('keydown', handleEscape);
            if (modal.parentNode) modal.parentNode.removeChild(modal);
            if (triggerCancel && typeof cancelFn === 'function') cancelFn({ cancelled: true });
        }

        var backdrop = modal.querySelector('.quota-modal-backdrop');
        if (backdrop) backdrop.addEventListener('click', function() {
            closeModal(true);
        });

        function handleEscape(event) {
            if (event.key === 'Escape') {
                closeModal(true);
            }
        }

        modal.querySelector('#credit-confirm-btn').addEventListener('click', function() {
            var selectedCredit = modal.querySelector('input[name="credit_id"]:checked');
            if (!selectedCredit) {
                notify('请选择一张次卡', 'warning');
                return;
            }
            var selectedCreditId = parseInt(selectedCredit.value, 10);
            var selectedCreditData = Array.isArray(data.credits)
                ? data.credits.find(function(item) { return parseInt(item.id, 10) === selectedCreditId; }) || null
                : null;
            var selectedCreditName = selectedCreditData
                ? (selectedCreditData.name || (selectedCreditData.is_universal ? '通用次卡' : '专用次卡'))
                : '已选次卡';

            confirmed = true;
            closeModal(false);
            notify('正在使用「' + selectedCreditName + '」继续' + featureLabel + '...', 'info');
            document.dispatchEvent(new CustomEvent('quota:credit-selected', {
                detail: {
                    quotaKey: data.quota_key || '',
                    quotaLabel: featureLabel,
                    creditId: selectedCreditId,
                    creditName: selectedCreditName,
                    credit: selectedCreditData,
                }
            }));

            Promise.resolve(retryFn({
                use_credit: true,
                credit_id: selectedCreditId
            })).then(function(result) {
                if (isSuccessfulRetryResult(result)) {
                    notify('已使用「' + selectedCreditName + '」继续' + featureLabel + '，本次将扣除 1 次。', 'success');
                }
            }).catch(function() {
                // Let the original caller show the actual failure reason.
            });
        });
    }

    function showQuotaExceededModal(data) {
        var existing = document.getElementById('quota-exceeded-modal');
        if (existing) {
            existing.remove();
        }

        var featureLabel = quotaLabel(data.quota_key);
        var used = data.monthly_used ?? 0;
        var limit = data.monthly_limit ?? 0;
        var usagePercent = limit > 0 ? Math.round((used / limit) * 100) : 100;

        var modal = document.createElement('div');
        modal.id = 'quota-exceeded-modal';
        modal.className = 'quota-modal fade show';
        modal.setAttribute('role', 'dialog');
        modal.innerHTML =
            '<div class="quota-modal-backdrop fade show"></div>' +
            '<div class="quota-modal-card quota-modal-card--sm">' +
                '<div class="quota-modal-header">' +
                    '<h5 class="quota-modal-title"><i class="ti ti-alert-circle text-danger"></i><span>次数已用完</span></h5>' +
                    '<button type="button" class="btn-close" data-dismiss="modal"></button>' +
                '</div>' +
                '<div class="quota-modal-body">' +
                    '<p style="margin:0 0 12px 0;">' + escapeHtml(featureLabel) + ' 本月免费次数已用完。</p>' +
                    '<div class="quota-usage-bar" style="margin-bottom:12px;">' +
                        '<div class="d-flex justify-content-between small text-secondary mb-1">' +
                            '<span>本月使用情况</span>' +
                            '<span>' + used + ' / ' + limit + ' 次</span>' +
                        '</div>' +
                        '<div class="progress" style="height:6px;">' +
                            '<div class="progress-bar bg-danger" style="width:' + usagePercent + '%"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="quota-info-block quota-info-block--blue" style="margin-bottom:8px;">' +
                        '<i class="ti ti-bulb me-1"></i>升级套餐可立即恢复次数，并解锁更多高级功能。' +
                    '</div>' +
                    '<p style="margin:0;color:#64748b;font-size:13px;">或购买次卡按需使用，灵活不受限。</p>' +
                '</div>' +
                '<div class="quota-modal-footer quota-modal-footer--end">' +
                    '<a href="' + (data.credit_packs_url || CREDIT_PACKS_URL) + '" class="btn btn-outline-primary"><i class="ti ti-ticket me-1"></i>购买次卡</a>' +
                    '<a href="' + (data.upgrade_url || UPGRADE_URL) + '" class="btn btn-primary"><i class="ti ti-arrow-up me-1"></i>升级套餐</a>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);
        lockBodyScroll();

        function closeModal() {
            modal.remove();
            unlockBodyScroll();
        }

        modal.querySelector('[data-dismiss="modal"]').addEventListener('click', closeModal);
        var backdrop = modal.querySelector('.quota-modal-backdrop');
        if (backdrop) backdrop.addEventListener('click', closeModal);
    }

    function parseQuotaJson(response) {
        if (!response || response.headers.get('X-Quota-Status') !== 'exceeded') {
            return Promise.resolve(null);
        }

        return response.clone().json().then(function(data) {
            return data && typeof data === 'object' ? data : null;
        }).catch(function() {
            return null;
        });
    }

    function createQuotaHandledError(response, data, extra) {
        return Object.assign({
            quotaHandled: true,
            response: response,
            data: data || null
        }, extra || {});
    }

    function isSuccessfulRetryResult(result) {
        if (!result) {
            return false;
        }

        if (typeof Response !== 'undefined' && result instanceof Response) {
            return result.ok;
        }

        if (typeof result.ok === 'boolean') {
            return result.ok;
        }

        if (typeof result.status === 'number') {
            return result.status >= 200 && result.status < 400;
        }

        return false;
    }

    function handleQuotaData(data, retryFn, cancelFn) {
        if (!data || typeof data !== 'object') {
            return { handled: false, retryable: false };
        }

        var payload = data.data || data;
        if (data.code === 'QUOTA_EXCEEDED_BUT_CREDIT_AVAILABLE') {
            showCreditConfirmModal(payload, retryFn, cancelFn);
            return { handled: true, retryable: true };
        }

        if (data.code === 'QUOTA_EXCEEDED') {
            showQuotaExceededModal(payload);
            return { handled: true, retryable: false };
        }

        if (data.code === 'INVALID_CREDIT' || data.code === 'CREDIT_KEY_MISMATCH') {
            notify(data.message || '次卡无效，请重新选择后再试', 'error');
            return { handled: true, retryable: false };
        }

        return { handled: false, retryable: false };
    }

    function resolveQuotaResponse(response, retryFn) {
        if (!response || response.headers.get('X-Quota-Status') !== 'exceeded') {
            return Promise.resolve(response);
        }

        return parseQuotaJson(response).then(function(data) {
            if (!data) {
                return response;
            }

            return new Promise(function(resolve, reject) {
                var result = handleQuotaData(data, function(extraParams) {
                    return Promise.resolve(retryFn(extraParams)).then(function(retryResult) {
                        resolve(retryResult);
                        return retryResult;
                    }).catch(function(error) {
                        reject(error);
                        throw error;
                    });
                }, function(extra) {
                    reject(createQuotaHandledError(response, data, extra || { cancelled: true }));
                });

                if (!result.handled) {
                    resolve(response);
                    return;
                }

                if (!result.retryable) {
                    reject(createQuotaHandledError(response, data, { blocked: true }));
                }
            });
        });
    }

    function makeRetryRequest(input, init, options) {
        return function(extraParams) {
            return fetchWithQuotaSupport(input, buildRetryInit(init, extraParams, options));
        };
    }

    function fetchWithQuotaSupport(input, init) {
        var requestInit = init || {};
        if (requestInit.quotaIntercept === false) {
            var plainInit = Object.assign({}, requestInit);
            delete plainInit.quotaIntercept;
            return originalFetch(input, plainInit);
        }

        return originalFetch(input, requestInit).then(function(response) {
            var acceptHeader = getHeaderValue(requestInit.headers, 'Accept');
            var wantsEventStream = typeof acceptHeader === 'string' && acceptHeader.includes('text/event-stream');

            if (wantsEventStream && response.ok && response.body) {
                return response;
            }

            return resolveQuotaResponse(
                response,
                makeRetryRequest(input, requestInit, wantsEventStream ? { preserveAccept: true } : {})
            );
        });
    }

    function extractErrorMessage(payload) {
        if (!payload || typeof payload !== 'object') {
            return '';
        }
        if (typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message.trim();
        }
        if (payload.errors && typeof payload.errors === 'object') {
            var firstKey = Object.keys(payload.errors)[0];
            var firstError = firstKey ? payload.errors[firstKey] : null;
            if (Array.isArray(firstError) && firstError.length > 0) {
                return String(firstError[0] || '').trim();
            }
        }
        return '';
    }

    function submitForm(form, extraParams) {
        if (!form) {
            return Promise.reject(new Error('表单不存在'));
        }

        var action = form.getAttribute('action') || window.location.href;
        var requestInit = buildFormRequestInit(form, extraParams || {});

        return fetchWithQuotaSupport(action, requestInit).then(async function(response) {
            var contentType = getHeaderValue(response.headers, 'Content-Type');
            var isJson = typeof contentType === 'string' && contentType.includes('application/json');

            if (response.redirected && response.url) {
                window.location.href = response.url;
                return { ok: true, redirected: true, url: response.url };
            }

            if (!response.ok) {
                if (isJson) {
                    var errorPayload = await response.json().catch(function() { return null; });
                    return {
                        ok: false,
                        status: response.status,
                        data: errorPayload,
                        message: extractErrorMessage(errorPayload),
                        validationErrors: errorPayload && errorPayload.errors ? errorPayload.errors : null
                    };
                }

                if (response.url) {
                    window.location.href = response.url;
                    return { ok: false, redirected: true, url: response.url };
                }

                return { ok: false, status: response.status, message: '请求失败，请稍后重试' };
            }

            if (isJson) {
                return {
                    ok: true,
                    status: response.status,
                    data: await response.json().catch(function() { return null; })
                };
            }

            if (response.url) {
                window.location.href = response.url;
                return { ok: true, redirected: true, url: response.url };
            }

            return { ok: true, status: response.status };
        });
    }

    function handleQuotaResponse(response, retryFn) {
        if (!response || response.headers.get('X-Quota-Status') !== 'exceeded') {
            return false;
        }

        parseQuotaJson(response).then(function(data) {
            if (!data) {
                return;
            }
            handleQuotaData(data, retryFn, null);
        });

        return true;
    }

    window.quotaInterceptor = {
        fetch: fetchWithQuotaSupport,
        submitForm: submitForm,
        handleResponse: handleQuotaResponse,
        handleManualResponse: resolveQuotaResponse,
        processQuotaPayload: handleQuotaData,
        makeRetryRequest: makeRetryRequest,
        buildRetryInit: buildRetryInit,
        showCreditConfirmModal: showCreditConfirmModal,
        showQuotaExceededModal: showQuotaExceededModal,
    };

    document.addEventListener('submit', function(event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.quotaSubmit !== '1') {
            return;
        }

        event.preventDefault();

        if (form.dataset.quotaSubmitting === '1') {
            return;
        }

        form.dataset.quotaSubmitting = '1';

        var submitBtn = form.querySelector('.submit-btn, button[type="submit"]');
        var originalText = submitBtn ? submitBtn.innerHTML : '';
        var loadingText = form.dataset.quotaLoadingText || '提交中...';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loadingText;
        }

        function restoreFormState() {
            form.dataset.quotaSubmitting = '0';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }

        submitForm(form).then(function(result) {
            if (result && (result.redirected || result.ok)) {
                if (!result.redirected) {
                    restoreFormState();
                    form.dispatchEvent(new CustomEvent('quota:submitted', {
                        bubbles: true,
                        detail: result
                    }));
                }
                return;
            }

            notify((result && result.message) || '请求失败，请稍后重试', 'error');
            restoreFormState();
        }).catch(function(error) {
            if (error && error.quotaHandled) {
                restoreFormState();
                return;
            }

            notify((error && error.message) || '请求失败，请稍后重试', 'error');
            restoreFormState();
        });
    });

    window.fetch = fetchWithQuotaSupport;
})();
