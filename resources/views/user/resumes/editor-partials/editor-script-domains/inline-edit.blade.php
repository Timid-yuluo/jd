        /* ---------- 内联编辑 ---------- */
        findModIndex(el) {
            let node = el;
            while (node && node !== document.body) {
                const idx = node.getAttribute('data-mod-index');
                if (idx !== null) return parseInt(idx, 10);
                node = node.parentElement;
            }
            return -1;
        },

        inlineUpdate(modIndex, field, value) {
            if (modIndex < 0 || modIndex >= this.modules.length) return;
            this.modules[modIndex].data[field] = value;
        },

        onInlineFocus(modIndex) {
            this.activeIndex = modIndex;
            this.$nextTick(() => {
                const cards = document.getElementById('module-list')?.querySelectorAll('.card');
                cards?.[modIndex]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },

        inlineUpdateItem(modIndex, itemIndex, value) {
            if (modIndex < 0 || modIndex >= this.modules.length) return;
            const items = this.modules[modIndex].data.items;
            if (!Array.isArray(items)) return;
            if (itemIndex >= 0 && itemIndex < items.length) {
                items[itemIndex] = value;
            }
        },

        addInlineItem(modIndex, afterIndex = -1) {
            if (modIndex < 0 || modIndex >= this.modules.length) return;
            const items = this.modules[modIndex].data.items;
            if (!Array.isArray(items)) return;
            if (afterIndex >= 0) {
                items.splice(afterIndex + 1, 0, '');
            } else {
                items.push('');
            }
            this.$nextTick(() => {
                const list = document.querySelectorAll(`[data-mod-index="${modIndex}"] .inline-item`);
                const target = list[afterIndex + 1] || list[list.length - 1];
                if (target) {
                    target.focus();
                    const range = document.createRange();
                    range.selectNodeContents(target);
                    range.collapse(false);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            });
        },

        removeInlineItem(modIndex, itemIndex) {
            if (modIndex < 0 || modIndex >= this.modules.length) return;
            const items = this.modules[modIndex].data.items;
            if (!Array.isArray(items) || items.length <= 1) return;
            items.splice(itemIndex, 1);
        },

        onItemEnter(e, modIndex, itemIndex) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addInlineItem(modIndex, itemIndex);
            }
            if (e.key === 'Backspace' && (e.target.innerText || '').trim() === '') {
                e.preventDefault();
                this.removeInlineItem(modIndex, itemIndex);
            }
        },
