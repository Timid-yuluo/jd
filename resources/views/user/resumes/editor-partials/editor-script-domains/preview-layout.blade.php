        scrollToModule(index, target = 'editor') {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }

            this.activeIndex = index;

            if (target === 'preview') {
                this.$nextTick(() => {
                    const previewNode = document.querySelector(`[data-mod-index="${index}"]`);
                    previewNode?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                return;
            }

            const collapsed = new Set(this.collapsedModules);
            collapsed.delete(mod._key);
            this.collapsedModules = collapsed;

            this.$nextTick(() => {
                const card = document.querySelector(`[data-module-key="${mod._key}"]`);
                card?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        focusFirstIncompleteField(index, preferredField = null) {
            const mod = this.modules[index];
            if (!mod) {
                return;
            }

            const missingKeys = this.moduleMissingFieldKeys(mod);
            const targetField = preferredField || missingKeys[0];
            if (!targetField) {
                this.showToast('这个模块已经比较完整了', 'info');
                return;
            }

            this.activeIndex = index;
            const collapsed = new Set(this.collapsedModules);
            collapsed.delete(mod._key);
            this.collapsedModules = collapsed;

            this.$nextTick(() => {
                const card = document.querySelector(`[data-module-key="${mod._key}"]`);
                if (!card) {
                    return;
                }

                let target = card.querySelector(`[data-field="${targetField}"]`);
                if (!target && targetField === 'items') {
                    const addButton = card.querySelector('[data-add-item="true"]');
                    if (addButton) {
                        addButton.click();
                        target = card.querySelector('[data-field="items"]');
                    }
                }

                if (!target) {
                    return;
                }

                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof target.focus === 'function') {
                    target.focus();
                }
                if (typeof target.select === 'function') {
                    target.select();
                }
            });
        },

        resolvedTitleStyleVariant() {
            const selected = String(this.titleStyleVariant || 'template');
            if (selected !== 'template') {
                return selected;
            }

            const map = {
                classic: 'professional',
                modern: 'professional',
                minimal: 'minimal',
                timeline: 'professional',
                creative: 'creative',
                elegant: 'executive',
            };

            return map[String(this.template || 'classic')] || 'professional';
        },

        titleStyleCssVars() {
            const map = {
                professional: {
                    weight: '700',
                    spacing: '0.02em',
                    transform: 'none',
                    ruleWidth: '2px',
                    ruleColor: 'var(--accent-c, #2563eb)',
                    rulePadding: '5px',
                },
                minimal: {
                    weight: '600',
                    spacing: '0.12em',
                    transform: 'uppercase',
                    ruleWidth: '1px',
                    ruleColor: '#e5e7eb',
                    rulePadding: '8px',
                },
                executive: {
                    weight: '600',
                    spacing: '0.12em',
                    transform: 'uppercase',
                    ruleWidth: '1px',
                    ruleColor: '#d7dee7',
                    rulePadding: '8px',
                },
                creative: {
                    weight: '700',
                    spacing: '0.01em',
                    transform: 'none',
                    ruleWidth: '0px',
                    ruleColor: 'transparent',
                    rulePadding: '0px',
                },
                compact: {
                    weight: '700',
                    spacing: '0',
                    transform: 'none',
                    ruleWidth: '1px',
                    ruleColor: '#dbe3ee',
                    rulePadding: '4px',
                },
            };

            const preset = map[this.resolvedTitleStyleVariant()] || map.professional;

            return `
                --rs-title-weight: ${preset.weight};
                --rs-title-spacing: ${preset.spacing};
                --rs-title-transform: ${preset.transform};
                --rs-title-rule-width: ${preset.ruleWidth};
                --rs-title-rule-color: ${preset.ruleColor};
                --rs-title-rule-padding: ${preset.rulePadding};
            `;
        },

        applyTitleStyleVariant(variant = 'template') {
            this.titleStyleVariant = String(variant || 'template');
            this.layoutProfile = 'custom';
            this.dirty = true;
            this.showToast('已应用标题规范', 'success');
        },

        defaultModuleLayoutForTemplate(type, templateOverride = '') {
            const template = String(templateOverride || this.template || 'classic');
            const typeKey = String(type || '');

            const defaults = {
                classic: {
                    personal: 'full',
                    objective: 'full',
                    summary: 'full',
                    skill: 'right',
                    certificate: 'right',
                    education: 'left',
                    experience: 'left',
                    project: 'right',
                },
                minimal: {
                    personal: 'full',
                    objective: 'full',
                    summary: 'full',
                    skill: 'right',
                    certificate: 'right',
                    education: 'left',
                    experience: 'left',
                    project: 'right',
                },
                creative: {
                    personal: 'full',
                    objective: 'full',
                    skill: 'full',
                    summary: 'full',
                    education: 'left',
                    experience: 'left',
                    project: 'right',
                    certificate: 'right',
                },
                modern: {
                    personal: 'left',
                    skill: 'left',
                    summary: 'left',
                    objective: 'right',
                    education: 'right',
                    experience: 'right',
                    project: 'right',
                    certificate: 'right',
                },
                elegant: {
                    objective: 'left',
                    experience: 'left',
                    project: 'left',
                    education: 'right',
                    skill: 'right',
                    certificate: 'right',
                    summary: 'right',
                    personal: 'full',
                },
                timeline: {
                    personal: 'full',
                    objective: 'full',
                    summary: 'full',
                    experience: 'left',
                    project: 'left',
                    education: 'left',
                    skill: 'right',
                    certificate: 'right',
                },
            };

            return defaults[template]?.[typeKey] || 'auto';
        },

        moduleLayoutValue(mod, templateOverride = '') {
            const explicit = String(mod?.data?._display?.layoutColumn || '').trim();
            if (explicit && explicit !== 'auto') {
                return explicit;
            }

            return this.defaultModuleLayoutForTemplate(mod?.type, templateOverride);
        },

        moduleGridColumnStyle(mod, options = {}) {
            const layout = this.moduleLayoutValue(mod, options.template || '');
            const fallbackFullTypes = Array.isArray(options.defaultFullTypes) ? options.defaultFullTypes : [];

            if (layout === 'full' || (layout === 'auto' && fallbackFullTypes.includes(String(mod?.type || '')))) {
                return 'grid-column: 1 / -1;';
            }
            if (layout === 'left') {
                return 'grid-column: 1;';
            }
            if (layout === 'right') {
                return 'grid-column: 2;';
            }

            return '';
        },

        clampedLeftColumnRatio() {
            const raw = Number(this.leftColumnRatio || 50);
            if (Number.isNaN(raw)) {
                return 50;
            }

            return Math.max(35, Math.min(65, raw));
        },

        rightColumnRatio() {
            return 100 - this.clampedLeftColumnRatio();
        },

        dualColumnGridStyle(gap = '18px') {
            return `display: grid; grid-template-columns: minmax(0, ${this.clampedLeftColumnRatio()}%) minmax(0, ${this.rightColumnRatio()}%); gap: ${gap}; align-items: start;`;
        },

        splitColumnStyle(column) {
            const left = this.clampedLeftColumnRatio();
            const right = this.rightColumnRatio();

            if (column === 'left') {
                return `flex: 0 0 ${left}%; max-width: ${left}%;`;
            }
            if (column === 'right') {
                return `flex: 0 0 ${right}%; max-width: ${right}%;`;
            }

            return '';
        },

        applyColumnRatioPreset(ratio = 50) {
            this.leftColumnRatio = String(Math.max(35, Math.min(65, Number(ratio || 50))));
            this.layoutProfile = 'custom';
            this.dirty = true;
            this.showToast('已调整栏位占比', 'success');
        },

        moduleBelongsToColumn(mod, column, templateOverride = '') {
            const layout = this.moduleLayoutValue(mod, templateOverride);

            if (column === 'left') {
                return layout === 'left';
            }
            if (column === 'right') {
                return layout === 'right' || layout === 'full';
            }
            if (column === 'full') {
                return layout === 'full';
            }

            return false;
        },

        moduleLayoutBadgeText(mod) {
            const explicit = String(mod?.data?._display?.layoutColumn || '').trim();
            if (!explicit || explicit === 'auto') {
                return '';
            }

            const map = {
                left: '左栏',
                right: '右栏',
                full: '通栏',
            };

            return map[explicit] || '';
        },

        moduleLayoutGroupLabel(column) {
            const map = {
                full: '通栏区',
                left: '左栏区',
                right: '右栏区',
                auto: '自动区',
            };

            return map[String(column || '')] || '自动区';
        },

        moduleLayoutGroups() {
            const buckets = {
                full: [],
                left: [],
                right: [],
                auto: [],
            };

            this.modules.forEach((mod, index) => {
                const explicit = String(mod?.data?._display?.layoutColumn || 'auto').trim() || 'auto';
                const bucketKey = ['full', 'left', 'right'].includes(explicit) ? explicit : 'auto';
                buckets[bucketKey].push({
                    index,
                    key: mod?._key || `mod-${index}`,
                    label: this.moduleTypeLabel(mod?.type),
                    title: this.moduleSummary(mod) || this.moduleTypeLabel(mod?.type),
                    type: mod?.type || '',
                });
            });

            return [
                { key: 'full', label: this.moduleLayoutGroupLabel('full'), items: buckets.full },
                { key: 'left', label: this.moduleLayoutGroupLabel('left'), items: buckets.left },
                { key: 'right', label: this.moduleLayoutGroupLabel('right'), items: buckets.right },
                { key: 'auto', label: this.moduleLayoutGroupLabel('auto'), items: buckets.auto },
            ];
        },

        batchSetModuleLayouts(indexes = [], layout = 'auto') {
            const targetLayout = ['auto', 'left', 'right', 'full'].includes(String(layout || 'auto'))
                ? String(layout || 'auto')
                : 'auto';
            const targetIndexes = new Set((Array.isArray(indexes) ? indexes : []).map((index) => Number(index)));

            if (!targetIndexes.size) {
                return;
            }

            const nextModules = this.modules.map((mod, index) => {
                if (!targetIndexes.has(index)) {
                    return mod;
                }

                const next = JSON.parse(JSON.stringify(mod));
                const currentDisplay = this.moduleDisplayConfig(mod);
                next.data = next.data && typeof next.data === 'object' ? next.data : {};
                next.data._display = {
                    ...currentDisplay,
                    layoutColumn: targetLayout,
                };

                if (targetLayout === 'auto') {
                    delete next.data._display.layoutColumn;
                }

                return next;
            });

            this.modules = nextModules;
            this.dirty = true;
            this.showToast('已批量更新模块栏位', 'success');
        },

        startLayoutDrag(index) {
            this.layoutDragIndex = Number(index);
        },

        endLayoutDrag() {
            this.layoutDragIndex = null;
        },

        moveDraggedModuleToLayout(layout = 'auto') {
            if (this.layoutDragIndex === null || this.layoutDragIndex === undefined) {
                return;
            }

            const index = Number(this.layoutDragIndex);
            this.batchSetModuleLayouts([index], layout);
            this.activeIndex = index;
            this.layoutDragIndex = null;
        },

        applyModuleLayoutPlan(resolver) {
            const nextModules = this.modules.map((mod, index) => {
                const next = JSON.parse(JSON.stringify(mod));
                const currentDisplay = this.moduleDisplayConfig(mod);
                const planned = String(resolver(next, index) || 'auto');

                next.data = next.data && typeof next.data === 'object' ? next.data : {};
                next.data._display = {
                    ...currentDisplay,
                    layoutColumn: planned,
                };

                if (planned === 'auto') {
                    delete next.data._display.layoutColumn;
                }

                return next;
            });

            this.modules = nextModules;
            this.dirty = true;
        },

        applyResumeProfile(profile = 'campus') {
            const selected = String(profile || 'campus');
            this.layoutProfile = selected;

            if (selected === 'campus') {
                this.template = 'modern';
                this.theme = 'blue';
                this.leftColumnRatio = '38';
                this.fontSize = '13.5';
                this.lineHeight = '1.7';
                this.headingFontSize = '1.11';
                this.sectionSpacing = '18';
                this.titleStyleVariant = 'professional';
                this.applyModuleLayoutPlan((mod) => {
                    const type = String(mod?.type || '');
                    const map = {
                        personal: 'left',
                        skill: 'left',
                        summary: 'left',
                        objective: 'right',
                        education: 'right',
                        experience: 'right',
                        project: 'right',
                        certificate: 'right',
                    };

                    return map[type] || 'auto';
                });
            } else if (selected === 'experienced') {
                this.template = 'elegant';
                this.theme = 'blue';
                this.leftColumnRatio = '62';
                this.fontSize = '13.5';
                this.lineHeight = '1.7';
                this.headingFontSize = '1.11';
                this.sectionSpacing = '18';
                this.titleStyleVariant = 'executive';
                this.applyModuleLayoutPlan((mod) => {
                    const type = String(mod?.type || '');
                    const map = {
                        personal: 'full',
                        objective: 'left',
                        experience: 'left',
                        project: 'left',
                        education: 'right',
                        skill: 'right',
                        certificate: 'right',
                        summary: 'right',
                    };

                    return map[type] || 'auto';
                });
            } else if (selected === 'onepage') {
                this.template = 'creative';
                this.theme = 'blue';
                this.leftColumnRatio = '50';
                this.fontSize = '12.5';
                this.lineHeight = '1.5';
                this.headingFontSize = '1.02';
                this.sectionSpacing = '12';
                this.titleStyleVariant = 'compact';
                this.applyModuleLayoutPlan((mod) => {
                    const type = String(mod?.type || '');
                    const map = {
                        personal: 'full',
                        objective: 'full',
                        summary: 'full',
                        education: 'left',
                        experience: 'left',
                        project: 'right',
                        skill: 'right',
                        certificate: 'right',
                    };

                    return map[type] || 'auto';
                });
            } else {
                this.layoutProfile = 'custom';
            }

            this.dirty = true;
            this.showToast('已应用一键排版方案', 'success');
        },

        applyTypographyPreset(preset = 'balanced') {
            const map = {
                compact: {
                    fontSize: '12.5',
                    lineHeight: '1.5',
                    headingFontSize: '1.02',
                    sectionSpacing: '12',
                },
                balanced: {
                    fontSize: '13.5',
                    lineHeight: '1.7',
                    headingFontSize: '1.11',
                    sectionSpacing: '18',
                },
                spacious: {
                    fontSize: '14.5',
                    lineHeight: '1.9',
                    headingFontSize: '1.2',
                    sectionSpacing: '24',
                },
            };
            const target = map[String(preset || 'balanced')] || map.balanced;
            this.fontSize = target.fontSize;
            this.lineHeight = target.lineHeight;
            this.headingFontSize = target.headingFontSize;
            this.sectionSpacing = target.sectionSpacing;
            this.dirty = true;
            this.showToast('已应用排版预设', 'success');
        },

        applyFontPreset(preset = 'system') {
            const map = {
                system: "'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif",
                serif: "'Noto Serif SC','SimSun','STSong','宋体',serif",
                yahei: "'Microsoft YaHei','微软雅黑',sans-serif",
                elegant: "Georgia,'Times New Roman',serif",
            };
            this.fontFamily = map[String(preset || 'system')] || map.system;
            this.dirty = true;
            this.showToast('已切换字体方案', 'success');
        },

        applyColorPreset(preset = 'default') {
            const map = {
                default: { headingColor: '', accentColor: '', bodyFontColor: '' },
                business: { headingColor: '#0f172a', accentColor: '#2563eb', bodyFontColor: '#334155' },
                calm: { headingColor: '#1f2937', accentColor: '#0f766e', bodyFontColor: '#475569' },
                warm: { headingColor: '#7c2d12', accentColor: '#ea580c', bodyFontColor: '#4b5563' },
                elegant: { headingColor: '#312e81', accentColor: '#7c3aed', bodyFontColor: '#374151' },
            };
            const target = map[String(preset || 'default')] || map.default;
            this.headingColor = target.headingColor;
            this.accentColor = target.accentColor;
            this.bodyFontColor = target.bodyFontColor;
            this.dirty = true;
            this.showToast('已应用颜色预设', 'success');
        },

        resetFont() {
            this.fontFamily = "'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif";
            this.fontSize = '13.5';
            this.lineHeight = '1.7';
            this.headingFontSize = '1.11';
            this.headingColor = '';
            this.accentColor = '';
            this.bodyFontColor = '';
            this.sectionSpacing = '18';
            this.dirty = true;
            this.showToast('排版已恢复默认', 'info');
        },

        zoomInPreview() {
            this.previewScale = Math.min(130, Number(this.previewScale || 100) + 10);
        },

        zoomOutPreview() {
            this.previewScale = Math.max(70, Number(this.previewScale || 100) - 10);
        },

        resetPreviewZoom() {
            this.previewScale = 100;
            this.showToast('预览缩放已重置', 'info');
        },

        previewStageStyle() {
            const scale = Math.max(70, Math.min(130, Number(this.previewScale || 100))) / 100;
            return `transform: scale(${scale});`;
        },

        measureA4Overflow() {
            this.$nextTick(() => {
                const sheet = document.getElementById('resume-preview');
                if (!sheet) {
                    this.a4PageHeightPx = 0;
                    this.a4OverflowPx = 0;
                    this.a4AutoFitScale = 1;
                    this.a4AutoFitSkippedForLongContent = false;
                    return;
                }

                const mmToPx = 96 / 25.4;
                const a4HeightPx = Math.round(297 * mmToPx);
                const contentRoot = sheet.querySelector('.visual-resume') || sheet;
                const currentHeight = Math.ceil(contentRoot.scrollHeight || sheet.scrollHeight || 0);
                this.a4PageHeightPx = a4HeightPx;
                this.a4OverflowPx = Math.max(0, currentHeight - a4HeightPx);

                if (!this.a4AutoFitEnabled || currentHeight <= a4HeightPx + 1) {
                    this.a4AutoFitScale = 1;
                    this.a4AutoFitSkippedForLongContent = false;
                    return;
                }

                const requiredScale = a4HeightPx / currentHeight;
                const minReadableScale = Math.max(0.6, Math.min(0.95, Number(this.a4AutoFitMinScale || 0.78)));

                if (requiredScale < minReadableScale) {
                    this.a4AutoFitScale = 1;
                    this.a4AutoFitSkippedForLongContent = true;
                    return;
                }

                this.a4AutoFitSkippedForLongContent = false;
                this.a4AutoFitScale = Math.max(minReadableScale, Math.min(1, requiredScale));
            });
        },

        isA4AutoFitApplied() {
            return this.a4AutoFitEnabled
                && !this.a4AutoFitSkippedForLongContent
                && Number(this.a4AutoFitScale || 1) < 0.999;
        },

        a4StatusText() {
            if (!this.isA4Overflow()) {
                return '当前内容可落在 1 页 A4（' + this.a4FillPercent() + '%）';
            }

            if (this.isA4AutoFitApplied()) {
                return '已自动适配单页 A4（缩放 ' + Math.round((this.a4AutoFitScale || 1) * 100) + '%）';
            }

            if (this.a4AutoFitEnabled && this.a4AutoFitSkippedForLongContent) {
                return '内容较多，已保持可读性（超出 A4 约 ' + this.a4OverflowMm() + 'mm）';
            }

            return '当前内容超出 A4 约 ' + this.a4OverflowMm() + 'mm';
        },

        isA4Overflow() {
            return Number(this.a4OverflowPx || 0) > 1;
        },

        a4OverflowMm() {
            const px = Number(this.a4OverflowPx || 0);
            if (px <= 0) {
                return 0;
            }
            return Math.round((px / (96 / 25.4)) * 10) / 10;
        },

        a4FillPercent() {
            const height = Number(this.a4PageHeightPx || 0);
            if (height <= 0) {
                return 0;
            }
            const sheet = document.getElementById('resume-preview');
            const currentHeight = Math.ceil(sheet?.scrollHeight || 0);
            return Math.max(0, Math.round((currentHeight / height) * 100));
        },

        previewA4FitStyle() {
            const scale = Math.max(0.2, Math.min(1, Number(this.a4AutoFitScale || 1)));
            return `--editor-a4-fit-scale: ${scale};`;
        },

        applyOnePagePresetAndMeasure() {
            this.applyResumeProfile('onepage');
            this.a4AutoFitEnabled = true;
            this.$nextTick(() => this.measureA4Overflow());
        },

        applyLongContentReadableLayout() {
            this.a4AutoFitEnabled = false;
            this.layoutProfile = 'custom';
            this.applyTypographyPreset('compact');
            this.applyTitleStyleVariant('compact');
            this.leftColumnRatio = '50';

            const multiColumnTemplates = ['modern', 'creative', 'elegant', 'timeline'];
            if (multiColumnTemplates.includes(String(this.template || ''))) {
                this.template = 'classic';
            }

            this.applyModuleLayoutPlan(() => 'full');
            this.dirty = true;
            this.showToast('已切换为长内容可读排版（多页优先）', 'success');
            this.$nextTick(() => this.measureA4Overflow());
        },

        async togglePreviewFullscreen() {
            const previewArea = document.getElementById('editor-preview-area');
            if (!previewArea || !document.fullscreenEnabled) {
                this.showToast('当前浏览器不支持全屏预览', 'warning');
                return;
            }

            if (document.fullscreenElement === previewArea) {
                await document.exitFullscreen();
                return;
            }

            try {
                await previewArea.requestFullscreen();
            } catch (error) {
                                this.showToast('进入全屏失败，请稍后重试', 'danger');
            }
        },
