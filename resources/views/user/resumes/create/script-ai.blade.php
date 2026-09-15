
// ===== AI 优化 =====
async function aiOptimizeSection(btn, sectionType) {
    const card = btn.closest('.module-card');
    const resultPanel = card.querySelector('.ai-result-panel');
    const textarea = card.querySelector('textarea[data-field="content"]');
    const content = textarea ? textarea.value.trim() : '';

    if (!content) {
        showToast('请先填写内容再使用 AI 优化', 'warning');
        return;
    }

    const targetJob = document.getElementById('input-target-job').value.trim();

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>优化中...';
    resultPanel.classList.remove('d-none');
    resultPanel.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span><div class="small text-secondary mt-2">AI 正在分析并生成优化建议...</div></div>';

    try {
        const response = await fetch('{{ route("user.resumes.optimize-section") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                section_type: sectionType,
                content: content,
                target_job: targetJob,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || '优化失败');
        }

        renderAiResult(resultPanel, data, textarea, sectionType);
        updateAiAnalysisSidebar(data);
    } catch (e) {
        if (e?.quotaHandled) {
            resultPanel.classList.add('d-none');
            resultPanel.innerHTML = '';
            return;
        }
        resultPanel.innerHTML = `<div class="alert alert-danger mb-0" style="border-radius: 8px; font-size: 13px;"><i class="ti ti-alert-circle me-1"></i>${e.message || '优化失败，请稍后重试'}</div>`;
        showToast(e.message || '优化失败', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-sparkles"></i>AI 优化';
    }
}

function applyAiOptimize(btn) {
    const panel = btn.closest('.ai-result-panel');
    const card = panel.closest('.module-card');
    const textarea = card.querySelector('textarea[data-field="content"]');
    const originalText = textarea ? textarea.value.trim() : '';
    const optimizedText = panel.dataset.optimizedText;

    if (textarea && optimizedText) {
        saveOptimizeHistory(card, originalText, optimizedText);
        textarea.value = optimizedText;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        syncStepData(currentStep);
        saveDraft();
    }

    panel.classList.add('d-none');
    panel.innerHTML = '';
    showToast('已应用 AI 优化', 'success');
}

function ignoreAiOptimize(btn) {
    const panel = btn.closest('.ai-result-panel');
    panel.classList.add('d-none');
    panel.innerHTML = '';
}

function updateAiAnalysisSidebar(data) {
    const card = document.getElementById('ai-analysis-card');
    const body = document.getElementById('ai-analysis-body');
    if (!card || !body) return;

    const suggestions = data.suggestions || [];
    if (suggestions.length === 0) return;

    card.style.display = 'block';
    body.innerHTML = `
        <div class="mb-2">评分提升：<span style="color:#15803d;font-weight:700;">${data.score_before || 0} → ${data.score_after || 0}</span></div>
        <div>${escapeHtml(suggestions[0])}</div>
    `;
}

// ===== 自动触发分析 =====
const autoAnalyzeTimers = new WeakMap();
const autoAnalyzeControllers = new WeakMap();

function initAutoAnalyze() {
    // 为每个卡片独立防抖，避免不同卡片互相取消
    document.addEventListener('input', function(e) {
        if (e.target.matches('textarea[data-field="content"]')) {
            const card = e.target.closest('.module-card');
            if (card) {
                const btn = card.querySelector('.btn-ai-optimize[data-section]');
                if (btn) {
                    const section = btn.dataset.section;
                    if (section) {
                        scheduleAutoAnalyze(card, section);
                    }
                }
            }
        }
    });
}

function scheduleAutoAnalyze(card, sectionType) {
    const existingTimer = autoAnalyzeTimers.get(card);
    if (existingTimer) {
        clearTimeout(existingTimer);
    }
    const timer = setTimeout(() => runAutoAnalyze(card, sectionType), 3000);
    autoAnalyzeTimers.set(card, timer);
}

async function runAutoAnalyze(card, sectionType) {
    const textarea = card.querySelector('textarea[data-field="content"]');
    const content = textarea?.value?.trim() || '';
    if (content.length < 10) return;

    const targetJob = document.getElementById('input-target-job').value.trim();
    const badge = card.querySelector('[data-quality-badge]');
    if (badge) badge.textContent = '分析中...';
    const previousController = autoAnalyzeControllers.get(card);
    if (previousController) {
        previousController.abort();
    }
    const controller = new AbortController();
    autoAnalyzeControllers.set(card, controller);

    try {
        const response = await fetch('{{ route("user.resumes.optimize-section") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            quotaIntercept: false,
            signal: controller.signal,
            body: JSON.stringify({ section_type: sectionType, content, target_job: targetJob }),
        });
        const data = await response.json();
        if (data.success) {
            updateQualityBadge(card, data.score_after);
            updateAiAnalysisSidebar(data);
            updateStepQuality();
        }
    } catch (e) {
        if (e?.name === 'AbortError') return;
    } finally {
        if (autoAnalyzeControllers.get(card) === controller) {
            autoAnalyzeControllers.delete(card);
        }
    }
}

function updateQualityBadge(card, score) {
    const badge = card.querySelector('[data-quality-badge]');
    if (!badge) return;
    badge.textContent = (score || 0) + '/10';
    badge.className = 'quality-badge ' + (score >= 8 ? 'good' : score >= 5 ? 'medium' : 'poor');
}

// ===== 步骤条质量评级 =====
function updateStepQuality() {
    const stepScores = {};
    for (let step = 2; step <= 6; step++) {
        const section = sectionByStep(step);
        const container = document.getElementById('list-' + section);
        if (!container) continue;
        const badges = container.querySelectorAll('[data-quality-badge]');
        let total = 0, count = 0;
        badges.forEach(b => {
            const text = b.textContent;
            const match = text.match(/(\d+)/);
            if (match) { total += parseInt(match[1]); count++; }
        });
        stepScores[step] = count > 0 ? Math.round(total / count) : 0;
    }

    document.querySelectorAll('.step-node').forEach(el => {
        const step = parseInt(el.dataset.step);
        const existing = el.querySelector('.step-quality');
        if (existing) existing.remove();

        const score = stepScores[step];
        if (score > 0 && step >= 2) {
            const span = document.createElement('span');
            span.className = 'step-quality';
            span.style.cssText = 'position:absolute;top:-8px;right:8px;font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;background:' + (score>=8?'#dcfce7;color:#15803d':score>=5?'#fef3c7;color:#b45309':'#fee2e2;color:#b91c1c') + ';';
            span.textContent = score;
            el.style.position = 'relative';
            el.appendChild(span);
        }
    });
}

// ===== 打字机效果 =====
function typewriterEffect(element, text, speed = 15) {
    return new Promise(resolve => {
        element.textContent = '';
        element.classList.add('typewriter-cursor');
        let i = 0;
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else {
                element.classList.remove('typewriter-cursor');
                resolve();
            }
        }
        type();
    });
}

// ===== Diff 对比 =====
function computeDiff(oldText, newText) {
    // 简化的行级 diff
    const oldLines = oldText.split('\n');
    const newLines = newText.split('\n');
    const result = [];
    const maxLen = Math.max(oldLines.length, newLines.length);
    for (let i = 0; i < maxLen; i++) {
        const oldLine = oldLines[i] || '';
        const newLine = newLines[i] || '';
        if (oldLine === newLine) {
            result.push(escapeHtml(newLine));
        } else if (!newLine) {
            result.push('<span class="diff-del">' + escapeHtml(oldLine) + '</span>');
        } else if (!oldLine) {
            result.push('<span class="diff-ins">' + escapeHtml(newLine) + '</span>');
        } else {
            result.push('<span class="diff-del">' + escapeHtml(oldLine) + '</span> <span class="diff-ins">' + escapeHtml(newLine) + '</span>');
        }
    }
    return result.join('<br>');
}

function renderAiResult(panel, data, textarea, sectionType) {
    const suggestions = data.suggestions || [];
    const suggestionList = suggestions.length
        ? `<ul class="ai-suggestion-list">${suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`
        : '<div class="small text-secondary mb-2">暂无具体建议</div>';

    const originalText = textarea ? textarea.value.trim() : '';
    const diffHtml = computeDiff(originalText, data.optimized_text || '');

    panel.innerHTML = `
        <div class="ai-score-bar">
            <div class="ai-score-item">
                <div class="ai-score-value before">${data.score_before || 0}</div>
                <div class="ai-score-label">优化前</div>
            </div>
            <div class="ai-arrow"><i class="ti ti-arrow-right"></i></div>
            <div class="ai-score-item">
                <div class="ai-score-value after">${data.score_after || 0}</div>
                <div class="ai-score-label">优化后</div>
            </div>
        </div>
        <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">优化建议</div>
        ${suggestionList}
        <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">对比预览</div>
        <div class="ai-optimized-text" id="diff-${sectionType}-${Date.now()}">${diffHtml}</div>
        <div class="ai-action-btns">
            <button type="button" class="ai-btn-ignore" data-action="ignore-ai">忽略</button>
            <button type="button" class="ai-btn-apply" data-action="apply-ai">应用优化</button>
        </div>
    `;

    panel.dataset.optimizedText = data.optimized_text || '';
}

// ===== AI 生成（一句话扩写） =====
async function aiGenerateSection(btn, sectionType) {
    const card = btn.closest('.module-card');
    const textarea = card.querySelector('textarea[data-field="content"]');
    const brief = textarea ? textarea.value.trim() : '';

    if (!brief) {
        showToast('请先填写简要信息，AI 将据此生成完整描述', 'warning');
        return;
    }

    const targetJob = document.getElementById('input-target-job').value.trim();
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>生成中...';

    try {
        const response = await fetch('{{ route("user.resumes.generate-section") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ section_type: sectionType, brief, target_job: targetJob }),
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || '生成失败');

        const panel = card.querySelector('.ai-result-panel');
        panel.classList.remove('d-none');
        const suggestions = data.suggestions || [];
        const suggestionList = suggestions.length
            ? `<ul class="ai-suggestion-list">${suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`
            : '';

        panel.innerHTML = `
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">AI 生成结果</div>
            <div class="ai-optimized-text typewriter-cursor" id="gen-text-${sectionType}-${Date.now()}"></div>
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin: 12px 0 8px;">后续可补充</div>
            ${suggestionList}
            <div class="ai-action-btns">
                <button type="button" class="ai-btn-ignore" data-action="ignore-ai">忽略</button>
                <button type="button" class="ai-btn-apply" data-action="apply-ai">应用</button>
            </div>
        `;

        const textEl = panel.querySelector('.typewriter-cursor');
        await typewriterEffect(textEl, data.generated_text || '');
        panel.dataset.optimizedText = data.generated_text || '';
    } catch (e) {
        if (e?.quotaHandled) {
            return;
        }
        showToast(e.message || '生成失败', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-wand"></i>生成';
    }
}

async function aiGeneratePersonal() {
    const textarea = document.getElementById('input-personal-content');
    const brief = textarea.value.trim();
    if (!brief) { showToast('请先填写简要信息', 'warning'); return; }

    const targetJob = document.getElementById('input-target-job').value.trim();
    const panel = document.getElementById('personal-ai-result');
    const btn = document.querySelector('[data-action="ai-generate-personal"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>生成中...';

    try {
        const response = await fetch('{{ route("user.resumes.generate-section") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ section_type: 'personal', brief, target_job: targetJob }),
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || '生成失败');

        panel.classList.remove('d-none');
        panel.innerHTML = `
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">AI 生成结果</div>
            <div class="ai-optimized-text typewriter-cursor" id="gen-text-personal"></div>
            <div class="ai-action-btns">
                <button type="button" class="ai-btn-ignore" data-action="ignore-ai">忽略</button>
                <button type="button" class="ai-btn-apply" data-action="apply-personal-ai">应用</button>
            </div>
        `;
        const textEl = panel.querySelector('.typewriter-cursor');
        await typewriterEffect(textEl, data.generated_text || '');
        panel.dataset.optimizedText = data.generated_text || '';
    } catch (e) {
        if (e?.quotaHandled) {
            return;
        }
        showToast(e.message || '生成失败', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-wand"></i>AI 生成';
    }
}

async function aiOptimizePersonal() {
    const textarea = document.getElementById('input-personal-content');
    const content = textarea.value.trim();
    if (!content) { showToast('请先填写内容', 'warning'); return; }

    const targetJob = document.getElementById('input-target-job').value.trim();
    const panel = document.getElementById('personal-ai-result');
    const btn = document.querySelector('[data-action="ai-optimize-personal"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>优化中...';

    try {
        const response = await fetch('{{ route("user.resumes.optimize-section") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ section_type: 'personal', content, target_job: targetJob }),
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || '优化失败');

        panel.classList.remove('d-none');
        const suggestions = data.suggestions || [];
        const suggestionList = suggestions.length
            ? `<ul class="ai-suggestion-list">${suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`
            : '';
        const diffHtml = computeDiff(content, data.optimized_text || '');

        panel.innerHTML = `
            <div class="ai-score-bar">
                <div class="ai-score-item"><div class="ai-score-value before">${data.score_before||0}</div><div class="ai-score-label">优化前</div></div>
                <div class="ai-arrow"><i class="ti ti-arrow-right"></i></div>
                <div class="ai-score-item"><div class="ai-score-value after">${data.score_after||0}</div><div class="ai-score-label">优化后</div></div>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">优化建议</div>
            ${suggestionList}
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px;">对比预览</div>
            <div class="ai-optimized-text">${diffHtml}</div>
            <div class="ai-action-btns">
                <button type="button" class="ai-btn-ignore" data-action="ignore-ai">忽略</button>
                <button type="button" class="ai-btn-apply" data-action="apply-personal-ai">应用优化</button>
            </div>
        `;
        panel.dataset.optimizedText = data.optimized_text || '';
    } catch (e) {
        if (e?.quotaHandled) {
            return;
        }
        showToast(e.message || '优化失败', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-sparkles"></i>AI 优化';
    }
}

function applyPersonalAi(btn) {
    const panel = btn.closest('.ai-result-panel');
    const textarea = document.getElementById('input-personal-content');
    const text = panel.dataset.optimizedText;
    if (textarea && text) {
        textarea.value = text;
        formData.personal.content = text;
        saveDraft();
    }
    panel.classList.add('d-none');
    panel.innerHTML = '';
    showToast('已应用', 'success');
}

// ===== 一键优化全部 =====
async function batchOptimizeAll() {
    const btn = document.getElementById('btn-batch-optimize');
    const sections = ['education', 'experience', 'project', 'skill', 'certificate'];
    let optimizedCount = 0;

    // 先优化个人简介
    const personalContent = document.getElementById('input-personal-content')?.value.trim() || '';
    if (personalContent.length >= 10) {
        const targetJob = document.getElementById('input-target-job').value.trim();
        try {
            const response = await fetch('{{ route("user.resumes.optimize-section") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ section_type: 'personal', content: personalContent, target_job: targetJob }),
            });
            const data = await response.json();
            if (data.success && data.optimized_text) {
                document.getElementById('input-personal-content').value = data.optimized_text;
                formData.personal.content = data.optimized_text;
                optimizedCount++;
            }
        } catch (e) {}
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>正在批量优化...';

    for (const section of sections) {
        const container = document.getElementById('list-' + section);
        const cards = container.querySelectorAll('.module-card');
        for (const card of cards) {
            const textarea = card.querySelector('textarea[data-field="content"]');
            const content = textarea?.value?.trim() || '';
            if (content.length < 10) continue;

            const targetJob = document.getElementById('input-target-job').value.trim();
            try {
                const response = await fetch('{{ route("user.resumes.optimize-section") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ section_type: section, content, target_job: targetJob }),
                });
                const data = await response.json();
                if (data.success && data.optimized_text) {
                    saveOptimizeHistory(card, textarea.value, data.optimized_text);
                    textarea.value = data.optimized_text;
                    updateQualityBadge(card, data.score_after);
                    optimizedCount++;
                }
            } catch (e) {}
        }
    }

    syncStepData(currentStep);
    saveDraft();
    updateStepQuality();
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-robot"></i>一键优化全部模块';
    showToast(`已优化 ${optimizedCount} 个模块`, 'success');
}

// ===== 优化历史 =====
function saveOptimizeHistory(card, originalText, optimizedText) {
    const historyPanel = card.querySelector('[data-history-panel]');
    if (!historyPanel) return;

    let history = JSON.parse(historyPanel.dataset.history || '[]');
    history.push({
        original: originalText,
        optimized: optimizedText,
        time: new Date().toLocaleTimeString(),
    });
    if (history.length > 5) history = history.slice(-5);
    historyPanel.dataset.history = JSON.stringify(history);
}

function toggleHistory(btn) {
    const card = btn.closest('.module-card');
    const panel = card.querySelector('[data-history-panel]');
    if (!panel) return;
    panel.classList.toggle('d-none');
    if (!panel.classList.contains('d-none')) {
        renderHistoryPanel(panel);
    }
}

function renderHistoryPanel(panel) {
    const history = JSON.parse(panel.dataset.history || '[]');
    if (history.length === 0) {
        panel.innerHTML = '<div class="small text-secondary text-center py-2">暂无优化历史</div>';
        return;
    }
    panel.innerHTML = '<div style="font-size:11px;font-weight:700;color:#374151;margin-bottom:8px;">优化历史（点击回退）</div>' +
        history.map((h, i) => `<div class="history-item" data-action="apply-history" data-history-index="${i}">
            <div class="d-flex justify-content-between"><span>版本 ${i+1}</span><span class="text-secondary">${h.time}</span></div>
            <div class="text-secondary mt-1" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(h.optimized.substring(0,40))}...</div>
        </div>`).join('');
}

function applyHistoryVersion(el, index) {
    const panel = el.closest('.history-panel');
    const card = panel.closest('.module-card');
    const history = JSON.parse(panel.dataset.history || '[]');
    const item = history[index];
    if (!item) return;

    const textarea = card.querySelector('textarea[data-field="content"]');
    if (textarea) {
        textarea.value = item.original;
        syncStepData(currentStep);
        saveDraft();
    }
    panel.classList.add('d-none');
    showToast('已回退到优化前版本', 'success');
}

function showToast(message, type = 'info') {
    // 使用页面内 alert 或简单提示
    const colors = {
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#2563eb',
    };
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
        background: ${colors[type] || colors.info}; color: #fff;
        padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 500;
        z-index: 9999; box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}
