        buildModuleIssue(index, mod, message, field = null, severity = 'warning', type = 'generic') {
            return {
                index,
                moduleKey: mod?._key ?? String(index),
                moduleType: mod?.type ?? 'unknown',
                moduleLabel: this.moduleTypeLabel(mod?.type || '模块'),
                field,
                severity,
                type,
                message,
            };
        },

        requiredCoreModuleTypes() {
            return ['personal', 'objective', 'experience', 'project'];
        },

        normalizeChecklistText(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/[，。；：、,.!?(){}\[\]<>《》"'\x60~\-_/\\|]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        moduleEvidenceTexts(mod) {
            if (!mod || typeof mod !== 'object') {
                return [];
            }
            const data = mod.data || {};
            const rows = [];
            ['subtitle', 'content'].forEach((field) => {
                if (typeof data[field] === 'string' && data[field].trim() !== '') {
                    rows.push(data[field].trim());
                }
            });
            if (Array.isArray(data.items)) {
                data.items.forEach((item) => {
                    const text = typeof item === 'string' ? item.trim() : '';
                    if (text !== '') {
                        rows.push(text);
                    }
                });
            }
            return rows;
        },

        detectExperienceProjectDuplicates() {
            const candidates = [];
            this.modules.forEach((mod, index) => {
                if (!['experience', 'project'].includes(mod?.type)) {
                    return;
                }
                this.moduleEvidenceTexts(mod).forEach((text) => {
                    const normalized = this.normalizeChecklistText(text);
                    if (normalized.length < 8) {
                        return;
                    }
                    candidates.push({
                        index,
                        text,
                        normalized,
                        type: mod.type,
                    });
                });
            });

            const duplicates = [];
            for (let i = 0; i < candidates.length; i++) {
                for (let j = i + 1; j < candidates.length; j++) {
                    const left = candidates[i];
                    const right = candidates[j];
                    if (left.index === right.index || left.type === right.type) {
                        continue;
                    }
                    if (left.normalized !== right.normalized) {
                        continue;
                    }
                    duplicates.push({
                        leftIndex: left.index,
                        rightIndex: right.index,
                        sample: left.text,
                    });
                }
            }

            return duplicates;
        },

        exportReadinessCheck() {
            const issues = this.buildSaveChecklist();
            const criticalIssues = issues.filter((issue) => issue.severity === 'critical');
            const warningIssues = issues.filter((issue) => issue.severity !== 'critical');
            const pass = criticalIssues.length === 0;
            const focusIssue = criticalIssues[0] || warningIssues[0] || null;

            return {
                pass,
                criticalIssues,
                warningIssues,
                focusIssue,
                message: pass
                    ? (warningIssues.length > 0 ? `当前有 ${warningIssues.length} 个建议优化项，是否继续导出？` : '内容体检已通过，可直接导出。')
                    : `导出前需先处理 ${criticalIssues.length} 个关键项（如目标岗位、核心模块或联系方式缺失）。`,
            };
        },

        buildSaveChecklist() {
            const issues = [];

            if (this.modules.length === 0) {
                issues.push({
                    index: -1,
                    moduleKey: 'empty',
                    moduleType: 'empty',
                    moduleLabel: '简历内容',
                    field: null,
                    severity: 'critical',
                    type: 'empty',
                    message: '当前还没有任何模块，建议先添加基础模块后再保存。',
                });
                return issues;
            }

            const requiredTypes = this.requiredCoreModuleTypes();
            requiredTypes.forEach((type) => {
                if (!this.modules.some((mod) => mod?.type === type)) {
                    issues.push(this.buildModuleIssue(
                        -1,
                        { type },
                        `缺少核心模块：${this.moduleTypeLabel(type)}`,
                        null,
                        'critical',
                        'core_module_missing'
                    ));
                }
            });

            this.modules.forEach((mod, index) => {
                const itemsCount = this.filledItemsCount(mod?.data?.items);
                const contentLength = this.textLength(mod?.data?.content);
                const percent = this.moduleCompletionPercent(mod);

                if (mod?.type === 'personal') {
                    if (!this.hasMeaningfulText(mod, 'name')) {
                        issues.push(this.buildModuleIssue(index, mod, '缺少姓名', 'name', 'critical', 'required'));
                    }
                    if (!this.hasMeaningfulText(mod, 'phone')) {
                        issues.push(this.buildModuleIssue(index, mod, '缺少联系电话', 'phone', 'critical', 'required'));
                    }
                    if (!this.hasMeaningfulText(mod, 'email')) {
                        issues.push(this.buildModuleIssue(index, mod, '缺少邮箱', 'email', 'warning', 'contact'));
                    }
                }

                if (mod?.type === 'objective') {
                    if (!this.hasMeaningfulText(mod, 'target_job')) {
                        issues.push(this.buildModuleIssue(index, mod, '目标岗位未填写', 'target_job', 'critical', 'required'));
                    }
                    if (contentLength > 0 && contentLength < 16) {
                        issues.push(this.buildModuleIssue(index, mod, '求职意向说明偏短，可补充城市、方向或级别', 'content', 'warning', 'short'));
                    }
                }

                if (mod?.type === 'experience' && itemsCount < 2) {
                    issues.push(this.buildModuleIssue(index, mod, '实习经历亮点少于 2 条，建议补职责或量化成果', 'items', 'critical', 'weak'));
                }

                if (mod?.type === 'project' && itemsCount < 2) {
                    issues.push(this.buildModuleIssue(index, mod, '项目亮点少于 2 条，建议补技术贡献或结果', 'items', 'warning', 'weak'));
                }

                if (mod?.type === 'skill' && itemsCount < 3) {
                    issues.push(this.buildModuleIssue(index, mod, '技能关键词偏少，建议补到 3 项以上', 'items', 'warning', 'weak'));
                }

                if (mod?.type === 'summary' && contentLength < 30) {
                    issues.push(this.buildModuleIssue(index, mod, '自我评价偏短，建议补充年限、技术栈和业务经验', 'content', 'warning', 'short'));
                }

                if (percent < 35) {
                    issues.push(this.buildModuleIssue(index, mod, '当前模块整体内容偏少，建议先补齐基础信息', null, 'warning', 'incomplete'));
                }
            });

            const duplicates = this.detectExperienceProjectDuplicates();
            const duplicateIndexSet = new Set();
            duplicates.forEach((dup) => {
                const sample = String(dup.sample || '').slice(0, 30);
                [dup.leftIndex, dup.rightIndex].forEach((index) => {
                    const key = `${index}:${sample}`;
                    if (duplicateIndexSet.has(key)) {
                        return;
                    }
                    duplicateIndexSet.add(key);
                    const mod = this.modules[index];
                    issues.push(this.buildModuleIssue(
                        index,
                        mod,
                        `检测到与${this.moduleTypeLabel(index === dup.leftIndex ? this.modules[dup.rightIndex]?.type : this.modules[dup.leftIndex]?.type)}重复描述：「${sample}」`,
                        'content',
                        'warning',
                        'duplicate'
                    ));
                });
            });

            return issues;
        },

        saveChecklistItems(limit = 4) {
            return this.buildSaveChecklist().slice(0, limit);
        },

        saveChecklistSummaryText() {
            const issues = this.buildSaveChecklist();
            const criticalCount = issues.filter((issue) => issue.severity === 'critical').length;
            if (criticalCount > 0) {
                return `${criticalCount} 个关键项待处理`;
            }

            return `${issues.length} 个建议优化项`;
        },

        saveChecklistGuideText() {
            const issues = this.buildSaveChecklist();
            if (issues.length === 0) {
                return '当前内容已通过基础检查。';
            }

            const criticalCount = issues.filter((issue) => issue.severity === 'critical').length;
            if (criticalCount > 0) {
                return '建议优先处理关键缺项，避免保存后简历仍缺少核心信息。';
            }

            return '当前可以直接保存，也可以先处理下面这些会影响完整度的内容。';
        },

        hasCriticalSaveIssues() {
            return this.buildSaveChecklist().some((issue) => issue.severity === 'critical');
        },

        focusChecklistItem(issue) {
            if (!issue || typeof issue.index !== 'number' || issue.index < 0) {
                return;
            }

            if (issue.field) {
                this.focusFirstIncompleteField(issue.index, issue.field);
                return;
            }

            this.activeIndex = issue.index;
            this.$nextTick(() => {
                const mod = this.modules[issue.index];
                const card = mod ? document.querySelector(`[data-module-key="${mod._key}"]`) : null;
                card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },
