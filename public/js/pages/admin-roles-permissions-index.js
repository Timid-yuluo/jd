(function() {
'use strict';

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
