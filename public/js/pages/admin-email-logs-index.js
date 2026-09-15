(function() {
'use strict';

async function resendLog(id) {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要重发这封邮件吗？', { title: '重发确认', showCancel: true })
        : false;
    if (!confirmed) return;

    fetch(`/admin/email-logs/${id}/resend`, {
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

async function resendFailed() {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要批量重发失败的邮件吗？', { title: '批量重发确认', showCancel: true })
        : false;
    if (!confirmed) return;

    fetch('/admin/email-logs/resend-failed', {
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

async function deleteLog(id) {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要删除这条记录吗？', { title: '删除确认', showCancel: true })
        : false;
    if (!confirmed) return;

    fetch(`/admin/email-logs/${id}`, {
        method: 'DELETE',
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

async function clearOldLogs() {
    const days = typeof window.appPrompt === 'function'
        ? await window.appPrompt('请输入要保留最近多少天的日志（默认30天）：', '30', {
            title: '清理日志',
            inputType: 'number',
            placeholder: '请输入 1-3650 的整数',
            maxLength: 4,
            pattern: '^[1-9]\\d*$',
            min: 1,
            max: 3650,
            step: 1,
            required: true,
            requiredMessage: '请输入保留天数',
            patternMessage: '请输入正整数天数',
            invalidMessage: '请输入有效的整数天数',
            minMessage: '保留天数不能小于 1',
            maxMessage: '保留天数不能大于 3650'
        })
        : null;
    if (!days) return;
    const daysNumber = Number(days);
    if (!Number.isInteger(daysNumber) || daysNumber < 1 || daysNumber > 3650) {
        window.appNotify('保留天数不合法，请输入 1-3650 的整数', 'warning');
        return;
    }

    fetch('/admin/email-logs/clear', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ days: daysNumber })
    })
    .then(r => r.json())
    .then(data => {
        window.appNotify(data.message);
        if (data.success) location.reload();
    })
    .catch(e => window.appNotify('操作失败'));
}

function loadStatistics() {
    fetch('/admin/email-logs/statistics', {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderChart(data.data.daily);
            new bootstrap.Modal(document.getElementById('statisticsModal')).show();
        }
    })
    .catch(e => window.appNotify('加载统计失败'));
}

function renderChart(dailyData) {
    var el = document.getElementById('dailyChart');
    new ApexCharts(el, {
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        series: [
            { name: '发送总数', data: dailyData.map(function(d) { return d.count; }) },
            { name: '成功数', data: dailyData.map(function(d) { return d.sent_count; }) }
        ],
        xaxis: { categories: dailyData.map(function(d) { return d.date; }) },
        stroke: { width: 2, curve: 'smooth' },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        colors: ['rgb(59, 130, 246)', 'rgb(16, 185, 129)'],
        dataLabels: { enabled: false },
        yaxis: { min: 0 }
    }).render();
}

// 事件委托
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    var action = btn.dataset.action;
    switch (action) {
        case 'resend-log':
            resendLog(btn.dataset.logId || btn.closest('tr')?.dataset.logId);
            break;
        case 'resend-failed':
            resendFailed();
            break;
        case 'delete-log':
            deleteLog(btn.dataset.logId || btn.closest('tr')?.dataset.logId);
            break;
        case 'clear-old-logs':
            clearOldLogs();
            break;
        case 'load-statistics':
            loadStatistics();
            break;
    }
});
})();
