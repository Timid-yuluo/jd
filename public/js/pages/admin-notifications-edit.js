// 初始化富文本编辑器
$('#content-editor').summernote({
    height: 200,
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'underline', 'clear']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['fullscreen', 'codeview']]
    ]
});

function toggleTargetOptions() {
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    document.getElementById('roles-options').classList.toggle('d-none', targetType !== 'roles');
    document.getElementById('users-options').classList.toggle('d-none', targetType !== 'users');
}

function toggleChannelOptions() {
    const channel = document.querySelector('input[name="channel"]:checked').value;
    document.getElementById('email-template-options').classList.toggle('d-none', channel === 'site');
}

function toggleSendTypeOptions() {
    const sendType = document.querySelector('input[name="send_type"]:checked').value;
    document.getElementById('scheduled-time-options').classList.toggle('d-none', sendType !== 'later');
}

// 实时预览
document.querySelector('input[name="title"]').addEventListener('input', function() {
    document.getElementById('preview-title').textContent = this.value || '通知标题';
});

$('#content-editor').on('summernote.change', function(we, contents) {
    document.getElementById('preview-content').innerHTML = contents || '通知内容将显示在这里...';
});

// 类型变更更新预览样式
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const alert = document.getElementById('preview-alert');
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
toggleChannelOptions();

// 设置最小时间为当前时间
const scheduledAtInput = document.querySelector('input[name="scheduled_at"]');
if (scheduledAtInput) {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    scheduledAtInput.min = now.toISOString().slice(0, 16);
}
