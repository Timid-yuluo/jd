(function() {
'use strict';

function runAiTest() {
    const provider = document.getElementById('test-provider').value;
    const type = document.getElementById('test-type').value;
    const btn = document.getElementById('btn-test-ai');
    const resultDiv = document.getElementById('test-result');
    const statusIcon = document.getElementById('test-status-icon');
    const statusText = document.getElementById('test-status-text');
    const latencySpan = document.getElementById('test-latency');
    const outputPre = document.getElementById('test-output');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>测试中...';
    resultDiv.classList.remove('d-none');
    statusIcon.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    statusText.textContent = '测试中...';
    latencySpan.textContent = '';
    outputPre.textContent = '';

    fetch(document.querySelector('[data-route-admin-ai-config-test]')?.getAttribute('data-route-admin-ai-config-test') || '', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ provider: provider, test_type: type })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-play me-1"></i>运行测试';

        if (data.success) {
            statusIcon.innerHTML = '<i class="ti ti-check text-green"></i>';
            statusText.textContent = '测试成功';
            latencySpan.textContent = data.latency_ms + ' ms';
            outputPre.textContent = JSON.stringify(data.result, null, 2);
        } else {
            statusIcon.innerHTML = '<i class="ti ti-x text-red"></i>';
            statusText.textContent = '测试失败';
            outputPre.textContent = data.error || '未知错误';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-play me-1"></i>运行测试';
        statusIcon.innerHTML = '<i class="ti ti-x text-red"></i>';
        statusText.textContent = '请求失败';
        outputPre.textContent = err.message;
    });
}

function runBenchmark(triggerButton) {
    const btn = triggerButton || null;
    if (!btn) {
        return;
    }
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>对比中...';

    fetch(document.querySelector('[data-route-admin-ai-config-benchmark]')?.getAttribute('data-route-admin-ai-config-benchmark') || '', {
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

        // 构建对比结果弹窗
        let html = '<div class="table-responsive"><table class="table table-sm table-vcenter">';
        html += '<thead><tr><th>Provider</th><th>状态</th><th>延迟</th><th>24h调用</th><th>24h平均延迟</th><th>错误率</th></tr></thead><tbody>';

        const providerNames = { deepseek: 'DeepSeek', volcano: '火山引擎', zhipu: '智谱AI' };

        for (const [provider, result] of Object.entries(data.results)) {
            const name = providerNames[provider] || provider;
            if (result.available) {
                const isBest = provider === data.recommended;
                html += `<tr class="${isBest ? 'table-success' : ''}">`;
                html += `<td><strong>${name}</strong>${isBest ? ' <span class="badge bg-green">推荐</span>' : ''}</td>`;
                html += `<td><span class="badge bg-green-lt text-green"><i class="ti ti-check"></i> 可用</span></td>`;
                html += `<td>${result.latency} ms</td>`;
                html += `<td>${result.stats_24h.calls} 次</td>`;
                html += `<td>${result.stats_24h.avg_latency} ms</td>`;
                html += `<td>${result.stats_24h.error_rate}%</td>`;
                html += '</tr>';
            } else {
                html += `<tr><td><strong>${name}</strong></td>`;
                html += `<td colspan="5"><span class="badge bg-red-lt text-red"><i class="ti ti-x"></i> ${result.error}</span></td></tr>`;
            }
        }
        html += '</tbody></table></div>';
        html += `<div class="text-secondary small mt-2">测试时间: ${data.tested_at}</div>`;

        // 显示弹窗
        const modal = document.createElement('div');
        modal.className = 'modal modal-blur fade show';
        modal.style.display = 'block';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Provider 性能对比</h5>
                        <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                    </div>
                    <div class="modal-body">${html}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="this.closest('.modal').remove()">确定</button>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        `;
        document.body.appendChild(modal);
        document.body.classList.add('modal-open');
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        window.appNotify('对比测试失败: ' + err.message);
    });
}

// 事件委托
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-action]');
  if (!btn) return;
  var action = btn.dataset.action;
  switch (action) {
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
