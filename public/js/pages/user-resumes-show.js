const EXPORT_TASK_CREATE_URL = JSON.parse(document.querySelector('[data-json-route-user-resumes-export-tasks-create]')?.getAttribute('data-json-route-user-resumes-export-tasks-create') || 'null');
const EXPORT_TASK_STATUS_URL_TEMPLATE = JSON.parse(document.querySelector('[data-json-route-user-resumes-export-tasks-status-taskid-task-id]')?.getAttribute('data-json-route-user-resumes-export-tasks-status-taskid-task-id') || 'null');
const PRINT_PDF_URL = JSON.parse(document.querySelector('[data-json-route-user-resumes-print-pdf-resume-resume]')?.getAttribute('data-json-route-user-resumes-print-pdf-resume-resume') || 'null');
const EXPORT_POLICY = JSON.parse(document.querySelector('[data-json-export-policy]')?.getAttribute('data-json-export-policy') || 'null');

function showCopySuccess(btn, notify) {
    btn.innerHTML = '<i class="ti ti-check me-1"></i>已复制';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-success');
    var successAlert = document.getElementById('copySuccess');
    if (successAlert) { successAlert.classList.remove('d-none'); setTimeout(function() { successAlert.classList.add('d-none'); }, 2500); }
    if (notify) notify('链接已复制到剪贴板', 'success');
    setTimeout(function() { btn.innerHTML = '<i class="ti ti-copy me-1"></i>复制'; btn.classList.remove('btn-success'); btn.classList.add('btn-primary'); }, 2000);
}

function fallbackCopy(input, btn, notify) {
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        showCopySuccess(btn, notify);
    } catch (e) {
        if (notify) notify('复制失败，请手动选中链接复制', 'error');
    }
}

function copyResumeContent() {
    const el = document.getElementById('resume-raw-content') || document.getElementById('resume-raw-content-secondary');
    const content = el?.textContent?.trim() ?? '';
    const notify = typeof window.appNotify === 'function' ? window.appNotify : (msg) => { alert(msg); };
    if (!content) { notify('没有可复制的内容', 'warning'); return; }
    const btn = document.querySelector('[data-action="copy-resume"]');
    const showCopied = () => { if (btn) { const orig = btn.innerHTML; btn.innerHTML = '<i class="ti ti-check me-2"></i>已复制'; btn.disabled = true; setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2000); } };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(content).then(showCopied).catch(() => {
            fallbackCopyText(content, showCopied, notify);
        });
    } else {
        fallbackCopyText(content, showCopied, notify);
    }
}

function fallbackCopyText(text, onSuccess, notify) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.select();
    ta.setSelectionRange(0, 99999);
    try {
        document.execCommand('copy');
        onSuccess();
    } catch (e) {
        if (notify) notify('复制失败，请手动复制', 'error');
    } finally {
        document.body.removeChild(ta);
    }
}

const wait = (ms) => new Promise(resolve => window.setTimeout(resolve, ms));

async function pollExportTask(taskId, maxRounds = 45, intervalMs = 2000) {
    for (let i = 0; i < maxRounds; i++) {
        const statusUrl = EXPORT_TASK_STATUS_URL_TEMPLATE.replace('__TASK_ID__', encodeURIComponent(taskId));
        const response = await fetch(statusUrl, {
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) {
            throw new Error('查询导出任务状态失败');
        }

        const data = await response.json();
        if (!data?.success || !data?.task) {
            throw new Error(data?.message || '导出任务状态异常');
        }

        const task = data.task;
        if (task.status === 'completed') {
            return task;
        }
        if (task.status === 'failed') {
            throw new Error(task.error_message || '导出任务失败');
        }

        await wait(intervalMs);
    }

    throw new Error('导出耗时较长，请稍后在导出历史中下载');
}

async function exportResumePdf() {
    const notify = typeof window.appNotify === 'function' ? window.appNotify : () => {};
    const btn = document.querySelector('[data-action="export-pdf"]');
    const originalText = btn?.innerHTML;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2 ti-spin me-2"></i>导出中...';
    }

    try {
        const printUrl = new URL(PRINT_PDF_URL, window.location.origin);
        printUrl.searchParams.set('auto_print', '1');
        const openedWindow = window.open(printUrl.toString(), '_blank', 'noopener');
        if (!openedWindow) {
            window.location.href = printUrl.toString();
        }
        notify('已打开打印版页面，请在打印窗口选择"另存为 PDF"。', 'success');
    } catch (err) {
        notify(err?.message || 'PDF 导出失败，请稍后重试', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText || '<i class="ti ti-file-type-pdf me-2"></i>导出PDF';
        }
    }
}

// 事件委托
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    switch (btn.dataset.action) {
        case 'copy-resume':
            copyResumeContent();
            break;
        case 'export-pdf':
            exportResumePdf();
            break;
        case 'suggest-module-order':
            suggestModuleOrder();
            break;
        case 'translate-resume':
            translateResume(btn.dataset.direction || 'zh_to_en');
            break;
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 分享链接复制
    var copyBtn = document.getElementById('copyShareLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var input = document.getElementById('shareLinkInput');
            if (!input) return;
            var link = input.value;
            var notify = typeof window.appNotify === 'function' ? window.appNotify : function(msg, type) { alert(msg); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(function() {
                    showCopySuccess(copyBtn, notify);
                }).catch(function() {
                    fallbackCopy(input, copyBtn, notify);
                });
            } else {
                fallbackCopy(input, copyBtn, notify);
            }
        });
    }

    if (EXPORT_POLICY && EXPORT_POLICY.can_export_docx === false) {
        var docxLinks = document.querySelectorAll('a[href*="/export-docx"]');
        docxLinks.forEach(function(link) {
            link.classList.remove('btn-outline-success', 'btn-ghost-success');
            link.classList.add('btn-outline-secondary');
            link.setAttribute('href', 'javascript:void(0)');
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('title', '免费版暂不支持 DOCX 导出，请升级基础版或专业版');
        });
    }

    var params = new URLSearchParams(window.location.search);
    if (params.get('export') === 'pdf') {
        exportResumePdf();
    }

    // 分享二维码生成
    var qrContainer = document.getElementById('shareQrCode');
    if (qrContainer) {
        var shareUrl = document.getElementById('shareLinkInput');
        if (shareUrl && shareUrl.value) {
            generateQRCode(qrContainer, shareUrl.value, 140);
        }
    }
});

// AI 模块排序建议
async function suggestModuleOrder() {
    var notify = typeof window.appNotify === 'function' ? window.appNotify : function(msg) { alert(msg); };
    var resumeId = getResumeId();
    if (!resumeId) { notify('无法获取简历ID', 'error'); return; }

    try {
        notify('正在生成排序建议...', 'info');
        var resp = await fetch('/user/resumes/' + resumeId + '/suggest-module-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        });
        var data = await resp.json();
        if (!data?.success || !data?.payload?.suggested_order?.length) {
            notify(data?.message || '排序建议生成失败', 'error');
            return;
        }
        var items = data.payload.suggested_order;
        var summary = data.payload.summary || '';
        var html = '<div class="p-3"><h6 class="mb-2"><i class="ti ti-arrows-sort me-1"></i>AI 排序建议</h6>';
        if (summary) html += '<p class="text-secondary small mb-3">' + summary + '</p>';
        html += '<ol class="mb-0">';
        items.forEach(function(item) {
            html += '<li class="mb-1"><strong>' + (item.type || '') + '</strong>';
            if (item.reason) html += '<span class="text-secondary small ms-2">— ' + item.reason + '</span>';
            html += '</li>';
        });
        html += '</ol></div>';
        showModal('AI 排序建议', html);
    } catch (err) {
        notify(err?.message || '排序建议请求失败', 'error');
    }
}

// 一键翻译简历
async function translateResume(direction) {
    var notify = typeof window.appNotify === 'function' ? window.appNotify : function(msg) { alert(msg); };
    var resumeId = getResumeId();
    if (!resumeId) { notify('无法获取简历ID', 'error'); return; }

    var dirLabel = direction === 'zh_to_en' ? '英文' : '中文';
    if (!confirm('确定要将简历翻译为' + dirLabel + '吗？翻译结果将作为新简历保存。')) return;

    try {
        notify('正在翻译简历，请稍候...', 'info');
        var resp = await fetch('/user/resumes/' + resumeId + '/translate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ direction: direction }),
        });
        var data = await resp.json();
        if (!data?.success || !data?.payload?.translated_modules?.length) {
            notify(data?.message || '翻译失败', 'error');
            return;
        }
        notify('翻译完成！共翻译 ' + data.payload.translated_modules.length + ' 个模块', 'success');
    } catch (err) {
        notify(err?.message || '翻译请求失败', 'error');
    }
}

function getResumeId() {
    var m = window.location.pathname.match(/\/user\/resumes\/(\d+)/);
    return m ? m[1] : null;
}

function showModal(title, bodyHtml) {
    var existing = document.getElementById('dynamicModal');
    if (existing) existing.remove();
    var modal = document.createElement('div');
    modal.id = 'dynamicModal';
    modal.className = 'modal modal-blur fade';
    modal.setAttribute('tabindex', '-1');
    modal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title">' + title + '</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body">' + bodyHtml + '</div></div></div>';
    document.body.appendChild(modal);
    new bootstrap.Modal(modal).show();
    modal.addEventListener('hidden.bs.modal', function() { modal.remove(); });
}

// 简易 QR 码生成（使用 Google Chart API 作为后备方案，优先使用 Canvas）
function generateQRCode(container, text, size) {
    size = size || 140;
    var img = document.createElement('img');
    img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encodeURIComponent(text);
    img.alt = '简历分享二维码';
    img.style.width = size + 'px';
    img.style.height = size + 'px';
    img.loading = 'lazy';
    container.appendChild(img);
}
