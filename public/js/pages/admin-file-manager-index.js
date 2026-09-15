(function() {
'use strict';

function showRenameModal(path, name) {
    document.getElementById('rename_old_path').value = path;
    document.getElementById('rename_new_name').value = name;
    new bootstrap.Modal(document.getElementById('renameModal')).show();
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
