        openModuleDetail(index = this.activeIndex) {
            if (typeof index !== 'number' || !this.modules[index]) {
                return;
            }

            this.activeIndex = index;
            this.detailIndex = index;
            this.detailOpen = true;
            this.previewActionVisible = false;
            this.previewActionMuted = false;
        },

        closeModuleDetail() {
            this.detailOpen = false;
            this.$nextTick(() => this.syncPreviewModuleAction());
        },

        handlePreviewModuleClick(event) {
            if (event.target.closest('.editor-preview-module-action')) {
                return;
            }

            const modEl = event.target.closest('[data-mod-index]');
            if (!modEl) {
                _state.previewActionPrimed = false;
                _state.previewActionIndex = null;
                this.previewActionVisible = false;
                return;
            }

            const index = Number.parseInt(modEl.dataset.modIndex, 10);
            if (Number.isNaN(index) || !this.modules[index]) {
                return;
            }

            _state.previewActionPrimed = true;
            _state.previewActionIndex = index;
            this.activeIndex = index;
            this.$nextTick(() => this.syncPreviewModuleAction(index, { muted: true }));
        },

        handlePreviewModuleHover(event) {
            if (this.detailOpen || !this.previewActionVisible) {
                return;
            }

            if (event.target.closest('.editor-preview-module-action')) {
                this.previewActionMuted = false;
                return;
            }

            const modEl = event.target.closest('[data-mod-index]');
            if (!modEl) {
                this.previewActionMuted = true;
                return;
            }

            const index = Number.parseInt(modEl.dataset.modIndex, 10);
            if (!Number.isNaN(index) && index === this.activeIndex) {
                this.previewActionMuted = false;
                return;
            }

            this.previewActionMuted = true;
        },

        handlePreviewModuleLeave() {
            if (this.detailOpen || !this.previewActionVisible) {
                return;
            }

            this.previewActionMuted = true;
        },

        getPreviewModuleOffset(modEl, container) {
            let top = 0;
            let left = 0;
            let current = modEl;

            while (current && current !== container) {
                top += current.offsetTop || 0;
                left += current.offsetLeft || 0;
                current = current.offsetParent;
            }

            return {
                top,
                left,
                width: modEl.offsetWidth || 0,
            };
        },

        syncPreviewModuleIndicators(preview) {
            if (!preview) {
                return;
            }

            preview.querySelectorAll('[data-mod-index]').forEach((node) => {
                const index = Number.parseInt(node.dataset.modIndex, 10);
                const mod = Number.isNaN(index) ? null : this.modules[index];

                if (!mod) {
                    node.removeAttribute('data-editor-completion-label');
                    node.removeAttribute('data-editor-completion-score');
                    node.removeAttribute('data-editor-completion-tone');
                    return;
                }

                const percent = this.moduleCompletionPercent(mod);
                node.dataset.editorCompletionLabel = this.moduleCompletionLabel(mod);
                node.dataset.editorCompletionScore = `${percent}%`;
                node.dataset.editorCompletionTone = percent >= 75
                    ? 'high'
                    : (percent >= 35 ? 'medium' : 'low');
            });
        },

        syncPreviewModuleAction(index = null, options = {}) {
            this.$nextTick(() => {
                const preview = document.getElementById('resume-preview');
                preview?.querySelectorAll('.is-editor-active-preview-module').forEach((node) => {
                    node.classList.remove('is-editor-active-preview-module');
                });
                this.syncPreviewModuleIndicators(preview);
                this.applyKeywordHighlights();

                const targetIndex = typeof index === 'number'
                    ? index
                    : (_state.previewActionIndex ?? this.activeIndex);

                if (this.detailOpen || !this.modules[this.activeIndex]) {
                    this.previewActionVisible = false;
                    return;
                }

                const shouldShow = _state.previewActionPrimed && targetIndex === this.activeIndex;
                if (!shouldShow || !this.modules[targetIndex]) {
                    this.previewActionVisible = false;
                    return;
                }

                const modEl = preview?.querySelector(`[data-mod-index="${targetIndex}"]`);
                if (!preview || !modEl) {
                    this.previewActionVisible = false;
                    return;
                }

                const toolbarWidth = window.innerWidth < 992 ? 248 : 336;
                const offset = this.getPreviewModuleOffset(modEl, preview);
                const top = Math.max(6, Math.round(offset.top + 6));
                const left = Math.max(
                    8,
                    Math.min(
                        Math.round(offset.left + offset.width - toolbarWidth - 8),
                        preview.clientWidth - toolbarWidth - 8
                    )
                );

                const muted = typeof options.muted === 'boolean'
                    ? options.muted
                    : false;

                const activeEl = preview?.querySelector(`[data-mod-index="${this.activeIndex}"]`);
                activeEl?.classList.add('is-editor-active-preview-module');
                _state.previewActionIndex = targetIndex;
                this.previewActionMuted = muted;
                this.previewActionStyle = `top:${top}px;left:${left}px;`;
                this.previewActionVisible = true;
            });
        },

        applyKeywordHighlights() {
            const preview = document.getElementById('resume-preview');
            if (!preview) return;

            // 移除旧高亮
            preview.querySelectorAll('.keyword-highlight-hit').forEach(el => {
                el.replaceWith(document.createTextNode(el.textContent));
            });
            preview.normalize();

            if (!this.keywordHighlightEnabled) return;

            const keywords = (this.jdExtractedKeywords || [])
                .map(k => String(k).trim())
                .filter(k => k.length >= 2);
            if (keywords.length === 0) return;

            const walker = document.createTreeWalker(preview, NodeFilter.SHOW_TEXT, null);
            const textNodes = [];
            while (walker.nextNode()) textNodes.push(walker.currentNode);

            const escaped = keywords.map(k => k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
            const regex = new RegExp('(' + escaped.join('|') + ')', 'gi');

            textNodes.forEach(node => {
                const text = node.textContent;
                if (!regex.test(text)) return;
                regex.lastIndex = 0;

                const frag = document.createDocumentFragment();
                let lastIdx = 0;
                let match;
                while ((match = regex.exec(text)) !== null) {
                    if (match.index > lastIdx) {
                        frag.appendChild(document.createTextNode(text.slice(lastIdx, match.index)));
                    }
                    const span = document.createElement('span');
                    span.className = 'keyword-highlight-hit';
                    span.textContent = match[0];
                    span.title = '目标岗位关键词';
                    frag.appendChild(span);
                    lastIdx = regex.lastIndex;
                }
                if (lastIdx < text.length) {
                    frag.appendChild(document.createTextNode(text.slice(lastIdx)));
                }
                node.parentNode.replaceChild(frag, node);
            });
        },
