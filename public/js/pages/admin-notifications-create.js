(function() {
    'use strict';

    // ===== 事件委托 =====
    document.addEventListener('change', function(e) {
        var target = e.target;
        if (target.matches('[data-toggle="channel-options"]')) {
            toggleChannelOptions();
        } else if (target.matches('[data-toggle="target-options"]')) {
            toggleTargetOptions();
        } else if (target.matches('[data-toggle="send-type-options"]')) {
            toggleSendTypeOptions();
        }
    });

    function toggleTargetOptions() {
        var targetType = document.querySelector('input[name="target_type"]:checked').value;
        document.getElementById('roles-options').classList.toggle('d-none', targetType !== 'roles');
        document.getElementById('users-options').classList.toggle('d-none', targetType !== 'users');
    }

    function toggleChannelOptions() {
        var channel = document.querySelector('input[name="channel"]:checked').value;
        document.getElementById('email-template-options').classList.toggle('d-none', channel === 'site');
    }

    function toggleSendTypeOptions() {
        var sendType = document.querySelector('input[name="send_type"]:checked').value;
        document.getElementById('scheduled-time-options').classList.toggle('d-none', sendType !== 'later');

        var btn = document.getElementById('submit-btn');
        switch(sendType) {
            case 'draft':
                btn.innerHTML = '<i class="ti ti-device-floppy me-1"></i>保存为草稿';
                break;
            case 'now':
                btn.innerHTML = '<i class="ti ti-send me-1"></i>立即发送';
                break;
            case 'later':
                btn.innerHTML = '<i class="ti ti-clock me-1"></i>设置定时发送';
                break;
        }
    }

    // 实时预览
    document.querySelector('input[name="title"]').addEventListener('input', function() {
        document.getElementById('preview-title').textContent = this.value || '通知标题';
    });

    if (typeof window.bindTinyMceChange === 'function') {
        window.bindTinyMceChange('content-editor', function (content) {
            document.getElementById('preview-content').innerHTML = content || '通知内容将显示在这里...';
        });
    }

    // 类型变更更新预览样式
    document.querySelectorAll('input[name="type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var alert = document.getElementById('preview-alert');
            alert.className = 'alert';
            switch(this.value) {
                case 'announcement': alert.classList.add('alert-info'); break;
                case 'feature': alert.classList.add('alert-success'); break;
                case 'maintenance': alert.classList.add('alert-warning'); break;
                case 'warning': alert.classList.add('alert-danger'); break;
            }
        });
    });

    // 初始化
    if (document.querySelector('input[name="type"]:checked')) {
        document.querySelector('input[name="type"]:checked').dispatchEvent(new Event('change'));
    }
    toggleSendTypeOptions();

    // 设置最小时间为当前时间
    var scheduledAtInput = document.querySelector('input[name="scheduled_at"]');
    if (scheduledAtInput) {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        scheduledAtInput.min = now.toISOString().slice(0, 16);
    }
})();
