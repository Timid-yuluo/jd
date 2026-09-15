        hasOptimizationResult() {
            return this.optimizedContentForDiff !== '' || this.streamStatus === 'done';
        },

        isAtsStale() {
            if (this.resumeAtsScore === null) {
                return false;
            }
            return _state.lastAtsSignature !== this.buildModulesSignature();
        },

        isOptimizeStale() {
            if (!this.hasOptimizationResult()) {
                return false;
            }
            return _state.lastOptimizeSignature !== this.buildOptimizeSignature();
        },

        showResultRefreshHint() {
            return this.isAtsStale() || this.isOptimizeStale();
        },

        showResultRefreshActions() {
            return this.showResultRefreshHint();
        },

        resultRefreshHintText() {
            if (this.isAtsStale() && this.isOptimizeStale()) {
                return '当前内容已变更，建议重新执行 ATS 评分和 AI 岗位定向优化。';
            }
            if (this.isAtsStale()) {
                return '当前内容已变更，建议重新执行 ATS 评分。';
            }
            if (this.isOptimizeStale()) {
                return '当前内容或目标岗位已变更，建议重新执行 AI 岗位定向优化。';
            }
            return '';
        },

        isActionLocked(action) {
            return this.aiActionLocks[action] === true;
        },

        isAnyAiActionRunning() {
            return Object.values(this.aiActionLocks).some((locked) => locked === true);
        },

        tryStartAiAction(action, cooldownMs = 800) {
            if (this.isActionLocked(action)) {
                this.showToast('操作正在处理中，请稍候', 'info');
                return false;
            }

            const now = Date.now();
            const lastAt = _state.aiActionLastAt[action] ?? 0;
            if (now - lastAt < cooldownMs) {
                this.showToast('请勿频繁重复点击', 'info');
                return false;
            }

            if (this.isAnyAiActionRunning()) {
                this.showToast('请等待当前 AI 操作完成', 'info');
                return false;
            }

            _state.aiActionLastAt[action] = now;
            this.aiActionLocks[action] = true;
            return true;
        },

        finishAiAction(action) {
            this.aiActionLocks[action] = false;
        },

        @include('user.resumes.editor-partials.editor-script-domains.ai-modal-management')

        @include('user.resumes.editor-partials.editor-script-domains.ai-operations')
