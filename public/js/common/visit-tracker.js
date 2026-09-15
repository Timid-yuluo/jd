(function () {
    'use strict';

    var SESSION_KEY = '_vsid';
    var TRACK_ENDPOINT = '/api/track/event';
    var DURATION_ENDPOINT = '/api/track/duration';
    var pageLoadTime = Date.now();
    var reported = false;
    var lastPath = location.pathname;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getSessionId() {
        var sid = getCookie(SESSION_KEY);
        if (sid && sid.length === 64) return sid;
        var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        var id = '';
        for (var i = 0; i < 64; i++) {
            id += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return id;
    }

    function sendBeaconData(url, data) {
        var payload = JSON.stringify(data);
        try {
            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon(url, blob);
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
                xhr.send(payload);
            }
        } catch (e) {
            // 静默
        }
    }

    function reportDuration() {
        if (reported) return;
        reported = true;
        var duration = Date.now() - pageLoadTime;
        if (duration < 2000) return;
        sendBeaconData(DURATION_ENDPOINT, {
            session_id: getSessionId(),
            path: lastPath,
            duration_ms: duration,
        });
    }

    function reportEvent(event, label) {
        sendBeaconData(TRACK_ENDPOINT, {
            event: event,
            label: label || '',
            path: location.pathname,
        });
    }

    function resetPageTimer() {
        lastPath = location.pathname;
        pageLoadTime = Date.now();
        reported = false;
    }

    // 页面卸载时上报停留时长
    window.addEventListener('beforeunload', reportDuration);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            reportDuration();
        } else {
            resetPageTimer();
        }
    });

    // SPA 兼容：监听 popstate（浏览器前进/后退）
    window.addEventListener('popstate', function () {
        if (location.pathname !== lastPath) {
            reportDuration();
            resetPageTimer();
        }
    });

    // SPA 兼容：拦截 pushState/replaceState
    var origPushState = history.pushState;
    var origReplaceState = history.replaceState;

    history.pushState = function () {
        origPushState.apply(this, arguments);
        if (location.pathname !== lastPath) {
            reportDuration();
            resetPageTimer();
        }
    };

    history.replaceState = function () {
        origReplaceState.apply(this, arguments);
    };

    // 关键交互事件上报（带 data-track 属性的元素）
    document.addEventListener('click', function (e) {
        var trackable = e.target.closest('[data-track]');
        if (trackable) {
            var eventName = trackable.getAttribute('data-track');
            var eventLabel = trackable.getAttribute('data-track-label') ||
                (trackable.textContent || '').trim().substring(0, 100);
            reportEvent(eventName, eventLabel);
        }
    });

    // 滚动深度追踪（节流 + 里程碑）
    var scrollMilestones = { 25: false, 50: false, 75: false, 100: false };
    var scrollTimer = null;

    function checkScrollDepth() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight
        ) - window.innerHeight;

        if (docHeight <= 0) return;
        var percent = Math.round((scrollTop / docHeight) * 100);

        for (var milestone in scrollMilestones) {
            if (!scrollMilestones[milestone] && percent >= parseInt(milestone)) {
                scrollMilestones[milestone] = true;
                reportEvent('scroll_depth', milestone + '%');
            }
        }
    }

    window.addEventListener('scroll', function () {
        if (scrollTimer) return;
        scrollTimer = setTimeout(function () {
            scrollTimer = null;
            checkScrollDepth();
        }, 800);
    }, { passive: true });

    // 暴露全局方法供手动调用
    window.__trackEvent = reportEvent;
})();
