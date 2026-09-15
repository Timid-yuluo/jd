        importPreviewComparisonRows() {
            const currentModules = Array.isArray(this.modules) ? this.modules : [];
            const incomingModules = Array.isArray(this.importPreviewModules) ? this.importPreviewModules : [];
            const total = Math.max(currentModules.length, incomingModules.length);
            const rows = [];

            for (let index = 0; index < total; index += 1) {
                const current = currentModules[index] || null;
                const incoming = incomingModules[index] || null;
                let status = 'same';

                if (current && !incoming) {
                    status = 'remove';
                } else if (!current && incoming) {
                    status = 'add';
                } else if (this.normalizeModuleForCompare(current) !== this.normalizeModuleForCompare(incoming)) {
                    status = 'change';
                }

                rows.push({
                    index,
                    current,
                    incoming,
                    status,
                    fieldDiffs: this.importPreviewFieldDiffs(current, incoming),
                });
            }

            return rows;
        },

        importPreviewChangedCount() {
            return this.importPreviewComparisonRows().filter((row) => row.status !== 'same').length;
        },

        importPreviewDiffLabel(status) {
            const labels = {
                same: '无变化',
                change: '有变化',
                add: '新增',
                remove: '删除',
            };

            return labels[status] || '变化';
        },

        importPreviewDiffBadgeClass(status) {
            const classes = {
                same: 'bg-success-lt text-success',
                change: 'bg-primary-lt text-primary',
                add: 'bg-info-lt text-info',
                remove: 'bg-danger-lt text-danger',
            };

            return classes[status] || 'bg-secondary-lt text-secondary';
        },

        importPreviewMetaHintText() {
            if (this.importPreviewMeta?.ocr_used) {
                return '这次导入已自动启用 OCR 识别，适合扫描件或图片型 PDF，但个别字段仍建议人工复核。';
            }

            if (this.importPreviewMeta?.possible_scanned_pdf && !this.importPreviewMeta?.ocr_available) {
                return '当前文件看起来更像扫描件，但服务器未安装 OCR 能力，识别结果可能不完整。';
            }

            return '';
        },

        importPreviewStatEntries() {
            return Object.entries(this.importPreviewModuleStats || {}).map(([type, count]) => ({
                label: this.moduleTypeLabel(type),
                count,
            }));
        },

        normalizeModuleForCompare(mod) {
            if (!mod) {
                return '';
            }

            const data = mod.data || {};
            const normalizedText = (value) => typeof value === 'string' ? value.trim() : '';
            return JSON.stringify({
                type: mod.type || '',
                title: normalizedText(data.title),
                name: normalizedText(data.name),
                target_job: normalizedText(data.target_job),
                subtitle: normalizedText(data.subtitle),
                date: normalizedText(data.date),
                location: normalizedText(data.location),
                content: normalizedText(data.content),
                items: Array.isArray(data.items)
                    ? data.items.map((item) => typeof item === 'string' ? item.trim() : '').filter(Boolean)
                    : [],
            });
        },

        normalizeImportCompareText(value) {
            if (Array.isArray(value)) {
                return value
                    .map((item) => typeof item === 'string' ? item.trim() : '')
                    .filter(Boolean)
                    .join(' / ');
            }

            return typeof value === 'string' ? value.trim() : '';
        },

        importCompareFieldDefinitions(mod) {
            const common = [
                { key: 'title', label: '标题' },
                { key: 'content', label: '内容' },
                { key: 'items', label: '条目' },
            ];

            switch (mod?.type) {
                case 'personal':
                    return [
                        { key: 'name', label: '姓名' },
                        { key: 'phone', label: '电话' },
                        { key: 'email', label: '邮箱' },
                        { key: 'location', label: '地点' },
                    ];
                case 'objective':
                    return [
                        { key: 'target_job', label: '岗位' },
                        { key: 'content', label: '说明' },
                    ];
                case 'education':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'subtitle', label: '学校/专业' },
                        { key: 'date', label: '时间' },
                        { key: 'location', label: '地点' },
                        { key: 'content', label: '说明' },
                        { key: 'items', label: '成果' },
                    ];
                case 'experience':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'subtitle', label: '公司/职位' },
                        { key: 'date', label: '时间' },
                        { key: 'location', label: '地点' },
                        { key: 'content', label: '概述' },
                        { key: 'items', label: '职责/成果' },
                    ];
                case 'project':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'subtitle', label: '项目/角色' },
                        { key: 'date', label: '周期' },
                        { key: 'location', label: '场景' },
                        { key: 'content', label: '概述' },
                        { key: 'items', label: '亮点' },
                    ];
                case 'skill':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'items', label: '技能' },
                    ];
                case 'certificate':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'items', label: '证书/荣誉' },
                    ];
                case 'summary':
                    return [
                        { key: 'title', label: '标题' },
                        { key: 'content', label: '内容' },
                    ];
                default:
                    return common;
            }
        },

        importPreviewFieldDiffs(current, incoming) {
            const currentData = current?.data || {};
            const incomingData = incoming?.data || {};
            const moduleType = incoming?.type || current?.type || '';
            const seedModule = { type: moduleType };

            return this.importCompareFieldDefinitions(seedModule)
                .map((field) => {
                    const currentText = this.normalizeImportCompareText(currentData[field.key]);
                    const incomingText = this.normalizeImportCompareText(incomingData[field.key]);
                    if (currentText === incomingText) {
                        return null;
                    }

                    return {
                        key: field.key,
                        label: field.label,
                        currentText: currentText !== '' ? currentText : '',
                        incomingText: incomingText !== '' ? incomingText : '',
                    };
                })
                .filter(Boolean);
        },
