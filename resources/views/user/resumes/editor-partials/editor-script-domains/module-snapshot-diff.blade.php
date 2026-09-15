        moduleSnapshotList(mod) {
            if (!mod?._key) {
                return [];
            }
            const rows = Array.isArray(this.moduleSnapshots?.[mod._key]) ? this.moduleSnapshots[mod._key] : [];
            return [...rows].sort((a, b) => Number(b?.ts || 0) - Number(a?.ts || 0));
        },

        moduleSnapshotCurrentOptionValue() {
            return '__current__';
        },

        moduleSnapshotSourceData(mod, sourceTs) {
            const currentFlag = this.moduleSnapshotCurrentOptionValue();
            if (String(sourceTs || '') === currentFlag) {
                return JSON.parse(JSON.stringify(mod?.data || {}));
            }
            const list = this.moduleSnapshotList(mod);
            const target = list.find((row) => Number(row?.ts || 0) === Number(sourceTs || 0));
            return target ? JSON.parse(JSON.stringify(target.data || {})) : {};
        },

        moduleSnapshotCompareSelectionOf(mod) {
            const key = mod?._key;
            if (!key) {
                return { left: '', right: '' };
            }
            const selection = this.moduleSnapshotCompareSelection?.[key] || {};
            return {
                left: String(selection.left || ''),
                right: String(selection.right || ''),
            };
        },

        moduleSnapshotCompareOptionsOf(mod) {
            const key = mod?._key;
            if (!key) {
                return { importantOnly: true, focusOnly: false };
            }
            const options = this.moduleSnapshotCompareOptions?.[key] || {};
            return {
                importantOnly: options.importantOnly !== false,
                focusOnly: options.focusOnly === true,
            };
        },

        setModuleSnapshotCompareSelection(mod, side, value) {
            const key = mod?._key;
            if (!key || !['left', 'right'].includes(side)) {
                return;
            }
            const nextSelection = {
                ...(this.moduleSnapshotCompareSelection || {}),
                [key]: {
                    ...this.moduleSnapshotCompareSelectionOf(mod),
                    [side]: String(value || ''),
                },
            };
            this.moduleSnapshotCompareSelection = nextSelection;
        },

        swapModuleSnapshotCompareSelection(mod) {
            const selection = this.moduleSnapshotCompareSelectionOf(mod);
            if (!selection.left && !selection.right) {
                return;
            }
            this.setModuleSnapshotCompareSelection(mod, 'left', selection.right || '');
            this.setModuleSnapshotCompareSelection(mod, 'right', selection.left || '');
        },

        quickCompareLatestWithCurrent(mod) {
            const rows = this.moduleSnapshotList(mod);
            if (!rows.length) {
                this.showToast('暂无可用快照', 'warning');
                return;
            }
            this.setModuleSnapshotCompareSelection(mod, 'left', rows[0].ts);
            this.setModuleSnapshotCompareSelection(mod, 'right', this.moduleSnapshotCurrentOptionValue());
        },

        setModuleSnapshotCompareOption(mod, optionKey, value) {
            const key = mod?._key;
            if (!key) {
                return;
            }
            const nextOptions = {
                ...(this.moduleSnapshotCompareOptions || {}),
                [key]: {
                    ...this.moduleSnapshotCompareOptionsOf(mod),
                    [optionKey]: Boolean(value),
                },
            };
            this.moduleSnapshotCompareOptions = nextOptions;
        },

        snapshotDiffComparableKeys(data) {
            const source = data && typeof data === 'object' ? data : {};
            return Array.from(new Set([
                ...Object.keys(source),
            ])).sort();
        },

        snapshotFieldDisplayValue(value) {
            if (Array.isArray(value)) {
                return value.map((item) => String(item || '').trim()).filter(Boolean).join('\n');
            }
            if (value === null || value === undefined) {
                return '';
            }
            return String(value).trim();
        },

        snapshotDiffKeyPriority(key) {
            const map = {
                target_job: 1,
                content: 2,
                items: 3,
                title: 4,
                subtitle: 5,
                date: 6,
                location: 7,
                name: 8,
                phone: 9,
                email: 10,
                gender: 11,
                birthday: 12,
                wechat: 13,
                github: 14,
                website: 15,
                custom_fields: 16,
            };
            return map[String(key || '')] || 99;
        },

        moduleSnapshotDiffRows(mod) {
            const selection = this.moduleSnapshotCompareSelectionOf(mod);
            if (!selection.left || !selection.right || selection.left === selection.right) {
                return [];
            }

            const leftData = this.moduleSnapshotSourceData(mod, selection.left);
            const rightData = this.moduleSnapshotSourceData(mod, selection.right);
            const keys = Array.from(new Set([
                ...this.snapshotDiffComparableKeys(leftData),
                ...this.snapshotDiffComparableKeys(rightData),
            ]));

            const rows = [];
            keys.forEach((key) => {
                const left = this.snapshotFieldDisplayValue(leftData[key]);
                const right = this.snapshotFieldDisplayValue(rightData[key]);
                if (left === right) {
                    return;
                }
                rows.push({
                    key,
                    label: this.moduleFieldLabelByKey(key),
                    before: left,
                    after: right,
                    type: !left && right ? 'added' : (left && !right ? 'removed' : 'modified'),
                    mod,
                });
            });

            rows.sort((a, b) => {
                const pa = this.snapshotDiffKeyPriority(a?.key);
                const pb = this.snapshotDiffKeyPriority(b?.key);
                if (pa !== pb) {
                    return pa - pb;
                }
                const ia = this.isImportantSnapshotDiffRow(a) ? 0 : 1;
                const ib = this.isImportantSnapshotDiffRow(b) ? 0 : 1;
                if (ia !== ib) {
                    return ia - ib;
                }
                return String(a?.label || '').localeCompare(String(b?.label || ''), 'zh-CN');
            });

            return rows.slice(0, 12);
        },

        normalizeSnapshotCompareText(value) {
            return String(value || '')
                .replace(/\s+/g, ' ')
                .replace(/[，。；：、,.!?(){}\[\]<>《》"'\x60~\-_/\\|]/g, '')
                .trim()
                .toLowerCase();
        },

        escapeSnapshotDiffHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        snapshotDiffTokens(value) {
            return String(value || '').match(/[\u4e00-\u9fa5]+|[A-Za-z0-9_+#.\-]+|\n|\s+|./g) || [];
        },

        buildSnapshotDiffLcs(beforeTokens, afterTokens) {
            const m = beforeTokens.length;
            const n = afterTokens.length;
            const matrix = Array.from({ length: m + 1 }, () => new Array(n + 1).fill(0));
            for (let i = 1; i <= m; i += 1) {
                for (let j = 1; j <= n; j += 1) {
                    if (beforeTokens[i - 1] === afterTokens[j - 1]) {
                        matrix[i][j] = matrix[i - 1][j - 1] + 1;
                    } else {
                        matrix[i][j] = Math.max(matrix[i - 1][j], matrix[i][j - 1]);
                    }
                }
            }
            return matrix;
        },

        buildSnapshotTokenDiffParts(before, after) {
            const beforeTokens = this.snapshotDiffTokens(before).slice(0, 220);
            const afterTokens = this.snapshotDiffTokens(after).slice(0, 220);
            if ((beforeTokens.length * afterTokens.length) > 42000) {
                return {
                    beforeParts: [{ text: String(before || ''), changed: true }],
                    afterParts: [{ text: String(after || ''), changed: true }],
                };
            }
            const lcs = this.buildSnapshotDiffLcs(beforeTokens, afterTokens);
            const ops = [];
            let i = beforeTokens.length;
            let j = afterTokens.length;

            while (i > 0 && j > 0) {
                if (beforeTokens[i - 1] === afterTokens[j - 1]) {
                    ops.push({ type: 'equal', text: beforeTokens[i - 1] });
                    i -= 1;
                    j -= 1;
                    continue;
                }
                if (lcs[i - 1][j] >= lcs[i][j - 1]) {
                    ops.push({ type: 'remove', text: beforeTokens[i - 1] });
                    i -= 1;
                } else {
                    ops.push({ type: 'add', text: afterTokens[j - 1] });
                    j -= 1;
                }
            }
            while (i > 0) {
                ops.push({ type: 'remove', text: beforeTokens[i - 1] });
                i -= 1;
            }
            while (j > 0) {
                ops.push({ type: 'add', text: afterTokens[j - 1] });
                j -= 1;
            }
            ops.reverse();

            const beforeParts = [];
            const afterParts = [];
            ops.forEach((op) => {
                if (op.type === 'equal') {
                    beforeParts.push({ text: op.text, changed: false });
                    afterParts.push({ text: op.text, changed: false });
                    return;
                }
                if (op.type === 'remove') {
                    beforeParts.push({ text: op.text, changed: true });
                    return;
                }
                afterParts.push({ text: op.text, changed: true });
            });

            return { beforeParts, afterParts };
        },

        buildSnapshotFocusedRanges(parts) {
            const rows = Array.isArray(parts) ? parts : [];
            const changedIndexes = [];
            rows.forEach((part, index) => {
                if (part?.changed) {
                    changedIndexes.push(index);
                }
            });
            if (!changedIndexes.length) {
                return [];
            }
            const ranges = [];
            changedIndexes.forEach((idx) => {
                const start = Math.max(0, idx - 1);
                const end = Math.min(rows.length - 1, idx + 1);
                const last = ranges[ranges.length - 1];
                if (last && start <= (last.end + 1)) {
                    last.end = Math.max(last.end, end);
                    return;
                }
                ranges.push({ start, end });
            });
            return ranges;
        },

        renderSnapshotDiffParts(parts, changedClass, focusOnly = false) {
            const rows = Array.isArray(parts) ? parts : [];
            if (focusOnly) {
                const ranges = this.buildSnapshotFocusedRanges(rows);
                if (!ranges.length) {
                    return '';
                }
                const htmlParts = [];
                ranges.forEach((range, rangeIndex) => {
                    if (rangeIndex > 0) {
                        htmlParts.push('<span class="text-muted"> ... </span>');
                    }
                    for (let i = range.start; i <= range.end; i += 1) {
                        const part = rows[i];
                        const safe = this.escapeSnapshotDiffHtml(part?.text || '');
                        if (!part?.changed) {
                            htmlParts.push(safe);
                            continue;
                        }
                        htmlParts.push(`<span class="${changedClass}">${safe}</span>`);
                    }
                });
                return htmlParts.join('');
            }
            return rows.map((part) => {
                const safe = this.escapeSnapshotDiffHtml(part?.text || '');
                if (!part?.changed) {
                    return safe;
                }
                return `<span class="${changedClass}">${safe}</span>`;
            }).join('');
        },

        moduleSnapshotTokenDiffParts(row) {
            if (row && typeof row === 'object' && row.__tokenDiffParts) {
                return row.__tokenDiffParts;
            }
            const diff = this.buildSnapshotTokenDiffParts(row?.before || '', row?.after || '');
            if (row && typeof row === 'object') {
                row.__tokenDiffParts = diff;
            }
            return diff;
        },

        moduleSnapshotDiffBeforeHtml(row) {
            const diff = this.moduleSnapshotTokenDiffParts(row);
            const html = this.renderSnapshotDiffParts(
                diff.beforeParts,
                'editor-snapshot-diff-remove',
                this.moduleSnapshotCompareOptionsOf(row?.mod || {}).focusOnly === true
            );
            return html || '（空）';
        },

        moduleSnapshotDiffAfterHtml(row) {
            const diff = this.moduleSnapshotTokenDiffParts(row);
            const html = this.renderSnapshotDiffParts(
                diff.afterParts,
                'editor-snapshot-diff-add',
                this.moduleSnapshotCompareOptionsOf(row?.mod || {}).focusOnly === true
            );
            return html || '（空）';
        },

        isImportantSnapshotDiffRow(row) {
            const key = String(row?.key || '');
            const before = String(row?.before || '');
            const after = String(row?.after || '');
            if (['items', 'content', 'target_job'].includes(key)) {
                return true;
            }
            const beforeNorm = this.normalizeSnapshotCompareText(before);
            const afterNorm = this.normalizeSnapshotCompareText(after);
            if (beforeNorm === afterNorm) {
                return false;
            }
            const lengthDelta = Math.abs(beforeNorm.length - afterNorm.length);
            if (beforeNorm.length <= 6 && afterNorm.length <= 6 && lengthDelta <= 1) {
                return false;
            }
            return true;
        },

        moduleSnapshotVisibleDiffRows(mod) {
            const rows = this.moduleSnapshotDiffRows(mod);
            const options = this.moduleSnapshotCompareOptionsOf(mod);
            if (!options.importantOnly) {
                return rows;
            }
            return rows.filter((row) => this.isImportantSnapshotDiffRow(row));
        },

        moduleSnapshotDiffTypeLabel(type) {
            const map = {
                added: '新增',
                removed: '删除',
                modified: '修改',
            };
            return map[String(type || '')] || '变化';
        },

        moduleSnapshotDiffTypeBadgeClass(type) {
            const map = {
                added: 'bg-success-lt text-success',
                removed: 'bg-danger-lt text-danger',
                modified: 'bg-primary-lt text-primary',
            };
            return map[String(type || '')] || 'bg-light text-muted';
        },

        moduleSnapshotDiffCounts(mod) {
            const rows = this.moduleSnapshotDiffRows(mod);
            const counts = { total: rows.length, added: 0, removed: 0, modified: 0 };
            rows.forEach((row) => {
                const type = String(row?.type || 'modified');
                if (Object.prototype.hasOwnProperty.call(counts, type)) {
                    counts[type] += 1;
                }
            });
            return counts;
        },

        moduleSnapshotCompareVersionText(mod) {
            const selection = this.moduleSnapshotCompareSelectionOf(mod);
            if (!selection.left || !selection.right) {
                return '';
            }
            const leftLabel = this.moduleSnapshotOptionLabel(mod, selection.left);
            const rightLabel = this.moduleSnapshotOptionLabel(mod, selection.right);
            return `左侧：${leftLabel} ｜ 右侧：${rightLabel}`;
        },

        moduleSnapshotDiffSummary(mod) {
            const rows = this.moduleSnapshotDiffRows(mod);
            if (rows.length === 0) {
                return '当前组合无差异或未选择对比项';
            }
            const counts = this.moduleSnapshotDiffCounts(mod);
            const visibleRows = this.moduleSnapshotVisibleDiffRows(mod);
            const options = this.moduleSnapshotCompareOptionsOf(mod);
            const countText = `新增 ${counts.added} / 删除 ${counts.removed} / 修改 ${counts.modified}`;
            if (options.importantOnly) {
                return `共发现 ${rows.length} 处差异（${countText}），关键差异 ${visibleRows.length} 处`;
            }
            return `共发现 ${rows.length} 处字段差异（${countText}）`;
        },

        moduleSnapshotOptionLabel(mod, ts) {
            const currentFlag = this.moduleSnapshotCurrentOptionValue();
            if (String(ts || '') === currentFlag) {
                return '当前内容';
            }
            const list = this.moduleSnapshotList(mod);
            const row = list.find((item) => Number(item?.ts || 0) === Number(ts || 0));
            return row ? `快照 ${row.label}` : '快照';
        },

        saveModuleSnapshot(index) {
            const mod = this.modules[index];
            if (!mod?._key) {
                return;
            }
            const snapshots = { ...(this.moduleSnapshots || {}) };
            const current = Array.isArray(snapshots[mod._key]) ? [...snapshots[mod._key]] : [];
            const ts = Date.now();
            current.push({
                ts,
                label: new Date(ts).toLocaleTimeString('zh-CN', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                data: JSON.parse(JSON.stringify(mod.data || {})),
            });
            snapshots[mod._key] = current.slice(-8);
            this.moduleSnapshots = snapshots;
            const latest = snapshots[mod._key][snapshots[mod._key].length - 1];
            const previous = snapshots[mod._key][snapshots[mod._key].length - 2];
            if (latest) {
                this.setModuleSnapshotCompareSelection(mod, 'right', latest.ts);
            }
            if (previous) {
                this.setModuleSnapshotCompareSelection(mod, 'left', previous.ts);
            }
            this.showToast('已保存模块快照', 'success');
        },

        restoreModuleSnapshot(index, ts) {
            const mod = this.modules[index];
            if (!mod?._key) {
                return;
            }
            const rows = this.moduleSnapshotList(mod);
            const target = rows.find((row) => Number(row?.ts || 0) === Number(ts));
            if (!target) {
                this.showToast('未找到对应快照', 'warning');
                return;
            }
            this.pushUndo();
            const next = JSON.parse(JSON.stringify(mod));
            next.data = JSON.parse(JSON.stringify(target.data || {}));
            this.modules.splice(index, 1, next);
            this.activeIndex = index;
            this.dirty = true;
            this.showToast('已回滚到所选快照', 'info');
            this.scheduleInsightsRefresh(80);
        },

        removeModuleSnapshot(index, ts) {
            const mod = this.modules[index];
            if (!mod?._key) {
                return;
            }
            const snapshots = { ...(this.moduleSnapshots || {}) };
            const current = Array.isArray(snapshots[mod._key]) ? snapshots[mod._key] : [];
            snapshots[mod._key] = current.filter((row) => Number(row?.ts || 0) !== Number(ts));
            this.moduleSnapshots = snapshots;
            const selection = this.moduleSnapshotCompareSelectionOf(mod);
            if (String(selection.left) === String(ts) || String(selection.right) === String(ts)) {
                this.setModuleSnapshotCompareSelection(mod, 'left', '');
                this.setModuleSnapshotCompareSelection(mod, 'right', '');
            }
        },
