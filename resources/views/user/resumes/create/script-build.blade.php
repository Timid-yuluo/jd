function buildContentRaw() {
    const lines = [];

    lines.push('## 个人信息');
    if (formData.personal.name) lines.push(formData.personal.name);
    if (formData.personal.phone) lines.push('电话: ' + formData.personal.phone);
    if (formData.personal.email) lines.push('邮箱: ' + formData.personal.email);
    if (formData.personal.location) lines.push('城市: ' + formData.personal.location);
    if (formData.personal.gender) lines.push('性别: ' + formData.personal.gender);
    if (formData.personal.birthday) lines.push('生日: ' + formData.personal.birthday);
    if (formData.personal.wechat) lines.push('微信: ' + formData.personal.wechat);
    if (formData.personal.github) lines.push('GitHub: ' + formData.personal.github);
    if (formData.personal.website) lines.push('作品集: ' + formData.personal.website);
    (formData.personal.custom_fields || []).forEach(field => {
        if (field?.label && field?.value) lines.push(`${field.label}: ${field.value}`);
    });
    if (formData.personal.content) lines.push(formData.personal.content);
    lines.push('');

    const sections = [
        { key: 'education', title: '教育经历' },
        { key: 'experience', title: '实习经历' },
        { key: 'project', title: '项目经验' },
        { key: 'skill', title: '技能证书' },
        { key: 'certificate', title: '获奖情况' },
    ];

    for (const sec of sections) {
        const items = formData[sec.key];
        if (items.some(e => e.subtitle || e.content)) {
            lines.push('## ' + sec.title);
            items.forEach(e => {
                const meta = [e.subtitle, e.date, e.location].filter(Boolean).join(' | ');
                if (meta) lines.push(meta);
                if (e.content) lines.push(e.content);
                lines.push('');
            });
        }
    }

    return lines.join('\n').trim();
}

function buildModules() {
    const modules = [];
    let order = 0;

    modules.push({
        type: 'personal',
        data: { ...formData.personal },
        sort_order: order++,
    });

    const pushSection = (type, title, items) => {
        const valid = items.filter(i => i.subtitle || i.content);
        if (!valid.length) return;
        valid.forEach(item => {
            modules.push({
                type,
                data: {
                    title,
                    subtitle: item.subtitle || '',
                    date: item.date || '',
                    location: item.location || '',
                    content: item.content || '',
                },
                sort_order: order++,
            });
        });
    };

    pushSection('education', '教育经历', formData.education);
    pushSection('experience', '实习经历', formData.experience);
    pushSection('project', '项目经验', formData.project);

    if (formData.skill.some(s => s.content)) {
        modules.push({
            type: 'skill',
            data: {
                title: '技能证书',
                content: formData.skill.map(s => s.content).filter(Boolean).join('\n'),
            },
            sort_order: order++,
        });
    }

    if (formData.certificate.some(c => c.content)) {
        modules.push({
            type: 'certificate',
            data: {
                title: '获奖情况',
                content: formData.certificate.map(c => c.content).filter(Boolean).join('\n'),
            },
            sort_order: order++,
        });
    }

    return modules;
}

function submitForm() {
    const form = document.getElementById('resume-form');
    if (form && form.dataset.resumeQuotaAllowed === '0') {
        showToast('当前套餐简历数量已达上限，请升级后继续创建。', 'warning');
        window.location.href = '/user/pricing';
        return false;
    }

    syncStepData(currentStep);

    if (!formData.title) {
        showToast('请填写简历标题', 'warning');
        goToStep(1);
        return false;
    }

    const contentRaw = buildContentRaw();
    if (!contentRaw) {
        showToast('请至少填写一项内容', 'warning');
        return false;
    }

    document.getElementById('hidden-title').value = formData.title;
    document.getElementById('hidden-target-job').value = formData.target_job;
    document.getElementById('hidden-target-company').value = formData.target_company;
    document.getElementById('hidden-template').value = formData.template;
    document.getElementById('hidden-content-raw').value = contentRaw;
    document.getElementById('hidden-modules').value = JSON.stringify(buildModules());

    sessionStorage.removeItem(STORAGE_KEY);

    return true;
}

function goToStep(n) {
    if (n < 1 || n > totalSteps) return;
    if (n > currentStep && !validateStep(currentStep)) return;
    syncStepData(currentStep);
    currentStep = n;
    renderStep();
    updateStepIndicator();
    updateHint();
}

// ===== 进度与状态 =====
function updateProgress() {
    let filledSections = 0;
    if (formData.title) filledSections++;
    if (formData.education.some(e => e.subtitle || e.content)) filledSections++;
    if (formData.experience.some(e => e.subtitle || e.content)) filledSections++;
    if (formData.project.some(e => e.subtitle || e.content)) filledSections++;
    if (formData.skill.some(e => e.content)) filledSections++;
    if (formData.certificate.some(e => e.content)) filledSections++;

    const percent = Math.round((filledSections / 6) * 100);
    const circumference = 2 * Math.PI * 52;
    const offset = circumference - (percent / 100) * circumference;
    document.getElementById('progress-ring').style.strokeDashoffset = offset;
    document.getElementById('progress-percent').textContent = percent + '%';
}

function updateModuleStatus() {
    const statuses = {
        basic: formData.title ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
        education: formData.education.some(e => e.subtitle || e.content) ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
        experience: formData.experience.some(e => e.subtitle || e.content) ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
        project: formData.project.some(e => e.subtitle || e.content) ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
        skill: formData.skill.some(e => e.content) ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
        certificate: formData.certificate.some(e => e.content) ? { text: '已填写', class: 'bg-success-lt' } : { text: '待填写', class: 'bg-secondary-lt' },
    };

    for (const [key, status] of Object.entries(statuses)) {
        const badge = document.querySelector(`[data-status-badge="${key}"]`);
        if (badge) {
            badge.textContent = status.text;
            badge.className = `badge ${status.class}`;
            badge.style.fontSize = '11px';
        }
    }
}

function updateHint() {
    const body = document.getElementById('step-hint-body');
    if (body) body.textContent = stepHints[currentStep] || '';
}

// ===== 草稿保存 / 恢复 =====
function saveDraft() {
    const draft = {
        currentStep,
        formData,
        savedAt: new Date().toISOString(),
    };
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
}

function loadDraft() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft.formData) return;

        Object.assign(formData, draft.formData);
        if (draft.currentStep && draft.currentStep >= 1 && draft.currentStep <= totalSteps) {
            currentStep = draft.currentStep;
        }

        restoreListItems('education', formData.education);
        restoreListItems('experience', formData.experience);
        restoreListItems('project', formData.project);
        restoreListItems('skill', formData.skill);
        restoreListItems('certificate', formData.certificate);
    } catch (e) {}
}

function restoreListItems(section, items) {
    const container = document.getElementById(`list-${section}`);
    container.innerHTML = '';
    if (!items || items.length === 0) {
        addItem(section);
        return;
    }
    items.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = 'module-card';
        div.innerHTML = renderModuleCard(section, idx, item);
        container.appendChild(div);
    });
}

function autoLoadLatestResume() {
    var dataEl = document.getElementById('latestResumeData');
    if (!dataEl) return;

    try {
        var data = JSON.parse(dataEl.textContent);
        if (!data) return;

        if (data.title) formData.title = data.title;
        if (data.target_job) formData.target_job = data.target_job;
        if (data.target_company) formData.target_company = data.target_company;
        if (data.template) formData.template = data.template;

        if (Array.isArray(data.modules)) {
            data.modules.forEach(function(m) {
                if (!m || !m.type || !m.data) return;
                if (m.type === 'personal' && m.data) {
                    var d = m.data;
                    if (d.name) formData.personal.name = d.name;
                    if (d.phone) formData.personal.phone = d.phone;
                    if (d.email) formData.personal.email = d.email;
                    if (d.location) formData.personal.location = d.location;
                    if (d.gender) formData.personal.gender = d.gender;
                    if (d.birthday) formData.personal.birthday = d.birthday;
                    if (d.wechat) formData.personal.wechat = d.wechat;
                    if (d.github) formData.personal.github = d.github;
                    if (d.website) formData.personal.website = d.website;
                    if (d.content && typeof d.content === 'string') formData.personal.content = d.content;
                    if (Array.isArray(d.custom_fields)) formData.personal.custom_fields = d.custom_fields;
                    return;
                }
                if (Array.isArray(formData[m.type])) {
                    formData[m.type].push(m.data);
                }
            });
        }

        document.getElementById('input-title').value = formData.title;
        document.getElementById('input-target-job').value = formData.target_job;
        document.getElementById('input-target-company').value = formData.target_company;
        document.getElementById('input-template').value = formData.template;
        document.getElementById('input-name').value = formData.personal.name;
        document.getElementById('input-phone').value = formData.personal.phone;
        document.getElementById('input-email').value = formData.personal.email;
        document.getElementById('input-location').value = formData.personal.location;
        document.getElementById('input-gender').value = formData.personal.gender || '';
        document.getElementById('input-birthday').value = formData.personal.birthday || '';
        document.getElementById('input-wechat').value = formData.personal.wechat || '';
        document.getElementById('input-github').value = formData.personal.github || '';
        document.getElementById('input-website').value = formData.personal.website || '';
        document.getElementById('input-personal-content').value = formData.personal.content;
        if (typeof renderPersonalCustomFields === 'function') {
            renderPersonalCustomFields(formData.personal.custom_fields || []);
        }

        restoreListItems('education', formData.education);
        restoreListItems('experience', formData.experience);
        restoreListItems('project', formData.project);
        restoreListItems('skill', formData.skill);
        restoreListItems('certificate', formData.certificate);

        var btn = document.getElementById('btnLoadLatest');
        if (btn) {
            btn.textContent = '已自动加载';
            btn.disabled = true;
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-success');
        }
    } catch(e) {
        console.error('auto load latest resume error:', e);
    }
}

// ===== 导入功能 =====
