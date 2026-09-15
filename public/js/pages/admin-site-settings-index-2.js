function getAppConfirm() {
    return typeof window['appConfirm'] === 'function' ? window['appConfirm'] : null;
}

function getAppNotify() {
    return typeof window['appNotify'] === 'function' ? window['appNotify'] : null;
}

function bindColorPickerSync() {
    const colorPicker = document.getElementById('color-picker');
    const colorInput = document.getElementById('color-input');

    if (!colorPicker || !colorInput) {
        return;
    }

    colorPicker.addEventListener('input', function () {
        colorInput.value = this.value;
    });

    colorInput.addEventListener('input', function () {
        colorPicker.value = this.value;
    });
}

async function clearCache(triggerButton) {
    const appConfirm = getAppConfirm();
    const confirmed = appConfirm
        ? await appConfirm('确定要清除系统缓存吗？\n\n这将清除配置缓存、路由缓存、视图缓存等。', { title: '清缓存确认', showCancel: true })
        : false;
    if (!confirmed) {
        return;
    }

    const btn = triggerButton || null;
    if (!btn) {
        return;
    }
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>清除中...';

    fetch(document.querySelector('[data-route-admin-site-settings-clear-cache]')?.getAttribute('data-route-admin-site-settings-clear-cache') || '', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        const appNotify = getAppNotify();
        if (appNotify) {
            appNotify(data.success ? '缓存已清除成功！' : '清除失败：' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        const appNotify = getAppNotify();
        if (appNotify) {
            appNotify('请求失败：' + err.message);
        }
    });
}

function exportSettings() {
    window.open(document.querySelector('[data-route-admin-site-settings-export]')?.getAttribute('data-route-admin-site-settings-export') || '', '_blank');
}

async function sanitizeHeadSnippets(triggerButton) {
    const confirmMessage = '确定要清理历史头部异常代码吗？\n\n将检查并清理 analytics_google / analytics_baidu / analytics_clarity / custom_head_code 中的无效片段。';
    const appConfirm = getAppConfirm();
    const ok = appConfirm
        ? await appConfirm(confirmMessage, { title: '清理确认' })
        : false;
    if (!ok) return;

    const btn = triggerButton || null;
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>清理中...';
    }

    fetch(document.querySelector('[data-route-admin-site-settings-sanitize-head-snippets]')?.getAttribute('data-route-admin-site-settings-sanitize-head-snippets') || '', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        const notify = getAppNotify()
            ? getAppNotify()
            : () => {};
        if (data.success) {
            const suffix = Array.isArray(data.updated_keys) && data.updated_keys.length
                ? '（已更新：' + data.updated_keys.join('、') + '）'
                : '';
            notify((data.message || '清理完成') + suffix, 'success');
            setTimeout(() => window.location.reload(), 900);
        } else {
            notify(data.message || '清理失败', 'error');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
        const notify = getAppNotify()
            ? getAppNotify()
            : () => {};
        notify('请求失败：' + err.message, 'error');
    });
}

function bindOcrProviderSwitcher() {
    const providerSelect = document.querySelector('select[name="ocr_provider"]');

    if (!providerSelect) {
        return;
    }

    providerSelect.addEventListener('change', function () {
        const provider = this.value;

        document.querySelectorAll('.ocr-config-section').forEach(function (el) {
            el.classList.add('d-none');
        });

        if (!provider) {
            return;
        }

        const configSection = document.getElementById('ocr-' + provider + '-config');
        if (configSection) {
            configSection.classList.remove('d-none');
        }
    });
}

function bindQuickActions() {
    const pageRoot = document.querySelector('[data-site-settings-page]');

    if (!pageRoot) {
        return;
    }

    pageRoot.addEventListener('click', function (event) {
        const actionButton = event.target.closest('[data-site-settings-action]');
        if (!actionButton) {
            return;
        }

        const action = actionButton.getAttribute('data-site-settings-action');
        if (action === 'clear-cache') {
            clearCache(actionButton);
            return;
        }

        if (action === 'sanitize-head-snippets') {
            sanitizeHeadSnippets(actionButton);
            return;
        }

        if (action === 'export-settings') {
            exportSettings();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    bindColorPickerSync();
    bindOcrProviderSwitcher();
    bindQuickActions();
});
