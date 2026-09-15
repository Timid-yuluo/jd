window.showLoading = function(form, text) {
    const btn = form.querySelector('.submit-btn') || form.querySelector('button[type="submit"]');
    if (btn && !btn.dataset.originalHtml) {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + (text || '处理中...');
    }
    return true;
};

// Notification System
(function() {
    var notifyDropdown = document.getElementById('notification-dropdown');
    if (!notifyDropdown) return;

    var unreadUrl = notifyDropdown.dataset.unreadUrl;
    var recentUrl = notifyDropdown.dataset.recentUrl;
    var badge = document.getElementById('notification-badge');
    var navBadge = document.getElementById('nav-notification-badge');
    var list = document.getElementById('notification-list');
    var previousCount = 0;

    function updateNotificationCount() {
        fetch(unreadUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var count = data.count;

                if (count > previousCount && previousCount > 0) {
                    playNotificationSound();
                    showBrowserNotification('新通知', '您有 ' + (count - previousCount) + ' 条新消息');
                }
                previousCount = count;

                if (count > 0) {
                    if (badge) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'block';
                    }
                    if (navBadge) {
                        navBadge.textContent = count > 99 ? '99+' : count;
                        navBadge.style.display = 'inline';
                    }
                } else {
                    if (badge) badge.style.display = 'none';
                    if (navBadge) navBadge.style.display = 'none';
                }
            }
        })
        .catch(function() {});
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function loadRecentNotifications() {
        if (!list) return;

        fetch(recentUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var notifications = data.notifications;
                if (notifications.length === 0) {
                    list.innerHTML = '<div class="list-group-item text-center py-4 text-secondary"><i class="ti ti-bell-off d-block mb-2"></i>暂无通知</div>';
                } else {
                    list.innerHTML = notifications.map(function(n) {
                        var title = escapeHtml(n.title || '');
                        var url = escapeHtml(n.url || '#');
                        var typeColor = escapeHtml(n.type_color || 'primary');
                        var createdAt = escapeHtml(n.created_at || '');
                        var icon = n.is_read ? 'ti-check' : 'ti-point-filled';
                        var fw = n.is_read ? 'normal' : 'bold';
                        return '<a href="' + url + '" class="list-group-item list-group-item-action p-3">' +
                            '<div class="d-flex align-items-center">' +
                            '<div class="me-3"><span class="badge bg-' + typeColor + '-lt text-' + typeColor + '"><i class="ti ' + icon + '"></i></span></div>' +
                            '<div class="flex-fill"><div class="fw-' + fw + ' text-truncate" style="max-width: 200px;">' + title + '</div><div class="small text-secondary">' + createdAt + '</div></div>' +
                            '</div></a>';
                    }).join('');
                }
            }
        })
        .catch(function() {
            list.innerHTML = '<div class="list-group-item text-center py-3 text-secondary">加载失败</div>';
        });
    }

    function playNotificationSound() {
        var audio = new Audio('/sounds/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(function() {});
    }

    function showBrowserNotification(title, body) {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                badge: '/favicon.ico'
            });
        }
    }

    updateNotificationCount();

    var dropdown = document.querySelector('[data-bs-toggle="dropdown"][aria-label="通知"]');
    if (dropdown) {
        dropdown.addEventListener('click', loadRecentNotifications);
    }

    // 可见性感知 + 自适应频率轮询
    // - 页面可见：有新通知时 15s 轮询，无新通知时逐步退避到 60s
    // - 页面隐藏：暂停轮询，重新可见时立即检查
    var pollTimer = null;
    var currentInterval = 15000; // 起始 15s
    var minInterval = 15000;
    var maxInterval = 60000;
    var backoffStep = 5000;

    function scheduleNextPoll() {
        if (pollTimer) clearTimeout(pollTimer);
        if (document.hidden) return; // 页面隐藏时不调度
        pollTimer = setTimeout(function() {
            updateNotificationCount();
        }, currentInterval);
    }

    // 包装原 updateNotificationCount，根据结果调整下次轮询间隔
    var originalUpdate = updateNotificationCount;
    updateNotificationCount = function() {
        fetch(unreadUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var count = data.count;
                if (count > previousCount && previousCount > 0) {
                    playNotificationSound();
                    showBrowserNotification('新通知', '您有 ' + (count - previousCount) + ' 条新消息');
                    // 有新通知时恢复到最短间隔
                    currentInterval = minInterval;
                } else if (count <= previousCount) {
                    // 无新通知时逐步退避
                    currentInterval = Math.min(currentInterval + backoffStep, maxInterval);
                }
                previousCount = count;

                if (count > 0) {
                    if (badge) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'block';
                    }
                    if (navBadge) {
                        navBadge.textContent = count > 99 ? '99+' : count;
                        navBadge.style.display = 'inline';
                    }
                } else {
                    if (badge) badge.style.display = 'none';
                    if (navBadge) navBadge.style.display = 'none';
                }
            }
            scheduleNextPoll();
        })
        .catch(function() {
            // 失败时也继续调度，使用当前间隔
            scheduleNextPoll();
        });
    };

    // 页面可见性变化：重新可见时立即检查并恢复轮询
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotificationCount();
        }
    });

    // 启动自适应轮询（替代原固定 setInterval）
    scheduleNextPoll();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(function() {
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission().catch(function() {});
                }
            })
            .catch(function() {});
    }
})();
