        defaultModuleFieldValue(type, field) {
            const defaults = {
                education: { title: '教育经历' },
                experience: { title: '实习经历' },
                project: { title: '项目经验' },
                skill: { title: '技能证书' },
                certificate: { title: '获奖情况' },
                summary: { title: '自我评价' },
            };

            return defaults[type]?.[field] ?? '';
        },

        hasMeaningfulText(mod, field) {
            const value = mod?.data?.[field];
            if (typeof value !== 'string') {
                return false;
            }

            const trimmed = value.trim();
            if (trimmed === '') {
                return false;
            }

            const defaultValue = String(this.defaultModuleFieldValue(mod?.type, field) || '').trim();
            return defaultValue === '' || trimmed !== defaultValue;
        },

        filledItemsCount(items) {
            if (!Array.isArray(items)) {
                return 0;
            }

            return items.filter((item) => typeof item === 'string' && item.trim() !== '').length;
        },

        hasFilledItems(items) {
            return this.filledItemsCount(items) > 0;
        },

        textLength(value) {
            return typeof value === 'string' ? value.trim().length : 0;
        },

        moduleCompletionMetrics(mod) {
            const itemsCount = this.filledItemsCount(mod?.data?.items);
            const contentLength = this.textLength(mod?.data?.content);

            switch (mod?.type) {
                case 'personal':
                    return {
                        total: 4,
                        filled: ['name', 'phone', 'email', 'location']
                            .filter((field) => this.hasMeaningfulText(mod, field)).length,
                    };
                case 'objective':
                    return {
                        total: 2,
                        filled: ['target_job', 'content']
                            .filter((field) => this.hasMeaningfulText(mod, field)).length,
                    };
                case 'education':
                case 'experience':
                case 'project':
                    return {
                        total: 5,
                        filled: [
                            this.hasMeaningfulText(mod, 'subtitle'),
                            this.hasMeaningfulText(mod, 'date'),
                            this.hasMeaningfulText(mod, 'location'),
                            contentLength >= 12 || itemsCount >= 1,
                            itemsCount >= 2 || contentLength >= 40,
                        ].filter(Boolean).length,
                    };
                case 'skill':
                    return {
                        total: 3,
                        filled: [
                            itemsCount >= 1,
                            itemsCount >= 3,
                            itemsCount >= 6,
                        ].filter(Boolean).length,
                    };
                case 'certificate':
                    return {
                        total: 2,
                        filled: [
                            itemsCount >= 1,
                            itemsCount >= 2,
                        ].filter(Boolean).length,
                    };
                case 'summary':
                    return {
                        total: 2,
                        filled: [
                            contentLength >= 30,
                            contentLength >= 80,
                        ].filter(Boolean).length,
                    };
                default:
                    return {
                        total: 2,
                        filled: [
                            contentLength >= 20,
                            itemsCount >= 1,
                        ].filter(Boolean).length,
                    };
            }
        },

        moduleCompletionStats() {
            return this.modules.reduce((stats, mod) => {
                const percent = this.moduleCompletionPercent(mod);
                if (percent >= 75) {
                    stats.high += 1;
                } else if (percent >= 35) {
                    stats.medium += 1;
                } else {
                    stats.low += 1;
                }

                return stats;
            }, { high: 0, medium: 0, low: 0 });
        },

        moduleCompletionCount(level) {
            return this.moduleCompletionStats()[level] ?? 0;
        },

        moduleCompletionOverviewText() {
            const total = this.modules.length;
            if (total === 0) {
                return '从上方添加模块开始完善简历内容。';
            }

            const stats = this.moduleCompletionStats();
            if (stats.low > 0) {
                return `当前有 ${stats.low} 个模块待填写，建议优先补齐基础信息和关键经历。`;
            }
            if (stats.medium > 0) {
                return `当前有 ${stats.medium} 个模块待完善，补充量化成果后更利于 ATS 和 AI 优化。`;
            }

            return `当前 ${total} 个模块都较完整，可以继续优化措辞和亮点表达。`;
        },

        moduleCompletionPercent(mod) {
            const metrics = this.moduleCompletionMetrics(mod);
            return Math.max(0, Math.min(100, Math.round((metrics.filled / Math.max(metrics.total, 1)) * 100)));
        },

        moduleCompletionLabel(mod) {
            const percent = this.moduleCompletionPercent(mod);
            if (percent >= 75) {
                return '较完整';
            }
            if (percent >= 35) {
                return '待完善';
            }
            return '待填写';
        },

        moduleCompletionBadgeClass(mod) {
            const percent = this.moduleCompletionPercent(mod);
            if (percent >= 75) {
                return 'bg-success-lt text-success';
            }
            if (percent >= 35) {
                return 'bg-primary-lt text-primary';
            }
            return 'bg-warning-lt text-warning';
        },

        moduleCompletionBarClass(mod) {
            const percent = this.moduleCompletionPercent(mod);
            if (percent >= 75) {
                return 'is-high';
            }
            if (percent >= 35) {
                return 'is-medium';
            }
            return 'is-low';
        },

        @include('user.resumes.editor-partials.editor-script-domains.module-hints')
