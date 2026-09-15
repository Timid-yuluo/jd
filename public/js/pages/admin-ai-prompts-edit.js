(function() {
'use strict';

function runPromptTest() {
    const input = document.getElementById('test-input').value;
    const btn = document.getElementById('btn-test-prompt');
    const resultDiv = document.getElementById('test-result');
    const statusIcon = document.getElementById('test-status-icon');
    const statusText = document.getElementById('test-status-text');
    const latencySpan = document.getElementById('test-latency');
    const outputPre = document.getElementById('test-output');

    if (!input.trim()) {
        window.appNotify('请输入测试内容');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>测试中...';
    resultDiv.classList.remove('d-none');
    statusIcon.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    statusText.textContent = '测试中...';
    latencySpan.textContent = '';
    outputPre.textContent = '';

    fetch(document.querySelector('[data-route-admin-ai-prompts-test-0]')?.getAttribute('data-route-admin-ai-prompts-test-0') || '', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ test_input: input })
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
