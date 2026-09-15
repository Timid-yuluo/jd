// Service Worker for Push Notifications
const CACHE_NAME = 'notification-cache-v1';

// 安装 Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker installed');
    self.skipWaiting();
});

// 激活 Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker activated');
    event.waitUntil(clients.claim());
});

// 处理推送消息
self.addEventListener('push', (event) => {
    console.log('Push received:', event);

    const data = event.data ? event.data.json() : {};

    const title = data.title || '新通知';
    const options = {
        body: data.body || '您有一条新消息',
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        tag: data.tag || 'notification',
        requireInteraction: data.requireInteraction || false,
        data: data.data || {},
        actions: data.actions || [
            {
                action: 'open',
                title: '查看'
            },
            {
                action: 'close',
                title: '关闭'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// 处理通知点击
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);

    event.notification.close();

    const notificationData = event.notification.data;
    let url = notificationData.url || '/user/notifications';

    // 处理按钮点击
    if (event.action === 'close') {
        return;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // 如果已有窗口打开，则聚焦到该窗口
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // 否则打开新窗口
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// 处理后台同步（可选）
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

async function syncNotifications() {
    // 可以在这里实现后台同步逻辑
    console.log('Background sync triggered');
}
