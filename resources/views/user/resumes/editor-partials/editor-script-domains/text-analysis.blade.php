        moduleTextForAnalyze(mod) {
            if (!mod || typeof mod !== 'object') {
                return '';
            }
            const d = mod.data || {};
            const textParts = [];
            ['name', 'target_job', 'title', 'subtitle', 'content', 'location', 'date'].forEach((key) => {
                if (typeof d[key] === 'string' && d[key].trim() !== '') {
                    textParts.push(d[key].trim());
                }
            });
            if (Array.isArray(d.items)) {
                d.items.forEach((item) => {
                    if (String(item || '').trim() !== '') {
                        textParts.push(String(item).trim());
                    }
                });
            }
            return textParts.join(' ');
        },

        inferModuleTypeByTitle(title) {
            const text = String(title || '').toLowerCase();
            if (/(个人|联系|姓名|基本资料)/.test(text)) return 'personal';
            if (/(求职|意向|目标岗位)/.test(text)) return 'objective';
            if (/(教育|学历|学校)/.test(text)) return 'education';
            if (/(工作|经历|实习|职业)/.test(text)) return 'experience';
            if (/(项目)/.test(text)) return 'project';
            if (/(技能|技术栈|特长)/.test(text)) return 'skill';
            if (/(证书|荣誉|获奖)/.test(text)) return 'certificate';
            if (/(评价|总结|介绍)/.test(text)) return 'summary';
            return 'summary';
        },

        virtualModulesFromRawText(rawText) {
            const rows = String(rawText || '').split('\n');
            const modules = [];
            let current = null;

            const commitCurrent = () => {
                if (!current) {
                    return;
                }
                const data = {
                    title: current.title,
                    subtitle: '',
                    date: '',
                    location: '',
                    content: current.contentLines.join('\n').trim(),
                    items: current.items.filter((item) => String(item || '').trim() !== ''),
                };
                modules.push({
                    id: null,
                    type: current.type,
                    data,
                });
            };

            rows.forEach((rawLine) => {
                const line = String(rawLine || '');
                const sectionMatch = line.match(/^##\s+(.+)$/);
                if (sectionMatch) {
                    commitCurrent();
                    const title = sectionMatch[1].trim();
                    current = {
                        title,
                        type: this.inferModuleTypeByTitle(title),
                        contentLines: [],
                        items: [],
                    };
                    return;
                }

                if (!current) {
                    return;
                }
                const trimmed = line.trim();
                if (trimmed === '') {
                    return;
                }
                if (/^[-*•]\s+/.test(trimmed) || /^\d+\.\s+/.test(trimmed)) {
                    current.items.push(trimmed.replace(/^[-*•]\s+/, '').replace(/^\d+\.\s+/, '').trim());
                } else {
                    current.contentLines.push(trimmed);
                }
            });
            commitCurrent();

            return modules;
        },
