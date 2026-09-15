(function() {
    'use strict';

    function submitReply() {
        var content = document.getElementById('replyContent').value.trim();
        if (!content) { window.appNotify('请输入回复内容', 'error'); return; }

        var btn = document.getElementById('replyBtn');
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>发送中...';

        fetch(document.querySelector('[data-route-feedback-reply-0]')?.getAttribute('data-route-feedback-reply-0') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ content: content })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                window.appNotify(d.message, 'success');
                document.getElementById('replyContent').value = '';
                var container = document.getElementById('repliesContainer');
                var emptyMsg = container.querySelector('.text-center');
                if (emptyMsg) emptyMsg.remove();

                var contentDiv = document.createElement('div');
                contentDiv.textContent = content;
                contentDiv.style.whiteSpace = 'pre-wrap';
                contentDiv.className = 'p-3 rounded bg-info-lt';

                var wrapper = document.createElement('div');
                wrapper.className = 'd-flex mb-3 flex-row-reverse';

                var avatar = document.createElement('div');
                avatar.className = 'avatar avatar-sm ms-3 me-0';
                avatar.style.cssText = 'background:#0EA5E9;color:#fff;font-weight:600;font-size:.75rem;flex-shrink:0;';
                avatar.textContent = (document.querySelector('[data-auth-name]')?.dataset.authName?.charAt(0)?.toUpperCase() || '');

                var body = document.createElement('div');
                body.className = 'ms-auto';
                body.style.maxWidth = '75%';

                var header = document.createElement('div');
                header.className = 'd-flex align-items-center gap-2 mb-1';
                header.innerHTML = '<span class="fw-medium small">' + (document.querySelector('[data-auth-name]')?.getAttribute('data-auth-name') || '') + '</span><span class="badge bg-info-lt">我</span><span class="text-secondary small">刚刚</span>';

                body.appendChild(header);
                body.appendChild(contentDiv);
                wrapper.appendChild(avatar);
                wrapper.appendChild(body);
                container.appendChild(wrapper);
            } else {
                window.appNotify(d.message || '回复失败', 'error');
            }
        })
        .catch(function() { window.appNotify('网络错误', 'error'); })
        .finally(function() { btn.disabled = false; btn.innerHTML = originalHtml; });
    }

    // 事件委托
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action="submit-reply"]');
        if (btn) submitReply();
    });
})();
