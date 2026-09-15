(function() {
'use strict';

function runTask(taskKey) {
    const modal = new bootstrap.Modal(document.getElementById('runTaskModal'));
    const content = document.getElementById('runTaskContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="mb-0">正在执行任务...</p>
        </div>
    `;
    modal.show();
    
    fetch((document.querySelector('[data-url-admin-schedule]')?.getAttribute('data-url-admin-schedule') || '') + `/${taskKey}/run`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = `
                <div class="alert alert-success">
                    <i class="ti ti-check-circle me-1"></i> 任务执行成功
                </div>
                <div class="mb-2">
                    <strong>耗时:</strong> ${data.duration}ms
                </div>
                <div class="bg-dark text-light p-2 rounded font-monospace small" style="max-height: 200px; overflow: auto;">
                    <pre class="m-0">${data.output || '无输出'}</pre>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-x-circle me-1"></i> ${data.message}
                </div>
            `;
        }
    })
    .catch(err => {
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-triangle me-1"></i> 请求失败: ${err.message}
            </div>
        `;
    });
}

function checkScheduleStatus() {
    fetch(document.querySelector('[data-route-admin-schedule-status]')?.getAttribute('data-route-admin-schedule-status') || '')
        .then(r => r.json())
        .then(data => {
            const statusEl = document.getElementById('cronStatus');
            const cronStatus = data.cron_configured 
                ? '<span class="badge bg-success">已配置</span>' 
                : '<span class="badge bg-danger">未配置</span>';
            const lastRun = data.last_run_at 
                ? new Date(data.last_run_at).toLocaleString() 
                : '从未执行';
            const lastStatus = data.last_run_status === 'success' 
                ? '<span class="badge bg-success">成功</span>' 
                : (data.last_run_status === 'failed' ? '<span class="badge bg-danger">失败</span>' : '-');
            
            statusEl.innerHTML = `
                <div class="row">
                    <div class="col-md-3"><strong>Cron配置:</strong> ${cronStatus}</div>
                    <div class="col-md-4"><strong>上次执行:</strong> ${lastRun}</div>
                    <div class="col-md-3"><strong>执行状态:</strong> ${lastStatus}</div>
                </div>
            `;
        })
        .catch(err => {
            window.appNotify('检查状态失败: ' + err.message, 'error');
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
