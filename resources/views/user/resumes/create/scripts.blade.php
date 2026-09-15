<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
@include('user.resumes.create.script-core')
@include('user.resumes.create.script-form')
@include('user.resumes.create.script-build')
@include('user.resumes.create.script-import-parse')
@include('user.resumes.create.script-smart-fill')
@include('user.resumes.create.script-ai')

init();

// 加载上一份简历数据
(function() {
    var btn = document.getElementById('btnLoadLatest');
    var dataEl = document.getElementById('latestResumeData');
    if (!btn || !dataEl) return;
    btn.addEventListener('click', function() {
        try {
            var data = JSON.parse(dataEl.textContent);
            if (data.title) document.getElementById('input-title').value = data.title;
            if (data.target_job) document.getElementById('input-target-job').value = data.target_job;
            if (data.target_company) document.getElementById('input-target-company').value = data.target_company;
            if (data.template) {
                document.getElementById('hidden-template').value = data.template;
                // 同步模板选择器
                document.querySelectorAll('#template-picker .template-card').forEach(function(card) {
                    var radio = card.querySelector('input[type=radio]');
                    if (radio) radio.checked = (radio.value === data.template);
                    var thumb = card.querySelector('.template-thumb');
                    if (thumb) {
                        var templates = {classic:'#2563eb',modern:'#7c3aed',minimal:'#6b7280',timeline:'#0891b2',creative:'#e11d48',elegant:'#b45309'};
                        thumb.style.borderColor = (card.dataset.template === data.template) ? (templates[data.template]||'#2563eb') : '#e5e7eb';
                    }
                });
            }
            if (Array.isArray(data.modules)) {
                data.modules.forEach(function(m) {
                    if (!m || !m.type || !m.data) return;
                    // 处理个人信息模块
                    if (m.type === 'personal' && m.data) {
                        var d = m.data;
                        if (d.name) document.getElementById('input-name').value = d.name;
                        if (d.phone) document.getElementById('input-phone').value = d.phone;
                        if (d.email) document.getElementById('input-email').value = d.email;
                        if (d.location) document.getElementById('input-location').value = d.location;
                        if (d.gender) document.getElementById('input-gender').value = d.gender;
                        if (d.birthday) document.getElementById('input-birthday').value = d.birthday;
                        if (d.wechat) document.getElementById('input-wechat').value = d.wechat;
                        if (d.github) document.getElementById('input-github').value = d.github;
                        if (d.website) document.getElementById('input-website').value = d.website;
                        if (d.content) document.getElementById('input-personal-content').value = (typeof d.content === 'string') ? d.content : '';
                        return;
                    }
                    // 其他模块通过 addItem 填充
                    if (typeof addItem === 'function') {
                        addItem(m.type, m.data);
                    }
                });
            }
            btn.textContent = '已加载';
            btn.disabled = true;
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');
            if (typeof window.appNotify === 'function') window.appNotify('已加载上一份简历数据', 'success');
        } catch(e) {
            console.error('load latest resume error:', e);
            if (typeof window.appNotify === 'function') window.appNotify('加载失败', 'error');
        }
    });
})();

// 模板选择器交互
(function() {
    var picker = document.getElementById('template-picker');
    if (!picker) return;
    var templates = {classic:'#2563eb',modern:'#7c3aed',minimal:'#6b7280',timeline:'#0891b2',creative:'#e11d48',elegant:'#b45309'};
    picker.addEventListener('click', function(e) {
        var card = e.target.closest('.template-card');
        if (!card) return;
        var val = card.dataset.template;
        document.getElementById('hidden-template').value = val;
        picker.querySelectorAll('.template-thumb').forEach(function(thumb) {
            var c = thumb.closest('.template-card');
            thumb.style.borderColor = (c && c.dataset.template === val) ? (templates[val]||'#2563eb') : '#e5e7eb';
        });
    });
})();
</script>
