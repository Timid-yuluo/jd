function resumeOptimizeComparePage() {
    const readAttr = (attrName) => {
        try {
            const el = document.querySelector(`[${attrName}]`);
            if (!el) {
                console.warn('[optimize-compare] Element not found for attribute:', attrName);
                return null;
            }
            const raw = el.getAttribute(attrName);
            if (raw === null || raw === undefined) return null;
            return JSON.parse(raw);
        } catch (e) {
            console.error('[optimize-compare] JSON parse error for attr:', attrName, e);
            return null;
        }
    };

    const payload = {
        rows: readAttr('data-json-'),
        beforeRaw: readAttr('data-json-beforeraw'),
        afterRaw: readAttr('data-json-afterraw'),
        beforeModules: readAttr('data-json-beforemodules'),
        afterModules: readAttr('data-json-aftermodules'),
        scoreDelta: readAttr('data-json-scoredelta'),
        riskTips: readAttr('data-json-risktips'),
        highlights: readAttr('data-json-highlights'),
        applyUrl: readAttr('data-json-route-user-resumes-optimize-compare-apply-resume-session'),
        csrfToken: readAttr('data-json-csrf-token'),
        resumeUpdatedAt: readAttr('data-json-resumeupdatedat'),
    };

    if (!payload.applyUrl) {
        console.error('[optimize-compare] applyUrl is missing! Apply will not work.');
    }
    if (!payload.csrfToken) {
        console.error('[optimize-compare] csrfToken is missing! Apply will not work.');
    }

    const stableSerialize = (value) => {
        if (Array.isArray(value)) {
            return `[${value.map((item) => stableSerialize(item)).join(',')}]`;
        }
        if (value && typeof value === 'object') {
            const keys = Object.keys(value).sort();
            return `{${keys.map((key) => `${JSON.stringify(key)}:${stableSerialize(value[key])}`).join(',')}}`;
        }
        return JSON.stringify(value ?? null);
    };

    const technicalKeys = new Set(['sort_order', 'module_key', 'id', 'uuid', 'created_at', 'updated_at']);
    const metaKeys = new Set(['title', 'subtitle', 'date', 'location']);

    const humanizeKey = (key) => String(key || '')
        .replace(/_/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const flattenValueLines = (value, path = [], options = {}) => {
        const includeMetaKeys = Boolean(options?.includeMetaKeys);

        if (value === null || value === undefined) {
            return [];
        }

        if (Array.isArray(value)) {
            return value.flatMap((item) => flattenValueLines(item, path, options));
        }

        if (typeof value === 'object') {
            return Object.entries(value)
                .filter(([key]) => {
                    if (technicalKeys.has(key)) return false;
                    if (!includeMetaKeys && metaKeys.has(key)) return false;
                    return true;
                })
                .flatMap(([key, childValue]) => flattenValueLines(childValue, [...path, key], options));
        }

        const normalized = String(value).trim();
        if (!normalized) {
            return [];
        }

        const label = path.length > 0 ? humanizeKey(path[path.length - 1]) : '';

        return [label ? `${label}: ${normalized}` : normalized];
    };

    const uniqueLines = (lines) => {
        const result = [];
        (Array.isArray(lines) ? lines : []).forEach((line) => {
            const normalized = String(line || '').trim();
            if (!normalized || result.includes(normalized)) {
                return;
            }
            result.push(normalized);
        });
        return result;
    };

    const buildModulePreview = (type, data) => {
        const safeData = data && typeof data === 'object' ? data : {};
        const meta = [safeData.subtitle, safeData.date, safeData.location]
            .map((item) => String(item || '').trim())
            .filter(Boolean)
            .join(' | ');

        let lines = [];
        if (type === 'personal') {
            lines = uniqueLines([
                ...[safeData.name, safeData.phone, safeData.email, safeData.location]
                    .map((item) => String(item || '').trim())
                    .filter(Boolean),
                ...flattenValueLines(safeData, [], { includeMetaKeys: false }),
            ]);
        } else {
            lines = uniqueLines(flattenValueLines(safeData, [], { includeMetaKeys: false }));
        }

        return {
            meta,
            lines,
            text: lines.join('\n'),
            signature: stableSerialize(safeData),
        };
    };

    const normalize = (modules) => {
        const result = [];
        const typeCount = {};
        (Array.isArray(modules) ? modules : []).forEach((module) => {
            const type = String(module?.type || '').trim();
            if (!type) return;
            typeCount[type] = (typeCount[type] || 0) + 1;
            const key = module?.module_key || `${type}:${typeCount[type]}`;
            const data = module?.data && typeof module.data === 'object' ? module.data : {};
            const title = String(data.title || (type === 'personal' ? '个人信息' : type)).trim();
            const preview = buildModulePreview(type, data);
            const content = preview.lines[0] || '';
            const itemsArray = preview.lines.slice(1);
            result.push({
                module_key: key,
                type,
                title,
                meta: preview.meta,
                content,
                items: itemsArray,
                text: preview.text,
                signature: preview.signature,
                changed: false,
            });
        });
        return result;
    };

    const modulesToText = (modules) => {
        const rows = normalize(modules);
        if (!rows.length) return '';
        return rows.map((row) => {
            const title = row.title ? `## ${row.title}` : '';
            const body = row.text || '';
            return [title, body].filter(Boolean).join('\n');
        }).join('\n\n').trim();
    };

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const diffTokens = (value) => String(value || '').match(/[\u4e00-\u9fa5]+|[A-Za-z0-9_+#.\-]+|\n|\s+|./g) || [];

    const buildLcs = (beforeTokens, afterTokens) => {
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
    };

    const tokenDiffParts = (beforeText, afterText) => {
        const beforeTokens = diffTokens(beforeText).slice(0, 260);
        const afterTokens = diffTokens(afterText).slice(0, 260);
        if ((beforeTokens.length * afterTokens.length) > 55000) {
            return {
                beforeParts: [{ text: String(beforeText || ''), changed: true }],
                afterParts: [{ text: String(afterText || ''), changed: true }],
            };
        }
        const lcs = buildLcs(beforeTokens, afterTokens);
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
    };

    const renderDiffParts = (parts, className) => (Array.isArray(parts) ? parts : []).map((part) => {
        const safe = escapeHtml(part?.text || '');
        if (!part?.changed) return safe;
        return `<span class="${className}">${safe}</span>`;
    }).join('');

    const estimateImpact = (beforeRow, afterRow, diffStats, highRisk) => {
        const beforeContent = String(beforeRow?.content || '');
        const afterContent = String(afterRow?.content || '');
        const beforeMeta = String(beforeRow?.meta || '');
        const afterMeta = String(afterRow?.meta || '');
        const beforeItems = Array.isArray(beforeRow?.items) ? beforeRow.items : [];
        const afterItems = Array.isArray(afterRow?.items) ? afterRow.items : [];

        let fieldCount = 0;
        if (beforeContent !== afterContent) fieldCount += 1;
        if (beforeMeta !== afterMeta) fieldCount += 1;
        if (beforeItems.join('\n') !== afterItems.join('\n')) fieldCount += 1;
        if (highRisk) fieldCount += 1;

        const itemCount = Math.abs(afterItems.length - beforeItems.length) + afterItems.filter((item, idx) => item !== beforeItems[idx]).length;
        const charCount = Number(diffStats?.addedChars || 0) + Number(diffStats?.removedChars || 0);

        return {
            fieldCount,
            itemCount,
            charCount,
        };
    };

    const before = normalize(payload.beforeModules);
    const after = normalize(payload.afterModules);
    const beforeMap = Object.fromEntries(before.map((row) => [row.module_key, row]));
    const afterMap = Object.fromEntries(after.map((row) => [row.module_key, row]));
    const mergedKeys = [
        ...after.map((row) => row.module_key),
        ...before.map((row) => row.module_key).filter((key) => !afterMap[key]),
    ];
    payload.rows = mergedKeys.map((moduleKey, idx) => {
        const beforeRow = beforeMap[moduleKey] || null;
        const afterRow = afterMap[moduleKey] || null;
        const beforeText = beforeRow?.text || '';
        const afterText = afterRow?.text || '';
        const beforeSignature = beforeRow?.signature || '';
        const afterSignature = afterRow?.signature || '';
        const changed = beforeSignature !== afterSignature;
        if (beforeRow) beforeRow.changed = changed;
        if (afterRow) afterRow.changed = changed;
        const tokenDiff = tokenDiffParts(beforeText, afterText);
        const beforeDiffHtml = renderDiffParts(tokenDiff.beforeParts, 'opt-diff-remove') || '（空）';
        const afterDiffHtml = renderDiffParts(tokenDiff.afterParts, 'opt-diff-add') || '（空）';
        const addedChars = (tokenDiff.afterParts || []).filter((part) => part?.changed).reduce((acc, part) => acc + String(part?.text || '').length, 0);
        const removedChars = (tokenDiff.beforeParts || []).filter((part) => part?.changed).reduce((acc, part) => acc + String(part?.text || '').length, 0);
        const highRisk = changed && (
            (afterRow?.type || beforeRow?.type) === 'personal'
            || /姓名|电话|邮箱|个人信息/.test(String(afterRow?.title || beforeRow?.title || ''))
        );
        if (beforeRow) beforeRow.diffHtmlBefore = beforeDiffHtml;
        if (afterRow) afterRow.diffHtmlAfter = afterDiffHtml;
        if (beforeRow) {
            beforeRow.diffStats = { addedChars, removedChars };
            beforeRow.highRisk = highRisk;
        }
        if (afterRow) {
            afterRow.diffStats = { addedChars, removedChars };
            afterRow.highRisk = highRisk;
        }
        return {
            module_key: moduleKey,
            type: afterRow?.type || beforeRow?.type || 'summary',
            title: afterRow?.title || beforeRow?.title || `模块 ${idx + 1}`,
            beforeText,
            afterText,
            changed,
            beforeDiffHtml,
            afterDiffHtml,
            diffStats: { addedChars, removedChars },
            highRisk,
            impact: estimateImpact(beforeRow, afterRow, { addedChars, removedChars }, highRisk),
        };
    });

    return {
        beforeRaw: String(payload.beforeRaw || modulesToText(payload.beforeModules) || ''),
        afterRaw: String(payload.afterRaw || modulesToText(payload.afterModules) || ''),
        beforeRenderRows: before,
        afterRenderRows: after,
        rows: payload.rows,
        riskTips: Array.isArray(payload.riskTips) ? payload.riskTips : [],
        highlights: Array.isArray(payload.highlights) ? payload.highlights : [],
        scoreDelta: payload.scoreDelta && typeof payload.scoreDelta === 'object' ? payload.scoreDelta : {},
        selectedKeys: payload.rows.filter((row) => row.changed && !row.highRisk).map((row) => row.module_key),
        allowSensitiveMap: Object.fromEntries(payload.rows.map((row) => [row.module_key, false])),
        diffFilter: 'all',
        confirmApplyVisible: false,
        pendingSelections: [],
        highRiskConfirmText: '',
        submitting: false,
        _showConfirmModal(selections, rows, context) {
            const self = this;
            const hasHighRisk = rows.some(function(r) { return r.highRisk; });
            const pendingRows = rows.filter(function(r) { return selections.some(function(s) { return s.module_key === r.module_key; }); });
            const summary = pendingRows.reduce(function(acc, r) {
                acc.moduleCount++;
                acc.fieldCount += Number(r.impact?.fieldCount || 0);
                acc.itemCount += Number(r.impact?.itemCount || 0);
                acc.charCount += Number(r.impact?.charCount || 0);
                return acc;
            }, { moduleCount: 0, fieldCount: 0, itemCount: 0, charCount: 0 });

            let listHtml = pendingRows.map(function(r) {
                return '<li>' + (r.title || r.module_key) + '</li>';
            }).join('');

            let highRiskHtml = hasHighRisk
                ? '<div class="alert alert-warning py-2 mb-2"><div class="small fw-semibold mb-1">检测到高风险模块</div><div class="small">请在下方输入确认词 <code>CONFIRM</code> 才能继续应用。</div></div><div class="mb-2"><label class="form-label small mb-1">确认词</label><input type="text" class="form-control form-control-sm" id="modalHighRiskInput" placeholder="请输入 CONFIRM"><div class="form-text text-danger" id="modalHighRiskHint" style="display:none;">确认词不正确，请输入 CONFIRM</div></div>'
                : '';

            const html = '<div class="modal" id="confirmApplyModal" style="display:block;position:fixed;top:0;left:0;width:100%;height:100%;z-index:1055;overflow:auto;background:rgba(0,0,0,0.5);">'
                + '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">'
                + '<div class="modal-header"><h5 class="modal-title">确认应用优化内容</h5><button type="button" class="btn-close" id="modalCancelBtn" aria-label="Close"></button></div>'
                + '<div class="modal-body">'
                + '<div class="small text-muted mb-2">即将覆盖以下模块内容，请确认后继续：</div>'
                + '<div class="border rounded p-2 bg-light-subtle mb-2">'
                + '<div class="small d-flex justify-content-between"><span>模块数量</span><span class="fw-semibold">' + summary.moduleCount + '</span></div>'
                + '<div class="small d-flex justify-content-between"><span>预计影响字段</span><span class="fw-semibold">' + summary.fieldCount + '</span></div>'
                + '<div class="small d-flex justify-content-between"><span>预计影响条目</span><span class="fw-semibold">' + summary.itemCount + '</span></div>'
                + '<div class="small d-flex justify-content-between"><span>预计变更字符</span><span class="fw-semibold">' + summary.charCount + '</span></div>'
                + '</div>'
                + highRiskHtml
                + '<div style="max-height:220px;overflow:auto;"><ul class="small mb-0 ps-3">' + listHtml + '</ul></div>'
                + '</div>'
                + '<div class="modal-footer">'
                + '<button type="button" class="btn btn-outline-secondary" id="modalCancelBtn2">取消</button>'
                + '<button type="button" class="btn btn-primary" id="modalConfirmBtn">确认应用</button>'
                + '</div></div></div></div>';

            const wrapper = document.createElement('div');
            wrapper.id = 'confirmApplyModalWrapper';
            wrapper.innerHTML = html;
            document.body.appendChild(wrapper);
            document.body.style.overflow = 'hidden';

            const closeModal = function() {
                wrapper.remove();
                document.body.style.overflow = '';
            };

            wrapper.querySelector('#modalCancelBtn').addEventListener('click', closeModal);
            wrapper.querySelector('#modalCancelBtn2').addEventListener('click', closeModal);
            wrapper.querySelector('#confirmApplyModal').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });

            wrapper.querySelector('#modalConfirmBtn').addEventListener('click', function() {
                if (hasHighRisk) {
                    const input = wrapper.querySelector('#modalHighRiskInput');
                    const hint = wrapper.querySelector('#modalHighRiskHint');
                    if (!input || input.value.trim() !== 'CONFIRM') {
                        if (hint) hint.style.display = 'block';
                        return;
                    }
                }
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 处理中...';

                const body = {
                    resume_updated_at: context.resumeUpdatedAt,
                    selections: selections,
                };
                console.log('[optimize-compare] Sending apply request:', JSON.stringify(body).substring(0, 500));

                fetch(context.applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': context.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                }).then(function(resp) {
                    console.log('[optimize-compare] Response status:', resp.status);
                    return resp.text().then(function(text) {
                        console.log('[optimize-compare] Response body:', text.substring(0, 500));
                        if (!resp.ok) throw new Error('HTTP ' + resp.status);
                        var result;
                        try { result = JSON.parse(text); } catch(e) { throw new Error('无效的响应格式'); }
                        if (!result.success) throw new Error(result.message || '应用失败');
                        console.log('[optimize-compare] Success, redirecting to:', result.redirect_url);
                        window.location.href = result.redirect_url;
                    });
                }).catch(function(err) {
                    console.error('[optimize-compare] Apply failed:', err);
                    alert('应用失败: ' + (err.message || '请稍后重试'));
                    btn.disabled = false;
                    btn.innerHTML = '确认应用';
                });
            });
        },
        _showModal() {
            const el = document.getElementById('confirmApplyModal');
            if (!el) { console.error('[optimize-compare] modal element not found'); return; }
            el.removeAttribute('aria-hidden');
            el.style.cssText = 'display:block;position:fixed;top:0;left:0;width:100%;height:100%;z-index:1055;overflow:auto;';
            el.classList.add('show');
            if (!document.querySelector('.modal-backdrop-confirm')) {
                const bd = document.createElement('div');
                bd.className = 'modal-backdrop fade show modal-backdrop-confirm';
                bd.style.zIndex = '1050';
                document.body.appendChild(bd);
            }
            document.body.style.overflow = 'hidden';
        },
        _hideModal() {
            const el = document.getElementById('confirmApplyModal');
            if (!el) return;
            el.style.display = 'none';
            el.classList.remove('show');
            el.setAttribute('aria-hidden', 'true');
            document.querySelectorAll('.modal-backdrop-confirm').forEach(function(bd) { bd.remove(); });
            document.body.style.overflow = '';
        },
        selectedCount() {
            return this.selectedKeys.length;
        },
        setDiffFilter(mode) {
            this.diffFilter = mode;
        },
        matchesFilter(row) {
            const filter = String(this.diffFilter || 'all');
            if (filter === 'all') return true;
            if (filter === 'changed') return Boolean(row?.changed);
            if (filter === 'added') return Boolean(row?.changed) && Number(row?.diffStats?.addedChars || 0) > 0;
            if (filter === 'removed') return Boolean(row?.changed) && Number(row?.diffStats?.removedChars || 0) > 0;
            if (filter === 'high_risk') return Boolean(row?.changed) && Boolean(row?.highRisk);
            return true;
        },
        filteredRows() {
            return this.rows.filter((row) => this.matchesFilter(row));
        },
        changedRows() {
            return this.rows.filter((row) => row.changed);
        },
        changedRowsCount() {
            return this.changedRows().length;
        },
        filteredChangedRows() {
            return this.filteredRows().filter((row) => row.changed);
        },
        recommendedRows() {
            return this.filteredRows().filter((row) => row.changed && !row.highRisk);
        },
        cautiousRows() {
            return this.filteredRows().filter((row) => row.highRisk);
        },
        selectedRows() {
            const keySet = new Set(this.selectedKeys);
            return this.rows.filter((row) => keySet.has(row.module_key));
        },
        selectedRowsFiltered() {
            const keySet = new Set(this.selectedKeys);
            return this.filteredRows().filter((row) => keySet.has(row.module_key));
        },
        selectAll() {
            this.selectedKeys = this.rows.map((row) => row.module_key);
        },
        selectChangedOnly() {
            this.selectedKeys = this.changedRows().map((row) => row.module_key);
        },
        selectRecommended() {
            this.selectedKeys = this.rows
                .filter((row) => row.changed && !row.highRisk && this.matchesFilter(row))
                .map((row) => row.module_key);
        },
        selectHighRiskOnly() {
            this.selectedKeys = this.rows
                .filter((row) => row.highRisk && this.matchesFilter(row))
                .map((row) => row.module_key);
        },
        selectFilteredOnly() {
            this.selectedKeys = this.filteredRows().map((row) => row.module_key);
        },
        clearAll() {
            this.selectedKeys = [];
        },
        selectionProgressText() {
            const total = this.filteredRows().length;
            const selected = this.selectedRowsFiltered().length;
            const highRiskSelected = this.selectedRowsFiltered().filter((row) => row.highRisk).length;
            if (total === 0) {
                return '当前过滤下无可选模块';
            }
            if (highRiskSelected > 0) {
                return `已选 ${selected} / ${total}（含 ${highRiskSelected} 个高风险）`;
            }
            return `已选 ${selected} / ${total}`;
        },
        scrollToModule(moduleKey) {
            const el = document.getElementById(`after-module-${moduleKey}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },
        buildSelections() {
            const rowsByKey = Object.fromEntries(this.rows.map((row) => [row.module_key, row]));
            const selections = [];

            this.selectedKeys.forEach((moduleKey) => {
                selections.push({
                    module_key: moduleKey,
                    action: 'replace',
                    allow_sensitive: Boolean(this.allowSensitiveMap[moduleKey]),
                });
            });

            // 去重
            const unique = new Map();
            selections.forEach((selection) => {
                const key = [
                    selection.module_key,
                    selection.field_key || '',
                    String(selection.item_index ?? ''),
                    selection.action,
                    selection.allow_sensitive ? '1' : '0',
                ].join('|');
                unique.set(key, selection);
            });

            return [...unique.values()].filter((selection) => rowsByKey[selection.module_key]);
        },
        pendingSelectionRows() {
            const keySet = new Set(this.pendingSelections.map((item) => item.module_key));
            return this.rows.filter((row) => keySet.has(row.module_key));
        },
        hasPendingHighRisk() {
            return this.pendingSelectionRows().some((row) => Boolean(row?.highRisk));
        },
        highRiskConfirmed() {
            return this.highRiskConfirmText === 'CONFIRM';
        },
        canSubmitApply() {
            if (this.submitting || this.pendingSelections.length === 0) {
                return false;
            }
            if (!this.hasPendingHighRisk()) {
                return true;
            }
            const ok = this.highRiskConfirmed();
            console.log('[optimize-compare] canSubmitApply:', ok, 'highRiskConfirmText:', this.highRiskConfirmText);
            return ok;
        },
        buildPendingReceipt() {
            const rows = this.pendingSelectionRows();
            const summary = this.pendingImpactSummary();
            return {
                generated_at: new Date().toISOString(),
                resume_id: JSON.parse(document.querySelector('[data-json-resume-id]')?.getAttribute('data-json-resume-id') || 'null'),
                optimize_session_id: JSON.parse(document.querySelector('[data-json-session-id]')?.getAttribute('data-json-session-id') || 'null'),
                module_count: summary.moduleCount,
                field_count: summary.fieldCount,
                item_count: summary.itemCount,
                char_count: summary.charCount,
                has_high_risk: this.hasPendingHighRisk(),
                modules: rows.map((row) => ({
                    module_key: row.module_key,
                    title: row.title,
                    type: row.type,
                    high_risk: Boolean(row.highRisk),
                    allow_sensitive: Boolean(this.allowSensitiveMap[row.module_key]),
                    impact: row.impact || { fieldCount: 0, itemCount: 0, charCount: 0 },
                })),
            };
        },
        downloadPendingReceipt() {
            const receipt = this.buildPendingReceipt();
            const blob = new Blob([JSON.stringify(receipt, null, 2)], { type: 'application/json;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `optimize-compare-receipt-${document.querySelector('[data-var-resume-id]')?.getAttribute('data-var-resume-id') || ''}-${document.querySelector('[data-var-session-id]')?.getAttribute('data-var-session-id') || ''}.json`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        },
        pendingImpactSummary() {
            const rows = this.pendingSelectionRows();
            return rows.reduce((acc, row) => {
                acc.moduleCount += 1;
                acc.fieldCount += Number(row?.impact?.fieldCount || 0);
                acc.itemCount += Number(row?.impact?.itemCount || 0);
                acc.charCount += Number(row?.impact?.charCount || 0);
                return acc;
            }, { moduleCount: 0, fieldCount: 0, itemCount: 0, charCount: 0 });
        },
        cancelApplyConfirm() {
            this.confirmApplyVisible = false;
            this.pendingSelections = [];
            this.highRiskConfirmText = '';
            this._hideModal();
        },
        async applySelected() {
            const selections = this.buildSelections();
            console.log('[optimize-compare] applySelected called, selections:', selections.length);
            if (this.submitting) return;
            if (selections.length === 0) {
                if (typeof window.appNotify === 'function') {
                    window.appNotify('请先选择要应用的模块。', 'warning');
                }
                return;
            }
            this.pendingSelections = selections;
            this._showConfirmModal(selections, this.rows, {
                applyUrl: payload.applyUrl,
                csrfToken: payload.csrfToken,
                resumeUpdatedAt: payload.resumeUpdatedAt,
            });
        },
        async confirmApplySelected() {
            // Now handled by pure JS modal
        },
        cancelApplyConfirm() {
            // Now handled by pure JS modal
        },
    };
}
