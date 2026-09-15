(function() {
    'use strict';

    function requestJson(url, options) {
        return fetch(url, options).then(function(response) {
            return response.json().catch(function() { return {}; }).then(function(data) {
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || '请求失败');
                }
                return data;
            });
        });
    }

    function csrfHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        };
    }

    function updateStatus() {
        requestJson(document.querySelector('[data-url-update-status]')?.getAttribute('data-url-update-status') || '', {
            method: 'PUT',
            headers: csrfHeaders(),
            body: JSON.stringify({
                status: document.getElementById('statusSelect').value,
                priority: document.getElementById('prioritySelect').value
            })
        })
        .then(function(data) { window.appNotify(data.message, 'success'); })
        .catch(function(error) { window.appNotify(error.message || '更新失败', 'error'); });
    }

    function saveNote() {
        requestJson(document.querySelector('[data-url-update-note]')?.getAttribute('data-url-update-note') || '', {
            method: 'PUT',
            headers: csrfHeaders(),
            body: JSON.stringify({ admin_note: document.getElementById('adminNote').value })
        })
        .then(function(data) { window.appNotify(data.message, 'success'); })
        .catch(function(error) { window.appNotify(error.message || '保存失败', 'error'); });
    }

    function updateAdoption() {
        requestJson(document.querySelector('[data-url-update-adoption]')?.getAttribute('data-url-update-adoption') || '', {
            method: 'PUT',
            headers: csrfHeaders(),
            body: JSON.stringify({
                adoption_status: document.getElementById('adoptionStatusSelect').value,
                adoption_note: document.getElementById('adoptionNote').value
            })
        })
        .then(function(data) {
            window.appNotify(data.message, 'success');
            setTimeout(function() { window.location.reload(); }, 500);
        })
        .catch(function(error) { window.appNotify(error.message || '保存失败', 'error'); });
    }

    function grantReward() {
        var credits = Number(document.getElementById('rewardCredits')?.value || 0);
        if (!credits || credits < 1) {
            window.appNotify('请输入正确的奖励次数', 'error');
            return;
        }

        if (!window.confirm('确认采纳该反馈并赠送次卡吗？奖励发放后不可重复操作。')) {
            return;
        }

        var btn = document.getElementById('rewardBtn');
        var originalHtml = btn.innerHTML;
        var validityValue = document.getElementById('rewardValidityDays')?.value || '';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>发放中...';

        requestJson(document.querySelector('[data-url-grant-reward]')?.getAttribute('data-url-grant-reward') || '', {
            method: 'POST',
            headers: csrfHeaders(),
            body: JSON.stringify({
                quota_key: document.getElementById('rewardQuotaKey')?.value || null,
                credits: credits,
                validity_days: validityValue === '' ? null : Number(validityValue),
                reason: document.getElementById('rewardReason')?.value || '',
                adoption_note: document.getElementById('adoptionNote')?.value || ''
            })
        })
        .then(function(data) {
            window.appNotify(data.message, 'success');
            setTimeout(function() { window.location.reload(); }, 600);
        })
        .catch(function(error) { window.appNotify(error.message || '赠送失败', 'error'); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function submitReply() {
        var content = document.getElementById('replyContent').value.trim();
        if (!content) {
            window.appNotify('请输入回复内容', 'error');
            return;
        }

        var btn = document.getElementById('replyBtn');
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>发送中...';

        requestJson(document.querySelector('[data-url-reply]')?.getAttribute('data-url-reply') || '', {
            method: 'POST',
            headers: csrfHeaders(),
            body: JSON.stringify({
                content: content,
                notify_user: document.getElementById('notifyUser').checked
            })
        })
        .then(function(data) {
            window.appNotify(data.message, 'success');
            document.getElementById('replyContent').value = '';

            var container = document.getElementById('repliesContainer');
            var emptyMsg = container.querySelector('.text-center');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            var contentDiv = document.createElement('div');
            contentDiv.textContent = content;
            contentDiv.style.whiteSpace = 'pre-wrap';
            contentDiv.className = 'p-3 rounded bg-primary-lt';

            var wrapper = document.createElement('div');
            wrapper.className = 'd-flex mb-3';

            var avatar = document.createElement('div');
            avatar.className = 'avatar avatar-sm me-3';
            avatar.style.cssText = 'background:#206bc4;color:#fff;font-weight:600;font-size:.75rem;flex-shrink:0;';
            avatar.textContent = (document.querySelector('[data-auth-name]')?.dataset.authName?.charAt(0)?.toUpperCase() || '');

            var body = document.createElement('div');
            body.className = 'me-auto';
            body.style.maxWidth = '75%';

            var header = document.createElement('div');
            header.className = 'd-flex align-items-center gap-2 mb-1';
            header.innerHTML = '<span class="fw-medium small">' + (document.querySelector('[data-auth-name]')?.getAttribute('data-auth-name') || '') + '</span><span class="badge bg-blue-lt">管理员</span><span class="text-secondary small">刚刚</span>';

            body.appendChild(header);
            body.appendChild(contentDiv);
            wrapper.appendChild(avatar);
            wrapper.appendChild(body);
            container.appendChild(wrapper);
            document.getElementById('statusSelect').value = 'replied';
        })
        .catch(function(error) { window.appNotify(error.message || '回复失败', 'error'); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function deleteFeedback() {
        requestJson(document.querySelector('[data-url-delete]')?.getAttribute('data-url-delete') || '', {
            method: 'DELETE',
            headers: csrfHeaders(),
            body: JSON.stringify({ confirm: true })
        })
        .then(function(data) {
            window.appNotify(data.message, 'success');
            setTimeout(function() {
                window.location.href = document.querySelector('[data-url-feedbacks-index]')?.getAttribute('data-url-feedbacks-index') || '';
            }, 800);
        })
        .catch(function(error) { window.appNotify(error.message || '删除失败', 'error'); });
    }

    // ===== 事件委托 =====
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;

        switch (btn.dataset.action) {
            case 'submit-reply':
                submitReply();
                break;
            case 'save-note':
                saveNote();
                break;
            case 'update-adoption':
                updateAdoption();
                break;
            case 'grant-reward':
                grantReward();
                break;
            case 'delete-feedback':
                var confirmMsg = btn.getAttribute('data-app-confirm');
                if (confirmMsg && !confirm(confirmMsg)) return;
                deleteFeedback();
                break;
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('[data-action="update-status"]')) {
            updateStatus();
        }
    });
})();
