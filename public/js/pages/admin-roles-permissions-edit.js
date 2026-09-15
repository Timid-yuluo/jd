(function() {
'use strict';

document.querySelectorAll('.group-check-all').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const checked = this.checked;
        document.querySelectorAll(`.permission-check[data-group="${group}"]`).forEach(cb => {
            cb.checked = checked;
        });
    });
});

document.querySelectorAll('.permission-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const allChecked = document.querySelectorAll(`.permission-check[data-group="${group}"]:checked`).length;
        const total = document.querySelectorAll(`.permission-check[data-group="${group}"]`).length;
        const groupCheck = document.querySelector(`.group-check-all[data-group="${group}"]`);
        if (groupCheck) {
            groupCheck.checked = allChecked === total;
            groupCheck.indeterminate = allChecked > 0 && allChecked < total;
        }
    });
});

function confirmDelete(id, name) {
    document.getElementById('deleteRoleName').textContent = name;
    document.getElementById('deleteForm').action = `document.querySelector('[data-url-admin-roles-permissions]')?.getAttribute('data-url-admin-roles-permissions') || ''/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
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
