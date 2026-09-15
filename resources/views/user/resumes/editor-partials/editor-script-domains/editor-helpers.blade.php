        captureModuleCompletionSnapshot() {
            _state.moduleCompletionSnapshot = Object.fromEntries(
                this.modules.map((mod, index) => [mod._key || String(index), this.moduleCompletionPercent(mod)])
            );
        },

        animateModuleCard(moduleKey, className) {
            if (!moduleKey || !className) {
                return;
            }

            const card = document.querySelector(`[data-module-key="${moduleKey}"]`);
            if (!card) {
                return;
            }

            card.classList.remove(className);
            void card.offsetWidth;
            card.classList.add(className);
            window.setTimeout(() => card.classList.remove(className), 950);
        },

        handleCompletionAnimations() {
            if (!_state.moduleCompletionSnapshot) {
                this.captureModuleCompletionSnapshot();
                return;
            }

            const nextSnapshot = {};
            this.modules.forEach((mod, index) => {
                const key = mod._key || String(index);
                const currentPercent = this.moduleCompletionPercent(mod);
                const previousPercent = _state.moduleCompletionSnapshot[key] ?? currentPercent;
                nextSnapshot[key] = currentPercent;
                if (currentPercent > previousPercent && currentPercent >= 35) {
                    this.$nextTick(() => this.animateModuleCard(key, 'module-completion-pop'));
                }
            });
            _state.moduleCompletionSnapshot = nextSnapshot;
        },

        moduleTypeLabel(type) {
            const map = this.config.moduleTypes || {};
            return map[type] || type;
        },

        modulePreviewHeading(mod) {
            const data = mod?.data || {};
            if (mod?.type === 'objective') {
                return data.target_job || '待识别目标岗位';
            }
            if (typeof data.title === 'string' && data.title.trim() !== '') {
                return data.title.trim();
            }
            return '识别后的模块内容';
        },
