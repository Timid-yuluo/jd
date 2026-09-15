let currentStep = 1;
const totalSteps = 6;
const STORAGE_KEY = 'resume_create_draft_v3';

localStorage.removeItem('resume_create_draft_v2');

const formData = {
    title: '',
    target_job: '',
    target_company: '',
    template: 'classic',
    personal: {
        name: '',
        phone: '',
        email: '',
        location: '',
        gender: '',
        birthday: '',
        wechat: '',
        github: '',
        website: '',
        custom_fields: [],
        content: '',
    },
    education: [],
    experience: [],
    project: [],
    skill: [],
    certificate: [],
};

const stepHints = {
    1: '填写简历标题和目标岗位，让 HR 快速了解你的求职方向。完整的信息将提升简历曝光率。',
    2: '按时间从近到远填写教育经历。建议包含学校、专业、学历、时间段和主修课程。',
    3: '描述实习经历时，使用 STAR 法则（情境-任务-行动-结果），并尽量量化成果。',
    4: '项目经验是技术岗的核心。写清项目背景、你的职责、使用的技术栈和最终成果。',
    5: '技能列表建议按熟练度排序。证书请写明颁发机构和获得时间。',
    6: '获奖情况按含金量排序。竞赛类奖项可补充获奖比例（如前 5%）。',
};

function init() {
    loadDraft();
    if (formData.education.length === 0) addItem('education');
    if (formData.experience.length === 0) addItem('experience');
    if (formData.project.length === 0) addItem('project');
    if (formData.skill.length === 0) addItem('skill');
    if (formData.certificate.length === 0) addItem('certificate');

    // 恢复第一步表单值
    document.getElementById('input-title').value = formData.title;
    document.getElementById('input-target-job').value = formData.target_job;
    document.getElementById('input-target-company').value = formData.target_company;
    document.getElementById('hidden-template').value = formData.template;
    // 同步模板选择器高亮
    document.querySelectorAll('#template-picker .template-card').forEach(function(card) {
        var radio = card.querySelector('input[type=radio]');
        var thumb = card.querySelector('.template-thumb');
        if (radio && thumb) {
            if (radio.value === formData.template) {
                radio.checked = true;
                thumb.style.borderColor = card.dataset.template === formData.template ? thumb.style.borderColor : '#e5e7eb';
            }
        }
    });
    document.getElementById('input-name').value = formData.personal.name;
    document.getElementById('input-phone').value = formData.personal.phone;
    document.getElementById('input-email').value = formData.personal.email;
    document.getElementById('input-location').value = formData.personal.location;
    document.getElementById('input-gender').value = formData.personal.gender || '';
    document.getElementById('input-birthday').value = formData.personal.birthday || '';
    document.getElementById('input-wechat').value = formData.personal.wechat || '';
    document.getElementById('input-github').value = formData.personal.github || '';
    document.getElementById('input-website').value = formData.personal.website || '';
    renderPersonalCustomFields(formData.personal.custom_fields || []);
    document.getElementById('input-personal-content').value = formData.personal.content;

    renderStep();
    updateStepIndicator();
    updateProgress();
    updateHint();
    updateModuleStatus();
    updateStepQuality();

    // 自动保存
    var draftTimer = setInterval(saveDraft, 5000);

    // 拖拽导入
    setupDragDrop();

    // 自动触发 AI 分析
    initAutoAnalyze();

    // ===== 全局事件委托（替代 inline event handlers）=====
    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-action]');
        if (!el) return;
        var action = el.dataset.action;

        switch (action) {
            case 'step-click':
                handleStepClick(parseInt(el.dataset.stepNum));
                break;
            case 'change-step':
                changeStep(parseInt(el.dataset.stepDir));
                break;
            case 'add-item':
                addItem(el.dataset.section);
                break;
            case 'add-custom-field':
                addPersonalCustomField();
                break;
            case 'import-resume':
                document.getElementById('resume-file').click();
                break;
            case 'ai-generate-personal':
                aiGeneratePersonal();
                break;
            case 'ai-optimize-personal':
                aiOptimizePersonal();
                break;
            case 'ai-generate-section':
                aiGenerateSection(el, el.dataset.section);
                break;
            case 'ai-optimize-section':
                aiOptimizeSection(el, el.dataset.section);
                break;
            case 'toggle-history':
                toggleHistory(el);
                break;
            case 'remove-item':
                removeItem(el);
                break;
            case 'remove-custom-field':
                removePersonalCustomField(el);
                break;
            case 'apply-ai':
                applyAiOptimize(el);
                break;
            case 'ignore-ai':
                ignoreAiOptimize(el);
                break;
            case 'apply-personal-ai':
                applyPersonalAi(el);
                break;
            case 'batch-optimize':
                batchOptimizeAll();
                break;
            case 'apply-history':
                applyHistoryVersion(el, parseInt(el.dataset.historyIndex));
                break;
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target.matches('[data-action="import-file"]')) {
            importResume(e.target);
        }
    });

    document.addEventListener('submit', function(e) {
        if (e.target.matches('[data-action="submit-form"]')) {
            clearInterval(draftTimer);
            if (submitForm() === false) {
                e.preventDefault();
                draftTimer = setInterval(saveDraft, 5000);
            }
        }
    });
}

function handleStepClick(n) {
    if (n === currentStep) return;
    if (n > currentStep && !validateStep(currentStep)) return;
    // 只允许跳转到已完成的步骤或相邻下一步
    if (n > currentStep + 1) return;
    syncStepData(currentStep);
    currentStep = n;
    renderStep();
    updateStepIndicator();
    updateHint();
}

function changeStep(delta) {
    const next = currentStep + delta;
    if (next < 1 || next > totalSteps) return;
    if (delta > 0 && !validateStep(currentStep)) return;
    syncStepData(currentStep);
    currentStep = next;
    renderStep();
    updateStepIndicator();
    updateHint();
}

function renderStep() {
    document.querySelectorAll('.step-panel').forEach(el => el.classList.add('d-none'));
    const activePanel = document.querySelector(`.step-panel[data-step="${currentStep}"]`);
    if (activePanel) activePanel.classList.remove('d-none');

    const prevBtn = document.getElementById('btn-prev');
    prevBtn.disabled = currentStep === 1;
    prevBtn.classList.toggle('d-none', currentStep === 1);
    document.getElementById('btn-next').classList.toggle('d-none', currentStep === totalSteps);
    document.getElementById('btn-submit').classList.toggle('d-none', currentStep !== totalSteps);
}

function updateStepIndicator() {
    const icons = ['ti-user','ti-school','ti-briefcase','ti-code','ti-certificate','ti-award'];
    document.querySelectorAll('.step-node').forEach(el => {
        const step = parseInt(el.dataset.step);
        const circle = el.querySelector('.step-circle');
        el.classList.remove('active', 'completed', 'clickable');
        if (step === currentStep) {
            el.classList.add('active');
            circle.innerHTML = `<i class="ti ${icons[step-1]}"></i>`;
        } else if (step < currentStep) {
            el.classList.add('completed', 'clickable');
            // 替换图标为对勾
            circle.innerHTML = '<i class="ti ti-check"></i>';
        } else {
            // 恢复原始图标
            circle.innerHTML = `<i class="ti ${icons[step-1]}"></i>`;
            if (step <= currentStep + 1) {
                el.classList.add('clickable');
            }
        }
    });
}

function validateStep(step) {
    if (step === 1) {
        const title = document.getElementById('input-title').value.trim();
        if (!title) {
            showToast('请填写简历标题', 'warning');
            document.getElementById('input-title').focus();
            return false;
        }
        const name = document.getElementById('input-name').value.trim();
        if (!name) {
            showToast('请填写姓名', 'warning');
            document.getElementById('input-name').focus();
            return false;
        }
        return true;
    }

    const section = sectionByStep(step);
    const container = document.getElementById('list-' + section);
    if (!container) return true;

    const cards = container.querySelectorAll('.module-card');
    let hasContent = false;
    for (const card of cards) {
        const subtitle = card.querySelector('input[data-field="subtitle"]')?.value.trim();
        const content = card.querySelector('textarea[data-field="content"]')?.value.trim();
        if (subtitle || content) {
            hasContent = true;
            break;
        }
    }

    if (!hasContent) {
        const labels = { 2: '教育经历', 3: '实习经历', 4: '项目经验', 5: '技能证书', 6: '获奖情况' };
        showToast(`请至少填写一项${labels[step] || '内容'}`, 'warning');
        return false;
    }

    return true;
}

function syncStepData(step) {
    if (step === 1) {
        formData.title = document.getElementById('input-title').value.trim();
        formData.target_job = document.getElementById('input-target-job').value.trim();
        formData.target_company = document.getElementById('input-target-company').value.trim();
        formData.template = document.getElementById('hidden-template').value;
        formData.personal.name = document.getElementById('input-name').value.trim();
        formData.personal.phone = document.getElementById('input-phone').value.trim();
        formData.personal.email = document.getElementById('input-email').value.trim();
        formData.personal.location = document.getElementById('input-location').value.trim();
        formData.personal.gender = document.getElementById('input-gender').value.trim();
        formData.personal.birthday = document.getElementById('input-birthday').value.trim();
        formData.personal.wechat = document.getElementById('input-wechat').value.trim();
        formData.personal.github = document.getElementById('input-github').value.trim();
        formData.personal.website = document.getElementById('input-website').value.trim();
        formData.personal.custom_fields = readPersonalCustomFieldsFromDom();
        formData.personal.content = document.getElementById('input-personal-content').value.trim();
    } else {
        const section = sectionByStep(step);
        const listEl = document.getElementById(`list-${section}`);
        const cards = listEl.querySelectorAll('.module-card');
        formData[section] = [];
        cards.forEach(card => {
            const data = {};
            card.querySelectorAll('[data-field]').forEach(input => {
                data[input.dataset.field] = input.value.trim();
            });
            formData[section].push(data);
        });
    }
    updateProgress();
    updateModuleStatus();
    saveDraft();
}

function sectionByStep(step) {
    return ['', '', 'education', 'experience', 'project', 'skill', 'certificate'][step];
}

