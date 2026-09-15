let pdfJsLib = null;
async function getPdfJs() {
    if (pdfJsLib) return pdfJsLib;
    const module = await import('{{ asset("vendor/pdfjs/pdf.min.js") }}');
    pdfJsLib = module.default ?? module;
    pdfJsLib.GlobalWorkerOptions.workerSrc = '{{ asset("vendor/pdfjs/pdf.worker.min.js") }}';
    return pdfJsLib;
}

function extractPdfText(items) {
    // 1. 按页面坐标排序：先Y后X，解决多栏/表格布局错乱
    const sorted = [...items].filter(it => (it.str || '').trim() !== '').sort((a, b) => {
        const ay = a.transform?.[5] ?? 0;
        const by = b.transform?.[5] ?? 0;
        const ax = a.transform?.[4] ?? 0;
        const bx = b.transform?.[4] ?? 0;
        const yDiff = ay - by;
        if (Math.abs(yDiff) > 2.5) return yDiff;   // 不同行按Y排
        return ax - bx;                              // 同行按X排
    });

    let result = '';
    let lastY = null;
    const threshold = 1.5;
    for (const item of sorted) {
        const str = item.str;
        const currentY = item.transform?.[5] ?? lastY;
        if (lastY !== null && currentY !== null && Math.abs(currentY - lastY) > threshold) {
            result += '\n';
        } else if (item.hasEOL) {
            result += '\n';
        } else if (result !== '' && !result.endsWith('\n')) {
            result += ' ';
        }
        result += str;
        lastY = currentY;
    }
    return result.replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
}

async function importResume(input) {
    const file = input.files[0];
    if (!file) return;
    const status = document.getElementById('import-status');
    const zone = document.getElementById('import-zone');
    try {
        zone.style.opacity = '0.6';
        zone.style.pointerEvents = 'none';
        status.classList.remove('d-none', 'alert-danger', 'alert-success');
        status.classList.add('alert-info');
        status.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>正在解析文件，请稍候...';

        const fileName = (file.name || '').toLowerCase();
        const isDoc = fileName.endsWith('.doc');
        const isDocx = fileName.endsWith('.docx');
        const isPdf = fileName.endsWith('.pdf') || file.type === 'application/pdf';
        const shouldUseServerImport = isDoc || isDocx || isPdf;

        let text = '';
        let importedModules = [];
        let usedStructuredFill = false;
        if (shouldUseServerImport) {
            const serverPayload = await importResumeViaServer(file);
            if (serverPayload?.text) {
                text = serverPayload.text;
            }
            if (Array.isArray(serverPayload?.modules)) {
                importedModules = serverPayload.modules;
            }
            if (serverPayload?.targetJob && !document.getElementById('input-target-job').value.trim()) {
                formData.target_job = String(serverPayload.targetJob).trim();
                document.getElementById('input-target-job').value = formData.target_job;
            }
            if (importedModules.length > 0) {
                usedStructuredFill = applyImportedModulesToFormData(importedModules);
            }
        }

        if (!text && isPdf) {
            if (isPdf) {
                const pdfjs = await getPdfJs();
                const buffer = await file.arrayBuffer();
                const pdf = await pdfjs.getDocument({ data: new Uint8Array(buffer) }).promise;
                const pages = [];
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const tc = await page.getTextContent();
                    const pt = extractPdfText(tc.items);
                    if (pt) pages.push(pt);
                }
                text = pages.join('\n\n').trim();
            } else {
                text = (await file.text()).trim();
            }
        }

        if (!text) {
            throw new Error('未识别到可解析文本，请尝试 DOC / DOCX / 可复制文本 PDF 后重试');
        }

        // 优先按后端结构化模块回填；无结构化结果时才回退文本猜测。
        if (!usedStructuredFill) {
            smartFillFromText(text);
        }

        status.classList.remove('alert-info');
        status.classList.add('alert-success');
        if (status.innerHTML.trim() === '' || status.innerHTML.includes('正在解析文件')) {
            status.innerHTML = '<i class="ti ti-check me-1"></i>导入成功：内容已解析并填充到各模块，请检查并补充完善。';
        }
        showToast('导入成功，请检查各步骤内容', 'success');
        updateProgress();
        updateModuleStatus();
        saveDraft();
    } catch (e) {
        if (e?.quotaHandled) {
            return;
        }
        showToast('导入失败：' + (e?.message || '未知错误'), 'error');
        status.classList.remove('alert-info');
        status.classList.add('alert-danger');
        status.innerHTML = '导入失败：' + (e?.message || '未知错误');
    } finally {
        zone.style.opacity = '1';
        zone.style.pointerEvents = 'auto';
        input.value = '';
    }
}

async function importResumeViaServer(file) {
    const uploadData = new FormData();
    uploadData.append('document', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch('{{ route("user.resumes.import-document-draft") }}', {
        method: 'POST',
        headers: {
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: uploadData,
    });
    const rawBody = await response.text();
    let data = null;
    try {
        data = JSON.parse(rawBody);
    } catch (e) {
        const textOnly = String(rawBody || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        const shortText = textOnly.slice(0, 160);
        const statusLabel = response.status ? `HTTP ${response.status}` : '响应异常';
        throw new Error(shortText ? `${statusLabel}：${shortText}` : `${statusLabel}：服务端返回了非 JSON 内容`);
    }

    if (!response.ok || !data?.success) {
        throw new Error(data?.message || `文件解析失败（HTTP ${response.status}）`);
    }

    return {
        text: String(data.content_raw || '').trim(),
        modules: Array.isArray(data.modules) ? data.modules : [],
        targetJob: String(data.target_job || '').trim(),
    };
}

function applyImportedModulesToFormData(modules) {
    const grouped = {
        education: [],
        experience: [],
        project: [],
        skill: [],
        certificate: [],
    };

    let hasStructuredData = false;
    const firstText = (...values) => {
        for (const value of values) {
            if (typeof value === 'string' && value.trim() !== '') {
                return value.trim();
            }
        }
        return '';
    };
    const composeContent = (data) => {
        const lines = [];
        if (typeof data.content === 'string' && data.content.trim() !== '') {
            lines.push(data.content.trim());
        }
        if (Array.isArray(data.items)) {
            data.items
                .map(item => (typeof item === 'string' ? item.trim() : ''))
                .filter(Boolean)
                .forEach(item => lines.push(item));
        }
        return lines.join('\n').trim();
    };
    const pickFirstByKeys = (data, keys) => {
        for (const key of keys) {
            const value = data?.[key];
            if (typeof value === 'string' && value.trim() !== '') {
                return value.trim();
            }
        }
        return '';
    };
    const buildExtraPersonalFields = (data) => {
        const mapping = [
            { label: '年龄', keys: ['age'] },
            { label: '民族', keys: ['nation', 'ethnicity'] },
            { label: '籍贯', keys: ['hometown', 'native_place'] },
            { label: '政治面貌', keys: ['political_status', 'politics'] },
            { label: '婚姻状况', keys: ['marital_status'] },
            { label: 'QQ', keys: ['qq'] },
        ];
        const fields = [];
        for (const item of mapping) {
            const value = pickFirstByKeys(data, item.keys);
            if (value) {
                fields.push({ label: item.label, value });
            }
        }
        return fields;
    };
    const normalizeLines = (text) => String(text || '')
        .split('\n')
        .map(line => line.trim())
        .filter(Boolean);
    const uniqLines = (lines) => {
        const deduped = [];
        const seen = new Set();
        for (const line of lines) {
            const key = line.replace(/\s+/g, '');
            if (seen.has(key)) continue;
            seen.add(key);
            deduped.push(line);
        }
        return deduped;
    };
    const normalizeSectionItems = (section, items) => {
        if (!Array.isArray(items) || items.length === 0) {
            return [];
        }
        if (section === 'skill' || section === 'certificate') {
            return items
                .map(item => ({ content: String(item?.content || '').trim() }))
                .filter(item => item.content !== '');
        }
        return items
            .map(item => ({
                subtitle: String(item?.subtitle || '').trim(),
                date: String(item?.date || '').trim(),
                location: String(item?.location || '').trim(),
                content: String(item?.content || '').trim(),
            }))
            .filter(item => item.subtitle || item.date || item.location || item.content);
    };
    const isLikelyDateLine = (line) => {
        const text = String(line || '').trim();
        if (!text) return false;
        return /((19|20)\d{2}([./-]\d{1,2})?\s*([~-]|至|到)\s*((19|20)\d{2}([./-]\d{1,2})?|至今|现在|Present|Now)|\b(19|20)\d{2}\b)/i.test(text)
            || /^(时间|日期|周期|任职时间|项目周期)\s*[:：]/i.test(text);
    };
    const isLikelyLocationLine = (line) => {
        const text = String(line || '').trim();
        if (!text) return false;
        return /^(地点|城市|地址|Location|City)\s*[:：]/i.test(text)
            || /(远程|驻场|海外|北京|上海|深圳|广州|杭州|苏州|成都|武汉|西安|南京|长沙|厦门|天津|重庆|郑州|合肥|福州|珠海|东莞|宁波|青岛|香港|澳门|台湾|.+(省|市|区|县))$/.test(text);
    };
    const extractDateValueFromLine = (line) => {
        const text = String(line || '').trim();
        if (!text) return '';
        const normalized = text.replace(/[－–—〜～]/g, '-');
        const labelMatch = normalized.match(/(?:时间|日期|周期|任职时间|项目周期|Duration|Period)\s*[:：]\s*([^\s,，;；]+)/i);
        if (labelMatch && labelMatch[1]) return labelMatch[1].trim();
        const rangeParts = normalized.match(/((?:19|20)\d{2}(?:[./-]\d{1,2})?)\s*(?:-|~|至|到)\s*((?:(?:19|20)\d{2}(?:[./-]\d{1,2})?)|至今|现在|Present|Now)/i);
        if (rangeParts && rangeParts[1] && rangeParts[2]) return `${rangeParts[1].trim()}-${rangeParts[2].trim()}`;
        const rangeMatch = normalized.match(/((?:19|20)\d{2}(?:[./-]\d{1,2})?\s*(?:-|~|至|到)\s*(?:(?:19|20)\d{2}(?:[./-]\d{1,2})?|至今|现在|Present|Now))/i);
        if (rangeMatch && rangeMatch[1]) return rangeMatch[1].trim();
        const allDates = [...normalized.matchAll(/((?:19|20)\d{2}(?:[./-]\d{1,2})?)/g)].map(v => v[1]).filter(Boolean);
        if (allDates.length >= 2 && allDates[0] !== allDates[allDates.length - 1]) {
            return `${allDates[0]}-${allDates[allDates.length - 1]}`;
        }
        const singleMatch = normalized.match(/((?:19|20)\d{2}[./-]\d{1,2})/);
        return singleMatch && singleMatch[1] ? singleMatch[1].trim() : '';
    };
    const extractLocationValueFromLine = (line) => {
        const text = String(line || '').trim();
        if (!text) return '';
        const labelMatch = text.match(/(?:地点|城市|地址|Location|City)\s*[:：]\s*([^\n,，;；]+)/i);
        if (labelMatch && labelMatch[1]) return labelMatch[1].trim();
        const parts = text.split(/\s+/).map(v => v.trim()).filter(Boolean);
        for (const part of parts) {
            if (isLikelyLocationLine(part)) return part;
        }
        return '';
    };
    const stripMetaFromContent = (item) => {
        const lines = normalizeLines(item.content);
        const kept = [];
        let date = String(item.date || '').trim();
        let location = String(item.location || '').trim();

        for (const line of lines) {
            let currentLine = line.trim();

            if (!date && isLikelyDateLine(currentLine)) {
                const dateValue = extractDateValueFromLine(currentLine);
                if (dateValue) {
                    date = dateValue;
                    currentLine = currentLine.replace(dateValue, '').replace(/^(时间|日期|周期|任职时间|项目周期)\s*[:：]\s*/i, '').trim();
                }
            }

            if (!location && isLikelyLocationLine(currentLine)) {
                const locationValue = extractLocationValueFromLine(currentLine);
                if (locationValue) {
                    location = locationValue;
                    currentLine = currentLine.replace(locationValue, '').replace(/^(地点|城市|地址|Location|City)\s*[:：]\s*/i, '').trim();
                }
            }

            if (currentLine) {
                kept.push(currentLine);
            }
        }

        return {
            ...item,
            date,
            location,
            content: kept.join('\n').trim(),
        };
    };

    for (const module of modules) {
        const type = String(module?.type || '').toLowerCase();
        const data = module?.data && typeof module.data === 'object' ? module.data : {};
        if (!type) continue;

        if (type === 'personal') {
            const rawPersonalLines = normalizeLines(composeContent(data));
            const personalExtras = {
                gender: firstText(data.gender, data.sex),
                birthday: firstText(data.birthday, data.birth_date, data.birth),
                wechat: firstText(data.wechat, data.weixin),
                github: firstText(data.github),
                website: firstText(data.portfolio, data.website, data.site, data.blog),
            };
            const extraCustomFields = buildExtraPersonalFields(data);
            const basicFields = [
                firstText(data.name, data.full_name),
                firstText(data.phone, data.mobile, data.tel),
                firstText(data.email, data.mail),
                firstText(data.location, data.city, data.address),
            ].filter(Boolean);
            const basicTokens = basicFields.map(v => String(v).replace(/\s+/g, ''));
            const filteredPersonalLines = rawPersonalLines.filter(line => {
                const token = line.replace(/\s+/g, '');
                if (!token) return false;
                if (basicTokens.includes(token)) return false;
                if (/^(电话|手机|邮箱|邮件|地址|所在地|现居地|城市|location|email|phone)[:：]/i.test(line)) return false;
                return true;
            });
            const personalContentLines = uniqLines([
                ...filteredPersonalLines,
            ]);
            const personal = {
                name: firstText(data.name, data.full_name),
                phone: firstText(data.phone, data.mobile, data.tel),
                email: firstText(data.email, data.mail),
                location: firstText(data.location, data.city, data.address),
                gender: personalExtras.gender,
                birthday: personalExtras.birthday,
                wechat: personalExtras.wechat,
                github: personalExtras.github,
                website: personalExtras.website,
                custom_fields: normalizePersonalCustomFields(extraCustomFields),
                content: personalContentLines.join('\n').trim(),
            };
            if (Object.values(personal).some(v => v !== '')) {
                hasStructuredData = true;
            }
            formData.personal = personal;
            document.getElementById('input-name').value = personal.name;
            document.getElementById('input-phone').value = personal.phone;
            document.getElementById('input-email').value = personal.email;
            document.getElementById('input-location').value = personal.location;
            document.getElementById('input-gender').value = personal.gender || '';
            document.getElementById('input-birthday').value = personal.birthday || '';
            document.getElementById('input-wechat').value = personal.wechat || '';
            document.getElementById('input-github').value = personal.github || '';
            document.getElementById('input-website').value = personal.website || '';
            renderPersonalCustomFields(personal.custom_fields || []);
            document.getElementById('input-personal-content').value = personal.content;
            continue;
        }

        if (type === 'objective') {
            const targetJob = firstText(data.target_job, data.job, data.position, data.content);
            if (targetJob !== '') {
                hasStructuredData = true;
                formData.target_job = targetJob;
                document.getElementById('input-target-job').value = targetJob;
            }
            continue;
        }

        if (type === 'education' || type === 'experience' || type === 'project') {
            let subtitle = '';
            let date = '';
            let location = '';
            const extraLines = [];

            if (type === 'education') {
                const school = firstText(data.school, data.university, data.college, data.institute);
                const major = firstText(data.major, data.specialty, data.program, data.discipline);
                const degree = firstText(data.degree, data.education, data.level);
                subtitle = firstText(
                    school ? [school, major].filter(Boolean).join(' / ') : '',
                    data.subtitle,
                    data.title
                );
                date = firstText(data.date, data.duration, data.period, data.time, data.start_end);
                location = firstText(data.location, data.city, data.campus, data.address);
                if (degree) {
                    extraLines.push(`学历：${degree}`);
                }
            } else if (type === 'experience') {
                subtitle = firstText(
                    [firstText(data.company), firstText(data.position, data.role, data.title)].filter(Boolean).join(' / '),
                    data.subtitle,
                    data.title
                );
                date = firstText(data.date, data.duration, data.period, data.time, data.start_end);
                location = firstText(data.location, data.city, data.address);
            } else {
                subtitle = firstText(data.subtitle, data.project_name, data.name, data.title);
                date = firstText(data.date, data.duration, data.period, data.time, data.start_end);
                location = firstText(data.location, data.url, data.link, data.city);
            }

            const contentLines = uniqLines([
                ...extraLines,
                ...normalizeLines(composeContent(data)),
            ]);
            const item = {
                subtitle,
                date,
                location,
                content: contentLines.join('\n').trim(),
            };
            const normalizedItem = stripMetaFromContent(item);
            if (normalizedItem.subtitle || normalizedItem.date || normalizedItem.location || normalizedItem.content) {
                grouped[type].push(normalizedItem);
                hasStructuredData = true;
            }
            continue;
        }

        if (type === 'skill' || type === 'certificate') {
            const content = composeContent(data);
            if (content !== '') {
                grouped[type].push({ content });
                hasStructuredData = true;
            }
        }
    }

    for (const section of Object.keys(grouped)) {
        formData[section] = normalizeSectionItems(section, grouped[section]);
        restoreListItems(section, formData[section]);
    }

    return hasStructuredData;
}
