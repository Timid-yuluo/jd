        renderKeywordHighlightedText(rawLine) {
            const line = String(rawLine || ' ');
            const keywords = this.focusKeywordsForOptimize()
                .filter((item) => String(item || '').trim() !== '')
                .sort((a, b) => String(b).length - String(a).length)
                .slice(0, 20);

            if (keywords.length === 0) {
                return this.escapeHtml(line);
            }

            const pattern = new RegExp(`(${keywords.map((kw) => this.escapeRegExp(kw)).join('|')})`, 'ig');
            const segments = line.split(pattern);
            return segments.map((segment) => {
                if (!segment) {
                    return '';
                }
                const matched = keywords.some((kw) => String(kw).toLowerCase() === segment.toLowerCase());
                if (!matched) {
                    return this.escapeHtml(segment);
                }
                return `<span class="badge bg-warning-lt text-warning border border-warning-subtle me-1">${this.escapeHtml(segment)}</span>`;
            }).join('');
        },

        parseSectionedLines(text) {
            const rows = [];
            let currentSection = '未归类';
            const lines = (text || '').split('\n');
            lines.forEach((line) => {
                const match = line.match(/^##\s+(.+)$/);
                if (match) {
                    currentSection = match[1].trim() || '未归类';
                }
                rows.push({ text: line, section: currentSection });
            });
            return rows;
        },

        buildDiffHtml(rows, changedSet, changedClass) {
            if (!Array.isArray(rows) || rows.length === 0) {
                return '<div class="text-secondary">暂无内容</div>';
            }
            const renderedLines = rows.filter((row) => {
                const inSection = this.selectedDiffSection === 'ALL' || row.section === this.selectedDiffSection;
                const changed = changedSet.has(row.text);
                return inSection && (!this.diffOnlyChanges || changed);
            });
            if (renderedLines.length === 0) {
                return '<div class="text-secondary">当前模式下无差异内容</div>';
            }
            return renderedLines.map((row) => {
                const cls = changedSet.has(row.text) ? changedClass : 'diff-line';
                return `<div class="${cls}">${this.renderKeywordHighlightedText(row.text || ' ')}</div>`;
            }).join('');
        },

        prepareDiff() {
            const currentRaw = this.modulesToRawText();
            const originalRows = this.parseSectionedLines(currentRaw || this.rawContentForDiff);
            const optimizedRows = this.parseSectionedLines(this.optimizedContentForDiff);
            const originalLines = originalRows.map((row) => row.text);
            const optimizedLines = optimizedRows.map((row) => row.text);
            const originalSet = new Set(originalLines.filter((line) => line.trim() !== ''));
            const optimizedSet = new Set(optimizedLines.filter((line) => line.trim() !== ''));
            const removedOrChanged = new Set(
                originalLines.filter((line) => line.trim() !== '' && !optimizedSet.has(line))
            );
            const addedOrChanged = new Set(
                optimizedLines.filter((line) => line.trim() !== '' && !originalSet.has(line))
            );
            this.diffDeltaText = optimizedLines
                .filter((line) => line.trim() !== '' && !originalSet.has(line))
                .join('\n');
            const sectionAddedCounter = {};
            const sectionRemovedCounter = {};
            optimizedRows.forEach((row) => {
                if (!addedOrChanged.has(row.text) || row.text.trim() === '') {
                    return;
                }
                sectionAddedCounter[row.section] = (sectionAddedCounter[row.section] || 0) + 1;
            });
            originalRows.forEach((row) => {
                if (!removedOrChanged.has(row.text) || row.text.trim() === '') {
                    return;
                }
                sectionRemovedCounter[row.section] = (sectionRemovedCounter[row.section] || 0) + 1;
            });
            const sectionNames = new Set([
                ...Object.keys(sectionAddedCounter),
                ...Object.keys(sectionRemovedCounter),
            ]);
            this.diffSections = Array.from(sectionNames)
                .map((name) => {
                    const added_count = sectionAddedCounter[name] || 0;
                    const removed_count = sectionRemovedCounter[name] || 0;
                    return {
                        name,
                        added_count,
                        removed_count,
                        replaced_count: Math.min(added_count, removed_count),
                        changed_count: added_count + removed_count,
                    };
                })
                .sort((a, b) => b.changed_count - a.changed_count);

            if (this.selectedDiffSection !== 'ALL' && !this.diffSections.some((sec) => sec.name === this.selectedDiffSection)) {
                this.selectedDiffSection = 'ALL';
            }

            const removedLines = originalLines.filter((line) => line.trim() !== '' && !optimizedSet.has(line));
            const optimizedLowerText = optimizedLines.join('\n').toLowerCase();
            const keywords = [
                ['laravel', 'Laravel'],
                ['php', 'PHP'],
                ['java', 'Java'],
                ['spring', 'Spring'],
                ['mysql', 'MySQL'],
                ['redis', 'Redis'],
                ['docker', 'Docker'],
                ['k8s', 'K8s'],
                ['kubernetes', 'Kubernetes'],
                ['vue', 'Vue'],
                ['react', 'React'],
                ['python', 'Python'],
                ['golang', 'Golang'],
                ['linux', 'Linux'],
                ['kafka', 'Kafka'],
                ['rabbitmq', 'RabbitMQ'],
                ['elasticsearch', 'Elasticsearch'],
                ['微服务', '微服务'],
                ['高并发', '高并发'],
            ];
            const weakenedKeywords = new Set();
            removedLines.forEach((line) => {
                const lower = line.toLowerCase();
                keywords.forEach(([key, label]) => {
                    if (lower.includes(key) && !optimizedLowerText.includes(key)) {
                        weakenedKeywords.add(label);
                    }
                });
            });
            const riskTips = [];
            if (weakenedKeywords.size > 0) {
                riskTips.push(`检测到关键技能词可能被弱化：${Array.from(weakenedKeywords).join('、')}`);
            }
            if (removedOrChanged.size >= 8 && removedOrChanged.size > addedOrChanged.size * 2) {
                riskTips.push('删除内容明显多于新增内容，建议复核是否误删了关键项目或技能描述。');
            }
            this.diffRiskTips = riskTips;
            const coverage = this.keywordCoverageOptimized || this.keywordCoverageStats(this.optimizedContentForDiff);
            this.diffHighlightedKeywords = {
                hit: Array.isArray(coverage?.hitKeywords) ? coverage.hitKeywords : [],
                miss: Array.isArray(coverage?.missedKeywords) ? coverage.missedKeywords : [],
            };
            this.refreshRegressionValidationSummary();

            this.diffHtmlOriginal = this.buildDiffHtml(originalRows, removedOrChanged, 'diff-line diff-line-removed');
            this.diffHtmlOptimized = this.buildDiffHtml(optimizedRows, addedOrChanged, 'diff-line diff-line-added');
        },

        focusSection(sectionName) {
            this.selectedDiffSection = sectionName;
            this.prepareDiff();
            const index = this.modules.findIndex((mod) => {
                const title = (mod?.data?.title || '').trim();
                return title !== '' && title === sectionName;
            });
            if (index < 0) {
                return;
            }
            this.activeIndex = index;
            this.$nextTick(() => {
                const listEl = document.getElementById('module-list');
                const cards = listEl?.querySelectorAll('.card');
                const target = cards?.[index];
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },

        copyOptimizedDelta() {
            if (!this.diffDeltaText) {
                this.showToast('当前没有可复制的优化增量', 'warning');
                return;
            }
            navigator.clipboard.writeText(this.diffDeltaText)
                .then(() => this.showToast('优化增量已复制', 'success'))
                .catch(() => this.showToast('复制失败，请手动复制', 'danger'));
        },

        openOptimizedDiff() {
            if (!this.optimizedContentForDiff) {
                this.showToast('当前暂无优化结果可对比。', 'warning');
                return;
            }

            this.prepareDiff();
            const modalEl = document.getElementById('optimized-diff-modal');
            if (!modalEl || !window.bootstrap) {
                return;
            }
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        },
