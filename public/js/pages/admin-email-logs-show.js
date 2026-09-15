(function() {
'use strict';

var container = document.querySelector('[data-route-admin-email-logs-resend-0]');
var resendUrl = container ? container.getAttribute('data-route-admin-email-logs-resend-0') : '';
var deleteUrl = container ? container.getAttribute('data-route-admin-email-logs-destroy-1') : '';
var indexUrl = container ? container.getAttribute('data-route-admin-email-logs-index') : '';

async function resend() {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要重新发送这封邮件吗？', { title: '重发确认', showCancel: true })
        : false;
    if (!confirmed) return;

    fetch(resendUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        window.appNotify(data.message);
        if (data.success) location.reload();
    })
    .catch(e => window.appNotify('操作失败'));
}

async function deleteLog() {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要删除这条记录吗？此操作不可恢复。', { title: '删除确认', showCancel: true })
        : false;
    if (!confirmed) return;

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        window.appNotify(data.message);
        if (data.success) {
            window.location.href = indexUrl;
        }
    })
    .catch(e => window.appNotify('操作失败'));
}

// 事件委托
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var action = btn.dataset.action;
    switch (action) {
        case 'resend':
            resend();
            break;
        case 'delete-log':
            deleteLog();
            break;
    }
});
})();
