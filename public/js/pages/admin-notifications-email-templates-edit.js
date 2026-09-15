(function() {
'use strict';

// 初始化富文本编辑器
$('#email-editor').summernote({
    height: 400,
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture']],
        ['view', ['fullscreen', 'codeview', 'help']]
    ]
});

// 变量按钮点击复制
document.querySelectorAll('.variable-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const variable = '{{ ' + this.dataset.variable + ' }}';
        $('#email-editor').summernote('editor.insertText', variable);
    });
});

// 预览功能
function previewTemplate() {
    const subject = document.querySelector('input[name="subject"]').value;
    const content = $('#email-editor').summernote('code');

    if (!subject || !content) {
        window.appNotify('请先填写邮件主题和内容');
        return;
    }

    // 简单的变量替换预览
    const previewData = {
        site_name: document.querySelector('[data-config-app-name]')?.getAttribute('data-config-app-name') || '',
        site_url: document.querySelector('[data-config-app-url]')?.getAttribute('data-config-app-url') || '',
        user_name: '张三',
        user_email: 'user@example.com',
        current_date: new Date().toISOString().split('T')[0],
        current_time: new Date().toLocaleTimeString(),
        notification_title: '这是一封测试邮件',
        notification_content: '<p>这是邮件的正文内容，支持 <strong>HTML</strong> 格式。</p>',
    };

    let previewSubject = subject;
    let previewContent = content;

    for (const [key, value] of Object.entries(previewData)) {
        const regex = new RegExp('{{ ' + key + ' }}', 'g');
        previewSubject = previewSubject.replace(regex, value);
        previewContent = previewContent.replace(regex, value);
    }

    document.getElementById('preview-subject').textContent = previewSubject;
    document.getElementById('preview-content').innerHTML = previewContent;

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
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
