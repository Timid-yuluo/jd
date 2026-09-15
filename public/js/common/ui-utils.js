/**
 * Common UI Utilities
 * 通用 UI 工具函数 — 替代 blade 模板中的 inline event handlers
 */
(function() {
    'use strict';

    // 自动提交的 select (onchange="this.form.submit()")
    document.addEventListener('change', function(e) {
        if (e.target.matches('[data-auto-submit]')) {
            var form = e.target.closest('form');
            if (form) {
                form.submit();
            }
        }
    });

    // 确认删除 (onsubmit="return confirm('...')")
    document.addEventListener('submit', function(e) {
        var form = e.target;
        var confirmMsg = form.dataset.confirmSubmit;
        if (confirmMsg && !confirm(confirmMsg)) {
            e.preventDefault();
        }
    });

    // window.print()
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-print]');
        if (btn) {
            window.print();
        }

        // history.back()
        var goBack = e.target.closest('[data-action="go-back"]');
        if (goBack) {
            e.preventDefault();
            history.back();
        }

        // 刷新页面 (location.reload())
        var reload = e.target.closest('[data-action="reload"]');
        if (reload) {
            e.preventDefault();
            location.reload();
        }

        // 移除父元素组 (this.closest('.input-group').remove())
        var removeGroup = e.target.closest('[data-action="remove-group"]');
        if (removeGroup) {
            var group = removeGroup.closest(removeGroup.dataset.removeTarget || '.input-group');
            if (group) group.remove();
        }
    });

    // ===== 表单防重复提交 =====
    // 标记正在提交的表单，防止用户多次点击提交按钮
    var submittingForms = new WeakSet();

    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form.dataset.preventDoubleSubmit !== 'true') {
            return;
        }

        if (submittingForms.has(form)) {
            e.preventDefault();
            return;
        }

        submittingForms.add(form);

        // 禁用所有提交按钮并添加 loading 状态
        var submitButtons = form.querySelectorAll('[type="submit"], .submit-btn');
        submitButtons.forEach(function(btn) {
            if (!btn.dataset.originalHtml) {
                btn.dataset.originalHtml = btn.innerHTML;
            }
            btn.disabled = true;
            btn.classList.add('btn-loading');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>处理中...';
        });

        // 5 秒后自动恢复，防止网络异常导致按钮永久禁用
        setTimeout(function() {
            submittingForms.delete(form);
            submitButtons.forEach(function(btn) {
                btn.disabled = false;
                btn.classList.remove('btn-loading');
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }, 5000);
    });

    // ===== 表单未保存离开保护 =====
    // 标记了 data-unsaved-warning 的表单在未保存时离开页面会提示用户
    var unsavedForms = document.querySelectorAll('[data-unsaved-warning]');
    var hasUnsavedChanges = false;

    unsavedForms.forEach(function(form) {
        form.addEventListener('input', function() {
            hasUnsavedChanges = true;
        });
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // 暴露全局方法供页面手动标记/清除状态
    window.UiUtils = {
        markUnsaved: function() { hasUnsavedChanges = true; },
        clearUnsaved: function() { hasUnsavedChanges = false; }
    };
})();
