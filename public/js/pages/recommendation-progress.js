/**
 * 岗位推荐生成进度轮询
 * #21 指数退避：3s → 5s → 10s → 15s，上限 15s，避免长任务（600s）发 200 次请求
 */
(function () {
    'use strict';

    var statusEl = document.getElementById('progress-status');
    if (!statusEl) return;

    var status = statusEl.dataset.status || '';
    if (status !== 'queued' && status !== 'processing' && status !== 'idle') return;

    var progressUrl = document.getElementById('progress-config')
        ? document.getElementById('progress-config').dataset.url
        : '';

    // #21 退避策略：每次失败/继续轮询时按序列增加间隔
    var backoffSteps = [3000, 3000, 5000, 5000, 10000, 10000, 15000];
    var stepIndex = 0;
    var MAX_INTERVAL = 15000;

    function nextInterval() {
        var interval = stepIndex < backoffSteps.length
            ? backoffSteps[stepIndex]
            : MAX_INTERVAL;
        stepIndex++;
        return interval;
    }

    function poll() {
        if (!progressUrl) return;
        fetch(progressUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.status === 'completed' || data.status === 'failed') {
                    location.reload();
                } else {
                    setTimeout(poll, nextInterval());
                }
            })
            .catch(function () { setTimeout(poll, nextInterval()); });
    }

    setTimeout(poll, nextInterval());
})();
