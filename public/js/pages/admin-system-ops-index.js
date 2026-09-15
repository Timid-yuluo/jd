(function() {
    'use strict';

    // ===== 事件委托 =====
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;

        switch (btn.dataset.action) {
            case 'clear-cache':
                clearCache(btn.dataset.cacheType);
                break;
            case 'optimize-system':
                optimizeSystem();
                break;
            case 'queue-action':
                queueAction(btn.dataset.queueAction);
                break;
            case 'run-migration':
                runMigration();
                break;
        }
    });

    async function clearCache(type) {
        var messages = {
            'all': '确定要清除所有缓存吗？',
            'config': '确定要清除配置缓存吗？',
            'route': '确定要清除路由缓存吗？',
            'view': '确定要清除视图缓存吗？',
        };

        var confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm(messages[type] || '确定要清除缓存吗？', { title: '清缓存确认', showCancel: true })
            : false;
        if (!confirmed) {
            return;
        }

        fetch(document.querySelector('[data-route-admin-system-ops-clear-cache]')?.getAttribute('data-route-admin-system-ops-clear-cache') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ type: type })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            window.appNotify(data.success ? '操作成功' : '操作失败：' + data.message);
        })
        .catch(function(err) {
            window.appNotify('请求失败：' + err.message);
        });
    }

    async function optimizeSystem() {
        var confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm('确定要优化系统吗？这将缓存配置、路由和视图。', { title: '优化确认', showCancel: true })
            : false;
        if (!confirmed) {
            return;
        }

        fetch(document.querySelector('[data-route-admin-system-ops-optimize]')?.getAttribute('data-route-admin-system-ops-optimize') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            window.appNotify(data.success ? '系统优化完成' : '操作失败：' + data.message);
        })
        .catch(function(err) {
            window.appNotify('请求失败：' + err.message);
        });
    }

    async function queueAction(action) {
        var messages = {
            'restart': '确定要重启队列吗？这将重启所有队列工作进程。',
            'retry': '确定要重试所有失败任务吗？',
            'flush': '确定要清空所有失败任务吗？此操作不可恢复！',
        };

        var confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm(messages[action], { title: '队列操作确认', showCancel: true })
            : false;
        if (!confirmed) {
            return;
        }

        fetch(document.querySelector('[data-route-admin-system-ops-queue-action]')?.getAttribute('data-route-admin-system-ops-queue-action') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ action: action })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            window.appNotify(data.success ? '操作成功' : '操作失败：' + data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(function(err) {
            window.appNotify('请求失败：' + err.message);
        });
    }

    async function runMigration() {
        var confirmed = typeof window.appConfirm === 'function'
            ? await window.appConfirm('确定要运行数据库迁移吗？请确保已备份数据库。', { title: '迁移确认', showCancel: true })
            : false;
        if (!confirmed) {
            return;
        }

        fetch(document.querySelector('[data-route-admin-system-ops-migrate]')?.getAttribute('data-route-admin-system-ops-migrate') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            window.appNotify(data.success ? ('迁移完成：' + data.output) : '迁移失败：' + data.message);
        })
        .catch(function(err) {
            window.appNotify('请求失败：' + err.message);
        });
    }
})();
