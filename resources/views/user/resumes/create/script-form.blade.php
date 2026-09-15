function addItem(section, initialData = null) {
    const container = document.getElementById(`list-${section}`);
    if (!container) return;
    const idx = container.children.length;
    const div = document.createElement('div');
    div.className = 'module-card';
    div.style.animation = 'fadeIn 0.3s ease';
    div.innerHTML = renderModuleCard(section, idx, initialData);
    container.appendChild(div);

    // 自动保存
    setTimeout(() => {
        syncStepData(currentStep);
        saveDraft();
    }, 100);
}

function removeItem(btn) {
    const card = btn.closest('.module-card');
    const container = card.parentElement;
    if (container.children.length <= 1) {
        showToast('至少保留一项', 'warning');
        return;
    }
    card.style.opacity = '0';
    card.style.transform = 'translateX(20px)';
    setTimeout(() => {
        card.remove();
        container.querySelectorAll('.module-card').forEach((c, i) => {
            const numEl = c.querySelector('.module-index');
            if (numEl) numEl.textContent = i + 1;
        });
        syncStepData(currentStep);
        saveDraft();
    }, 250);
}

function renderModuleCard(section, idx, data = null) {
    const num = idx + 1;
    const fields = {
        education: [
            { key: 'subtitle', label: '学校 / 专业', placeholder: '例如：北京大学 计算机科学与技术', width: 6, value: data?.subtitle ?? '' },
            { key: 'date', label: '时间', placeholder: '例如：2022.09 - 2026.06', width: 3, value: data?.date ?? '' },
            { key: 'location', label: '地点', placeholder: '城市', width: 3, value: data?.location ?? '' },
            { key: 'content', label: '描述', placeholder: '主修课程、GPA、荣誉等', width: 12, type: 'textarea', value: data?.content ?? '' },
        ],
        experience: [
            { key: 'subtitle', label: '公司 / 职位', placeholder: '例如：字节跳动 前端实习生', width: 6, value: data?.subtitle ?? '' },
            { key: 'date', label: '时间', placeholder: '例如：2024.06 - 2024.09', width: 3, value: data?.date ?? '' },
            { key: 'location', label: '地点', placeholder: '城市', width: 3, value: data?.location ?? '' },
            { key: 'content', label: '工作内容', placeholder: '职责描述、成果数据、技术栈等', width: 12, type: 'textarea', value: data?.content ?? '' },
        ],
        project: [
            { key: 'subtitle', label: '项目名称 / 角色', placeholder: '例如：电商小程序 前端负责人', width: 6, value: data?.subtitle ?? '' },
            { key: 'date', label: '时间', placeholder: '例如：2024.03 - 2024.05', width: 3, value: data?.date ?? '' },
            { key: 'location', label: '链接（可选）', placeholder: 'GitHub / 演示地址', width: 3, value: data?.location ?? '' },
            { key: 'content', label: '项目描述', placeholder: '背景、职责、技术方案、量化成果', width: 12, type: 'textarea', value: data?.content ?? '' },
        ],
        skill: [
            { key: 'content', label: '技能 / 证书', placeholder: '例如：Python、React、PMP 证书', width: 12, type: 'textarea', value: data?.content ?? '' },
        ],
        certificate: [
            { key: 'content', label: '奖项 / 荣誉', placeholder: '例如：国家奖学金、ACM 银奖', width: 12, type: 'textarea', value: data?.content ?? '' },
        ],
    };

    const rows = fields[section].map(f => {
        const input = f.type === 'textarea'
            ? `<textarea class="form-control-modern w-100" data-field="${f.key}" rows="3" placeholder="${f.placeholder}">${escapeHtml(f.value)}</textarea>`
            : `<input type="text" class="form-control-modern w-100" data-field="${f.key}" placeholder="${f.placeholder}" value="${escapeHtml(f.value)}">`;
        return `<div class="col-md-${f.width}"><div class="mb-2"><label class="form-label-modern">${f.label}</label>${input}</div></div>`;
    }).join('');

    const qualityBadge = `<span class="quality-badge poor" data-quality-badge="${section}-${idx}">待评分</span>`;
    return `
        <div class="module-card-header">
            <div class="d-flex align-items-center">
                <span class="module-number module-index">${num}</span>
                <span class="module-title-text">${sectionTitles[section]}</span>
                ${qualityBadge}
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn-ai-generate" data-action="ai-generate-section" data-section="${section}" title="AI 生成">
                    <i class="ti ti-wand"></i>生成
                </button>
                <button type="button" class="btn-ai-optimize" data-action="ai-optimize-section" data-section="${section}" title="AI 智能优化">
                    <i class="ti ti-sparkles"></i>优化
                </button>
                <button type="button" class="btn-remove-module" data-action="toggle-history" title="历史记录" style="color:#9ca3af;">
                    <i class="ti ti-history"></i>
                </button>
                <button type="button" class="btn-remove-module" data-action="remove-item" title="删除">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <div class="row">${rows}</div>
        <div class="ai-result-panel d-none"></div>
        <div class="history-panel d-none" data-history-panel="${section}-${idx}"></div>
    `;
}

const sectionTitles = {
    education: '教育经历',
    experience: '实习经历',
    project: '项目经验',
    skill: '技能证书',
    certificate: '获奖情况',
};

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function normalizePersonalCustomFields(fields) {
    if (!Array.isArray(fields)) return [];
    const normalized = [];
    const seen = new Set();
    fields.forEach(field => {
        const label = String(field?.label || '').trim();
        const value = String(field?.value || '').trim();
        if (!label || !value) return;
        const key = `${label}::${value}`.toLowerCase();
        if (seen.has(key)) return;
        seen.add(key);
        normalized.push({ label, value });
    });
    return normalized;
}

function readPersonalCustomFieldsFromDom() {
    const container = document.getElementById('personal-custom-fields');
    if (!container) return [];
    const rows = Array.from(container.querySelectorAll('[data-custom-row="personal"]'));
    return normalizePersonalCustomFields(rows.map(row => ({
        label: row.querySelector('[data-custom-label]')?.value || '',
        value: row.querySelector('[data-custom-value]')?.value || '',
    })));
}

function renderPersonalCustomFields(fields = []) {
    const container = document.getElementById('personal-custom-fields');
    if (!container) return;
    const renderFields = Array.isArray(fields)
        ? fields.map(field => ({
            label: String(field?.label || ''),
            value: String(field?.value || ''),
        }))
        : [];
    container.innerHTML = renderFields.map(field => `
        <div class="row mb-2" data-custom-row="personal">
            <div class="col-md-4">
                <input type="text" class="form-control-modern w-100" data-custom-label placeholder="字段名（如：QQ）" value="${escapeHtml(field.label)}">
            </div>
            <div class="col-md-7">
                <input type="text" class="form-control-modern w-100" data-custom-value placeholder="字段值" value="${escapeHtml(field.value)}">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn-remove-module" data-action="remove-custom-field" title="删除">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function addPersonalCustomField(label = '', value = '') {
    const fields = readPersonalCustomFieldsFromDom();
    fields.push({ label, value });
    renderPersonalCustomFields(fields);
    if (currentStep === 1) {
        syncStepData(1);
    }
}

function removePersonalCustomField(button) {
    const row = button.closest('[data-custom-row="personal"]');
    if (row) row.remove();
    formData.personal.custom_fields = readPersonalCustomFieldsFromDom();
    saveDraft();
}
