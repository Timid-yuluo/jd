(function() {
'use strict';

document.getElementById('check-all')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check:not(:disabled)').forEach(cb => cb.checked = this.checked);
    updateBatchBar();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-check')) {
        updateBatchBar();
    }
});

function getCheckedIds() {
    return Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
}

function updateBatchBar() {
    var bar = document.getElementById('batch-bar');
    var count = document.querySelectorAll('.row-check:checked').length;
    if (!bar) return;
    if (count > 0) {
        bar.classList.remove('d-none');
        var label = bar.querySelector('.batch-count');
        if (label) label.textContent = count;
    } else {
        bar.classList.add('d-none');
    }
    var checkAll = document.getElementById('check-all');
    if (checkAll) {
        var total = document.querySelectorAll('.row-check:not(:disabled)').length;
        checkAll.checked = total > 0 && count === total;
        checkAll.indeterminate = count > 0 && count < total;
    }
}

async function batchAction(actionUrl, actionName) {
    var ids = getCheckedIds();
    if (ids.length === 0) {
        window.appNotify('请先选择用户', 'warning');
        return;
    }
    var confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要' + actionName + '选中的 ' + ids.length + ' 个用户吗？', { title: actionName + '确认', showCancel: true })
        : false;
    if (!confirmed) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;
    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
    form.appendChild(csrf);
    ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}

window.batchDelete = function() {
    batchAction(document.getElementById('batch-form')?.action || '', '删除');
};

window.batchSuspend = function() {
    batchAction(document.querySelector('[data-batch-suspend-url]')?.dataset.batchSuspendUrl || '', '封禁');
};

document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var action = btn.dataset.action;
    switch (action) {
        case 'batch-delete': window.batchDelete(); break;
        case 'batch-suspend': window.batchSuspend(); break;
        default: if (typeof window[action] === 'function') { window[action](btn); } break;
    }
});

document.addEventListener('change', function(e) {
    if (e.target.matches('[data-action]')) {
        var action = e.target.dataset.action;
        if (typeof window[action] === 'function') { window[action](e.target); }
    }
});
})();
