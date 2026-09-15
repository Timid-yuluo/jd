        @include('user.resumes.editor-partials.editor-script-domains.module-completion')
        @include('user.resumes.editor-partials.editor-script-domains.save-checklist')
        @include('user.resumes.editor-partials.editor-script-domains.preview-layout')

        addModuleOfType(type) {
            if (!Array.isArray(this.modules)) {
                this.modules = [];
            }
            if (type === 'personal' && this.modules.some(m => m.type === 'personal')) {
                this.showToast('已存在个人信息模块，请勿重复添加', 'warning');
                return;
            }
            if (type === 'objective' && this.modules.some(m => m.type === 'objective')) {
                this.showToast('已存在求职意向模块，请勿重复添加', 'warning');
                return;
            }
            const defaults = {
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
                    avatar: '',
                },
                objective: { target_job: '', content: '' },
                education: { title: '教育经历', subtitle: '', date: '', location: '', content: '', items: [''], _detail_mode: 'structured', content_raw: '' },
                experience: { title: '实习经历', subtitle: '', date: '', location: '', content: '', items: [''], _detail_mode: 'structured', content_raw: '' },
                project: { title: '项目经验', subtitle: '', date: '', location: '', content: '', items: [''], _detail_mode: 'structured', content_raw: '' },
                skill: { title: '技能证书', content: '', items: [''], _content_mode: 'items' },
                certificate: { title: '获奖情况', content: '', items: [''], _content_mode: 'items' },
                summary: { title: '自我评价', content: '' },
            };
            const newModule = {
                id: null,
                _key: Date.now() + '_' + Math.random().toString(36).slice(2),
                type: type,
                data: JSON.parse(JSON.stringify(defaults[type])),
                sort_order: this.modules.length,
            };
            this.pushUndo();
            this.modules = [...this.modules, newModule];
            this.activeIndex = this.modules.length - 1;
            this.$nextTick(() => {
                const listEl = document.getElementById('module-list');
                const cards = listEl?.querySelectorAll('.card');
                const target = cards?.[this.activeIndex];
                if (target) {
                    target.classList.add('module-added');
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },

        async removeModule(index) {
            const ok = typeof window.appConfirm === 'function'
                ? await window.appConfirm('确定删除该模块吗？', { title: '删除确认', showCancel: true })
                : false;
            if (ok) {
                this.pushUndo();
                this.modules = this.modules.filter((_, i) => i !== index);
                if (this.activeIndex >= this.modules.length) {
                    this.activeIndex = Math.max(0, this.modules.length - 1);
                }
            }
        },

        duplicateModule(index) {
            const source = this.modules[index];
            if (!source) return;
            this.pushUndo();
            const clone = {
                id: null,
                _key: Date.now() + '_' + Math.random().toString(36).slice(2),
                type: source.type,
                data: JSON.parse(JSON.stringify(source.data)),
                sort_order: this.modules.length,
            };
            this.modules = [...this.modules.slice(0, index + 1), clone, ...this.modules.slice(index + 1)];
            this.activeIndex = index + 1;
            this.showToast('模块已复制', 'success');
        },

        moveUp(index) {
            if (index > 0) {
                this.pushUndo();
                const arr = [...this.modules];
                [arr[index], arr[index - 1]] = [arr[index - 1], arr[index]];
                this.modules = arr;
                this.activeIndex = index - 1;
            }
        },

        moveDown(index) {
            if (index < this.modules.length - 1) {
                this.pushUndo();
                const arr = [...this.modules];
                [arr[index], arr[index + 1]] = [arr[index + 1], arr[index]];
                this.modules = arr;
                this.activeIndex = index + 1;
            }
        },

        removeItem(arr, index) {
            return arr.filter((_, i) => i !== index);
        },

        moduleContentMode(mod) {
            const mode = String(mod?.data?._content_mode || '');
            if (mode === 'text' || mode === 'items') {
                return mode;
            }
            return 'items';
        },

        moduleItemsText(mod) {
            const items = Array.isArray(mod?.data?.items) ? mod.data.items : [];
            const lines = items
                .map((item) => String(item || '').trim())
                .filter(Boolean);
            if (lines.length > 0) {
                return lines.join('\n');
            }
            return String(mod?.data?.content || '');
        },

        parseModuleItemsFromText(text) {
            return String(text || '')
                .split(/\r?\n/)
                .map((line) => line.replace(/^[-•\s]+/, '').trim())
                .filter(Boolean);
        },

        setModuleContentMode(index, mode) {
            const mod = this.modules[index];
            if (!mod || !['skill', 'certificate'].includes(String(mod.type || ''))) {
                return;
            }
            const nextMode = mode === 'text' ? 'text' : 'items';
            mod.data._content_mode = nextMode;
            if (nextMode === 'text') {
                if (!String(mod.data.content || '').trim()) {
                    mod.data.content = this.moduleItemsText(mod);
                }
            } else {
                const parsed = this.parseModuleItemsFromText(mod.data.content || '');
                if (parsed.length > 0) {
                    mod.data.items = parsed;
                } else if (!Array.isArray(mod.data.items) || mod.data.items.length === 0) {
                    mod.data.items = [''];
                }
            }
            this.dirty = true;
        },

        setModuleItemsText(index, text) {
            const mod = this.modules[index];
            if (!mod || !['skill', 'certificate'].includes(String(mod.type || ''))) {
                return;
            }
            const nextText = String(text || '');
            mod.data.content = nextText;
            const parsed = this.parseModuleItemsFromText(nextText);
            mod.data.items = parsed.length > 0 ? parsed : [''];
            this.dirty = true;
        },

        moduleDetailMode(mod) {
            const mode = String(mod?.data?._detail_mode || '');
            if (mode === 'raw' || mode === 'structured') {
                return mode;
            }
            return 'structured';
        },

        buildStructuredModuleRawText(mod) {
            const d = mod?.data || {};
            const meta = [d.subtitle, d.date, d.location]
                .map((item) => String(item || '').trim())
                .filter(Boolean)
                .join(' | ');
            const lines = [];
            if (meta) {
                lines.push(meta);
            }
            if (String(d.content || '').trim()) {
                lines.push(String(d.content || '').trim());
            }
            const items = Array.isArray(d.items) ? d.items : [];
            items.forEach((item) => {
                const text = String(item || '').trim();
                if (text) {
                    lines.push(`- ${text}`);
                }
            });
            return lines.join('\n');
        },

        parseStructuredModuleRawText(text) {
            const lines = String(text || '')
                .split(/\r?\n/)
                .map((line) => String(line || '').trim())
                .filter(Boolean);
            const result = {
                subtitle: '',
                date: '',
                location: '',
                content: '',
                items: [],
            };
            if (!lines.length) {
                return result;
            }

            let cursor = 0;
            const firstLine = String(lines[0] || '');
            if (/[|｜]/.test(firstLine)) {
                const chunks = firstLine.split(/[|｜]/).map((part) => String(part || '').trim()).filter(Boolean);
                result.subtitle = chunks[0] || '';
                result.date = chunks[1] || '';
                result.location = chunks[2] || '';
                cursor = 1;
            }

            const contentLines = [];
            for (let i = cursor; i < lines.length; i += 1) {
                const line = String(lines[i] || '').trim();
                if (!line) {
                    continue;
                }
                if (/^[-•*]\s*/.test(line) || /^\d+[).、]\s*/.test(line)) {
                    result.items.push(line.replace(/^[-•*]\s*|^\d+[).、]\s*/, '').trim());
                    continue;
                }
                contentLines.push(line);
            }
            result.content = contentLines.join('\n').trim();
            return result;
        },

        setModuleDetailMode(index, mode) {
            const mod = this.modules[index];
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                return;
            }
            const nextMode = mode === 'raw' ? 'raw' : 'structured';
            mod.data._detail_mode = nextMode;
            if (nextMode === 'raw') {
                if (!String(mod.data.content_raw || '').trim()) {
                    mod.data.content_raw = this.buildStructuredModuleRawText(mod);
                }
            } else {
                this.applyModuleRawTextToStructured(index);
            }
            this.dirty = true;
        },

        applyModuleRawTextToStructured(index) {
            const mod = this.modules[index];
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                return;
            }
            const rawText = String(mod?.data?.content_raw || '');
            if (!rawText.trim()) {
                return;
            }
            const parsed = this.parseStructuredModuleRawText(rawText);
            if (parsed.subtitle) {
                mod.data.subtitle = parsed.subtitle;
            }
            if (parsed.date) {
                mod.data.date = parsed.date;
            }
            if (parsed.location) {
                mod.data.location = parsed.location;
            }
            if (parsed.content) {
                mod.data.content = parsed.content;
            }
            if (Array.isArray(parsed.items) && parsed.items.length > 0) {
                mod.data.items = parsed.items;
            }
            this.dirty = true;
            this.showToast('已将自由文本拆分到结构化字段', 'success');
        },

        moduleRawPreview(mod) {
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                return { subtitle: '', date: '', location: '', content: '', items: [] };
            }
            return this.parseStructuredModuleRawText(mod?.data?.content_raw || '');
        },

        moduleRawPreviewHasData(mod) {
            const preview = this.moduleRawPreview(mod);
            return Boolean(
                String(preview.subtitle || '').trim()
                || String(preview.date || '').trim()
                || String(preview.location || '').trim()
                || String(preview.content || '').trim()
                || (Array.isArray(preview.items) && preview.items.length > 0)
            );
        },

        looksLikeDateText(value) {
            const text = String(value || '').trim();
            if (!text) {
                return false;
            }
            return /((19|20)\d{2}[.\-/年]\d{1,2}([.\-/月]\d{1,2})?日?)|至今|现在|present|current/i.test(text);
        },

        moduleRawPreviewWarnings(mod) {
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                return [];
            }
            const raw = String(mod?.data?.content_raw || '').trim();
            if (!raw) {
                return [];
            }
            const lines = raw.split(/\r?\n/).map((line) => String(line || '').trim()).filter(Boolean);
            if (!lines.length) {
                return [];
            }
            const warnings = [];
            const firstLine = lines[0];
            const hasMetaSplitter = /[|｜]/.test(firstLine);
            const preview = this.moduleRawPreview(mod);
            const hasListSyntax = lines.some((line) => /^[-•*]\s*/.test(line) || /^\d+[).、]\s*/.test(line));

            if (!hasMetaSplitter) {
                warnings.push('建议首行使用“副标题 | 时间 | 地点/链接”格式，拆分更准确。');
            }
            if (!String(preview.subtitle || '').trim()) {
                warnings.push('未识别到副标题，建议在首行左侧补充学校/公司/项目角色。');
            }
            if (!String(preview.date || '').trim()) {
                warnings.push('未识别到时间，建议补充如“2024.03 - 2024.05”或“至今”。');
            } else if (!this.looksLikeDateText(preview.date)) {
                warnings.push('时间格式可能不标准，建议使用“YYYY.MM - YYYY.MM”格式。');
            }
            if (!hasListSyntax && (!Array.isArray(preview.items) || preview.items.length === 0)) {
                warnings.push('未识别到条目，建议使用“- ”或“1. ”开头列出关键成果。');
            }
            return warnings.slice(0, 3);
        },

        normalizeDateRangeText(value) {
            let text = String(value || '').trim();
            if (!text) {
                return '';
            }
            text = text
                .replace(/年/g, '.')
                .replace(/月/g, '.')
                .replace(/日/g, '')
                .replace(/\//g, '.')
                .replace(/\s*~\s*/g, ' - ')
                .replace(/\s*～\s*/g, ' - ')
                .replace(/\s*—\s*/g, ' - ')
                .replace(/\s*至\s*/g, ' - ')
                .replace(/\s+/g, ' ')
                .trim();
            return text;
        },

        buildAutoFixedModuleRawText(rawText) {
            const raw = String(rawText || '');
            const lines = raw
                .split(/\r?\n/)
                .map((line) => String(line || '').trim())
                .filter(Boolean);
            if (!lines.length) {
                return '';
            }

            const firstLine = String(lines[0] || '');
            const hasMetaSplitter = /[|｜]/.test(firstLine);
            if (hasMetaSplitter) {
                const chunks = firstLine
                    .split(/[|｜]/)
                    .map((part) => String(part || '').trim())
                    .filter(Boolean);
                if (chunks.length >= 2) {
                    chunks[1] = this.normalizeDateRangeText(chunks[1]);
                }
                lines[0] = chunks.join(' | ');
            } else {
                lines[0] = this.normalizeDateRangeText(firstLine);
            }

            const body = lines.slice(1);
            const hasExplicitItems = body.some((line) => /^[-•*]\s*/.test(line) || /^\d+[).、]\s*/.test(line));
            const normalizedBody = [];
            body.forEach((line, idx) => {
                const safe = String(line || '').trim();
                if (!safe) {
                    return;
                }
                if (/^\d+[).、]\s*/.test(safe)) {
                    normalizedBody.push(`- ${safe.replace(/^\d+[).、]\s*/, '').trim()}`);
                    return;
                }
                if (/^[-•*]\s*/.test(safe)) {
                    normalizedBody.push(`- ${safe.replace(/^[-•*]\s*/, '').trim()}`);
                    return;
                }
                if (!hasExplicitItems && idx > 0) {
                    normalizedBody.push(`- ${safe}`);
                    return;
                }
                normalizedBody.push(safe);
            });
            return [lines[0], ...normalizedBody].join('\n').trim();
        },

        previewAutoFixModuleRawText(index) {
            const mod = this.modules[index];
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                return;
            }
            const before = String(mod?.data?.content_raw || '').trim();
            if (!before) {
                this.showToast('暂无可修正内容', 'info');
                return;
            }
            const after = this.buildAutoFixedModuleRawText(before);
            this.rawAutoFixPreview = {
                visible: true,
                index,
                before,
                after: after || before,
            };
        },

        closeRawAutoFixPreview() {
            this.rawAutoFixPreview = {
                visible: false,
                index: null,
                before: '',
                after: '',
            };
        },

        confirmAutoFixModuleRawText() {
            const preview = this.rawAutoFixPreview || {};
            const index = Number(preview.index);
            const mod = this.modules[index];
            if (!mod || !['education', 'experience', 'project'].includes(String(mod.type || ''))) {
                this.closeRawAutoFixPreview();
                return;
            }
            const before = String(preview.before || '');
            const after = String(preview.after || '');
            if (!after.trim() || before.trim() === after.trim()) {
                this.closeRawAutoFixPreview();
                this.showToast('未发现可应用的格式修正', 'info');
                return;
            }
            mod.data.content_raw = after;
            this.dirty = true;
            this.showToast('已自动修正文本格式', 'success');
            this.closeRawAutoFixPreview();
        },

        normalizePersonalCustomFields(fields) {
            if (!Array.isArray(fields)) {
                return [];
            }
            const rows = [];
            const seen = new Set();
            fields.forEach((field) => {
                const label = String(field?.label || '').trim();
                const value = String(field?.value || '').trim();
                if (!label || !value) {
                    return;
                }
                const key = `${label}::${value}`.toLowerCase();
                if (seen.has(key)) {
                    return;
                }
                seen.add(key);
                rows.push({ label, value });
            });
            return rows;
        },

        personalCustomFields(mod) {
            const rows = Array.isArray(mod?.data?.custom_fields) ? mod.data.custom_fields : [];
            return rows.map((field) => ({
                label: String(field?.label || ''),
                value: String(field?.value || ''),
            }));
        },

        addPersonalCustomField(index) {
            const mod = this.modules[index];
            if (!mod || mod.type !== 'personal') {
                return;
            }
            const current = this.personalCustomFields(mod);
            mod.data.custom_fields = [...current, { label: '', value: '' }];
            this.dirty = true;
        },

        removePersonalCustomField(index, fieldIndex) {
            const mod = this.modules[index];
            if (!mod || mod.type !== 'personal') {
                return;
            }
            const current = this.personalCustomFields(mod);
            mod.data.custom_fields = current.filter((_, i) => i !== fieldIndex);
            this.dirty = true;
        },

        moduleDisplayDefaultsByType(type) {
            const base = {
                showBadge: false,
                badgeText: '',
                showDivider: true,
                showSubtitle: true,
                showDate: true,
                showLocation: true,
                itemMarker: 'dot',
            };
            const map = {
                personal: { ...base, showDivider: false, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                objective: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                summary: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'none' },
                skill: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'dot' },
                certificate: { ...base, showSubtitle: false, showDate: false, showLocation: false, itemMarker: 'dot' },
            };
            return map[String(type || '')] || base;
        },

        moduleDisplayConfig(mod) {
            const defaults = this.moduleDisplayDefaultsByType(mod?.type);
            const current = mod?.data?._display && typeof mod.data._display === 'object'
                ? mod.data._display
                : {};
            return {
                ...defaults,
                ...current,
                badgeText: String(current.badgeText || ''),
                itemMarker: ['dot', 'dash', 'none'].includes(String(current.itemMarker || defaults.itemMarker))
                    ? String(current.itemMarker || defaults.itemMarker)
                    : defaults.itemMarker,
                layoutColumn: ['auto', 'left', 'right', 'full'].includes(String(current.layoutColumn || 'auto'))
                    ? String(current.layoutColumn || 'auto')
                    : 'auto',
                headingFontSize: current.headingFontSize || '',
                headingColor: current.headingColor || '',
                accentColor: current.accentColor || '',
                sectionSpacing: current.sectionSpacing || '',
                bodyFontColor: current.bodyFontColor || '',
                bodyFontSize: current.bodyFontSize || '',
                bodyLineHeight: current.bodyLineHeight || '',
            };
        },

        moduleSupportsMetaField(mod, fieldKey) {
            const type = String(mod?.type || '');
            if (!['education', 'experience', 'project', 'certificate'].includes(type)) {
                return false;
            }
            return ['subtitle', 'date', 'location'].includes(String(fieldKey || ''));
        },

        moduleSupportsItems(mod) {
            return Array.isArray(mod?.data?.items);
        },

        moduleDefaultBadgeText(mod) {
            const type = String(mod?.type || '');
            const map = {
                objective: '核心',
                education: '教育',
                experience: '经历',
                project: '项目',
                skill: '技能',
                certificate: '证书',
                summary: '亮点',
            };
            return map[type] || (this.moduleTypeLabel(type) || '模块');
        },

        setModuleDisplayConfig(index, key, value) {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }
            const next = JSON.parse(JSON.stringify(mod));
            const current = this.moduleDisplayConfig(mod);
            next.data = next.data && typeof next.data === 'object' ? next.data : {};
            next.data._display = {
                ...current,
                [key]: value,
            };
            this.modules.splice(index, 1, next);
            this.activeIndex = index;
            this.dirty = true;
        },

        clearModuleDisplayConfig(index) {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }
            const next = JSON.parse(JSON.stringify(mod));
            next.data = next.data && typeof next.data === 'object' ? next.data : {};
            delete next.data._display;
            this.modules.splice(index, 1, next);
            this.activeIndex = index;
            this.dirty = true;
            this.showToast('模块排版已恢复为默认', 'info');
        },

        moduleStyleOverride(mod) {
            const d = mod?.data?._display;
            if (!d) return '';
            let s = '';
            if (d.sectionSpacing) s += `margin-bottom: ${d.sectionSpacing}px;`;
            if (d.headingFontSize) s += `--heading-fs: ${d.headingFontSize}em;`;
            if (d.headingColor) s += `--heading-c: ${d.headingColor};`;
            if (d.accentColor) s += `--accent-c: ${d.accentColor};`;
            if (d.bodyFontColor) s += `--body-c: ${d.bodyFontColor};`;
            if (d.bodyFontSize) s += `--body-fs: ${d.bodyFontSize}px;`;
            if (d.bodyLineHeight) s += `--body-lh: ${d.bodyLineHeight};`;
            return s;
        },

        @include('user.resumes.editor-partials.editor-script-domains.module-snapshot-diff')
        @include('user.resumes.editor-partials.editor-script-domains.undo-redo')

        toggleCollapse(key) {
            if (this.collapsedModules.has(key)) {
                this.collapsedModules.delete(key);
            } else {
                this.collapsedModules.add(key);
            }
        },

        isCollapsed(key) {
            return this.collapsedModules.has(key);
        },

        allCollapsed() {
            return this.modules.length > 0 && this.modules.every(m => this.collapsedModules.has(m._key));
        },

        toggleCollapseAll() {
            if (this.allCollapsed()) {
                this.collapsedModules = new Set();
            } else {
                this.collapsedModules = new Set(this.modules.map(m => m._key));
            }
        },

        wordCount() {
            let text = '';
            this.modules.forEach(m => {
                const d = m.data;
                if (d.name) text += d.name + ' ';
                if (d.phone) text += d.phone + ' ';
                if (d.email) text += d.email + ' ';
                if (d.gender) text += d.gender + ' ';
                if (d.birthday) text += d.birthday + ' ';
                if (d.wechat) text += d.wechat + ' ';
                if (d.github) text += d.github + ' ';
                if (d.website) text += d.website + ' ';
                if (d.target_job) text += d.target_job + ' ';
                if (d.title) text += d.title + ' ';
                if (d.subtitle) text += d.subtitle + ' ';
                if (d.content) text += d.content + ' ';
                if (d.location) text += d.location + ' ';
                if (Array.isArray(d.custom_fields)) {
                    d.custom_fields.forEach((field) => {
                        if (field?.label) text += `${field.label} `;
                        if (field?.value) text += `${field.value} `;
                    });
                }
                if (Array.isArray(d.items)) text += d.items.join(' ') + ' ';
            });
            const chars = text.replace(/\s+/g, '').length;
            const words = text.trim() ? text.trim().split(/\s+/).length : 0;
            return { chars, words };
        },

        modulesToRawText() {
            const lines = [];
            for (const m of this.modules) {
                const d = m.data;
                if (m.type === 'personal') {
                    lines.push('## 个人信息');
                    if (d.name) lines.push(d.name);
                    if (d.phone) lines.push('电话: ' + d.phone);
                    if (d.email) lines.push('邮箱: ' + d.email);
                    if (d.location) lines.push('城市: ' + d.location);
                    if (d.gender) lines.push('性别: ' + d.gender);
                    if (d.birthday) lines.push('生日: ' + d.birthday);
                    if (d.wechat) lines.push('微信: ' + d.wechat);
                    if (d.github) lines.push('GitHub: ' + d.github);
                    if (d.website) lines.push('作品集: ' + d.website);
                    if (Array.isArray(d.custom_fields)) {
                        d.custom_fields.forEach((field) => {
                            const label = String(field?.label || '').trim();
                            const value = String(field?.value || '').trim();
                            if (label && value) {
                                lines.push(`${label}: ${value}`);
                            }
                        });
                    }
                    if (d.content) lines.push(d.content);
                    lines.push('');
                    continue;
                }
                if (m.type === 'objective') {
                    lines.push('## 求职意向');
                    if (d.target_job) lines.push('目标岗位: ' + d.target_job);
                    if (d.content) lines.push(d.content);
                    lines.push('');
                    continue;
                }
                if (d.title) lines.push('## ' + d.title);
                const meta = [];
                if (d.subtitle) meta.push(d.subtitle);
                if (d.date) meta.push(d.date);
                if (d.location) meta.push(d.location);
                if (meta.length) lines.push(meta.join(' | '));
                if (d.content) lines.push(d.content);
                if (Array.isArray(d.items)) d.items.forEach(item => { if (item.trim()) lines.push('- ' + item); });
                lines.push('');
            }
            return lines.join('\n');
        },

        moduleToText(mod) {
            if (!mod) return '';
            const d = mod.data || {};
            const lines = [];
            const typeLabels = {personal:'个人信息',objective:'求职意向',education:'教育经历',experience:'实习经历',project:'项目经验',skill:'技能特长',certificate:'证书荣誉',summary:'个人简介'};
            lines.push('## ' + (typeLabels[mod.type] || mod.type));

            if (mod.type === 'personal') {
                if (d.name) lines.push(d.name);
                if (d.phone) lines.push('电话: ' + d.phone);
                if (d.email) lines.push('邮箱: ' + d.email);
                if (d.school) lines.push('学校: ' + d.school);
                if (d.major) lines.push('专业: ' + d.major);
                if (d.content) lines.push(d.content);
            } else if (mod.type === 'objective') {
                if (d.target_job) lines.push('目标岗位: ' + d.target_job);
                if (d.content) lines.push(d.content);
            } else if (Array.isArray(d.items) && d.items.length > 0) {
                if (typeof d.items[0] === 'object' && d.items[0] !== null) {
                    d.items.forEach(item => {
                        const parts = [item.school, item.company, item.name, item.title, item.position, item.degree, item.major].filter(Boolean);
                        if (parts.length) lines.push(parts.join(' · '));
                        const period = [item.start_date, item.end_date].filter(Boolean).join(' - ');
                        if (period) lines.push(period);
                        if (item.description) lines.push(item.description);
                        if (item.tech_stack) lines.push('技术栈: ' + item.tech_stack);
                    });
                } else {
                    d.items.forEach(item => { if (String(item).trim()) lines.push('- ' + item); });
                }
            } else {
                if (d.content) lines.push(d.content);
            }
            return lines.join('\n');
        },

        modulesForSubmit() {
            return this.modules.map((m, i) => ({
                id: m.id,
                type: m.type,
                data: m.data,
                sort_order: i,
            }));
        },

        buildModulesSignature() {
            return JSON.stringify(this.modulesForSubmit());
        },

        buildOptimizeSignature() {
            const goals = Array.isArray(this.optimizeGoals) ? [...this.optimizeGoals].sort() : [];
            const focusKeywords = Array.isArray(this.selectedJdKeywords) ? [...this.selectedJdKeywords].sort() : [];
            return JSON.stringify({
                modules: this.modulesForSubmit(),
                module_strategies: this.moduleStrategies || {},
                target_job: (this.streamTargetJob || '').trim(),
                target_company: (this.streamTargetCompany || '').trim(),
                target_job_title: (this.streamTargetJobTitle || '').trim(),
                target_job_description: (this.streamTargetJobDescription || '').trim(),
                optimize_goals: goals,
                optimize_mode: this.optimizeMode || 'balanced',
                focus_keywords: focusKeywords,
                prompt_strategy_template: this.promptStrategyTemplate || 'general',
            });
        },

        hasTargetJob() {
            return (this.streamTargetJob || '').trim() !== ''
                || (this.streamTargetCompany || '').trim() !== ''
                || (this.streamTargetJobTitle || '').trim() !== ''
                || (this.streamTargetJobDescription || '').trim() !== '';
        },

        @include('user.resumes.editor-partials.editor-script-domains.optimize-strategy')
        @include('user.resumes.editor-partials.editor-script-domains.keyword-extract')
        @include('user.resumes.editor-partials.editor-script-domains.module-score')
