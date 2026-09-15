(function() {
    'use strict';

    function showToast(message, type) {
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, type);
        }
    }

    // 总开关控制
    var masterSwitch = document.getElementById('email_notifications_enabled');
    masterSwitch.addEventListener('change', function() {
        var checks = document.querySelectorAll('.preference-check');
        checks.forEach(function(check) {
            check.disabled = !this.checked;
        }.bind(this));
    });

    // 保存设置
    document.getElementById('preferenceForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var data = {};

        ['email_notifications_enabled', 'notify_resume_completed', 'notify_interview_started',
         'notify_interview_completed', 'notify_job_application', 'notify_deadline_reminder',
         'notify_marketing'].forEach(function(key) {
            data[key] = formData.has(key) ? 1 : 0;
        });

        fetch(document.querySelector('[data-route-user-notification-preferences-update]')?.getAttribute('data-route-user-notification-preferences-update') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('设置已保存', 'success');
            } else {
                showToast(data.message || '保存失败', 'error');
            }
        })
        .catch(function() { showToast('保存失败', 'error'); });
    });

    // 恢复默认
    async function resetPreferences() {
        var confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm('确定要恢复默认设置吗？', { title: '恢复默认', showCancel: true })
            : false;
        if (!confirmed) return;

        fetch(document.querySelector('[data-route-user-notification-preferences-reset]')?.getAttribute('data-route-user-notification-preferences-reset') || '', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message || '重置失败', 'error');
            }
        })
        .catch(function() { showToast('重置失败', 'error'); });
    }

    // 发送测试邮件
    function sendTestEmail() {
        fetch(document.querySelector('[data-route-user-notification-preferences-test]')?.getAttribute('data-route-user-notification-preferences-test') || '', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            showToast(data.message, data.success ? 'success' : 'error');
        })
        .catch(function() { showToast('发送失败', 'error'); });
    }

    // 事件委托
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        switch (btn.dataset.action) {
            case 'reset-preferences':
                resetPreferences();
                break;
            case 'send-test-email':
                sendTestEmail();
                break;
        }
    });
})();
