(function() {
'use strict';

function previewData(type) {
    const title = document.querySelector(`[data-type="${type}"] h3`)?.textContent || '数据预览';
    document.getElementById('previewTitle').textContent = title + ' - 预览（前5条）';
    document.getElementById('previewBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary mb-3"></div><p class="mb-0">加载中...</p></div>';
    new bootstrap.Modal(document.getElementById('previewModal')).show();

    const baseUrl = document.querySelector('[data-url-admin-data-export]')?.getAttribute('data-url-admin-data-export') || '';
    fetch(baseUrl + '/' + type + '/preview')
        .then(r => r.json())
        .then(data => {
            if (!data.columns || data.columns.length === 0) {
                document.getElementById('previewBody').innerHTML = '<div class="text-center text-secondary py-4">暂无数据</div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-vcenter text-nowrap"><thead><tr>';
            data.columns.forEach(col => { html += `<th>${col}</th>`; });
            html += '</tr></thead><tbody>';

            if (data.rows && data.rows.length > 0) {
                data.rows.forEach(row => {
                    html += '<tr>';
                    data.columns.forEach(col => {
                        let val = row[col];
                        if (val && typeof val === 'object') val = JSON.stringify(val);
                        html += `<td>${val ?? ''}</td>`;
                    });
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="' + data.columns.length + '" class="text-center py-3 text-secondary">暂无数据</td></tr>';
            }

            html += '</tbody></table></div>';
            document.getElementById('previewBody').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('previewBody').innerHTML = '<div class="alert alert-danger">加载失败: ' + err.message + '</div>';
        });
}

// 事件委托
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-action]');
  if (!btn) return;
  var action = btn.dataset.action;
  if (action === 'preview-data') {
    previewData(btn.dataset.exportKey);
    return;
  }
  if (typeof window[action] === 'function') { window[action](btn); }
});

document.addEventListener('change', function(e) {
  if (e.target.matches('[data-action]')) {
    var action = e.target.dataset.action;
    if (typeof window[action] === 'function') { window[action](e.target); }
  }
});
})();
