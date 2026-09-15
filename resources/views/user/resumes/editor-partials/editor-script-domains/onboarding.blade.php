        onboardingStorageKey() {
            return `resume-editor-onboarding:${this.resolvedResumeStorageScope()}`;
        },

        onboardingCookieKey() {
            return `resume_editor_onboarding_${this.resolvedResumeStorageScope()}`;
        },

        onboardingCookieValue(name) {
            const needle = `${name}=`;
            const chunks = String(document.cookie || '').split(';');
            for (const chunk of chunks) {
                const part = chunk.trim();
                if (part.startsWith(needle)) {
                    return decodeURIComponent(part.slice(needle.length));
                }
            }
            return '';
        },

        setOnboardingSeenHidden() {
            const key = this.onboardingStorageKey();
            const cookieKey = this.onboardingCookieKey();
            try {
                window.localStorage.setItem(key, 'hidden');
            } catch (error) {}
            try {
                document.cookie = `${cookieKey}=hidden; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
            } catch (error) {}
        },

        onboardingIsHiddenByCurrentScope() {
            const key = this.onboardingStorageKey();
            const cookieKey = this.onboardingCookieKey();
            const storageHidden = (() => {
                try {
                    return window.localStorage.getItem(key) === 'hidden';
                } catch (error) {
                    return false;
                }
            })();
            const cookieHidden = this.onboardingCookieValue(cookieKey) === 'hidden';
            return storageHidden || cookieHidden;
        },

        onboardingLegacyStorageKeys() {
            const keys = [
                `resume-editor-onboarding:${this.resumeId}`,
                `resume-editor-onboarding:${window.resumeEditorConfig?.resumeId ?? ''}`,
                'resume-editor-onboarding:default',
                'resume-editor-onboarding:null',
                'resume-editor-onboarding:undefined',
            ];
            return [...new Set(keys.map((key) => String(key).trim()).filter((key) => key !== ''))];
        },

        maybeShowOnboarding() {
            try {
                if (this.onboardingIsHiddenByCurrentScope()) {
                    this.showOnboarding = false;
                    return;
                }

                const currentKey = this.onboardingStorageKey();
                const legacySeen = this.onboardingLegacyStorageKeys()
                    .some((key) => key !== currentKey && window.localStorage.getItem(key) === 'hidden');

                if (legacySeen) {
                    this.setOnboardingSeenHidden();
                    this.showOnboarding = false;
                    return;
                }

                this.showOnboarding = true;
            } catch (error) {
                this.showOnboarding = false;
            }
        },

        dismissOnboarding(remember = false) {
            if (remember) {
                this.setOnboardingSeenHidden();
            }

            this.showOnboarding = false;
        },

        onboardingStepItems() {
            const hasBaseModules = ['personal', 'objective', 'experience']
                .every((type) => this.modules.some((mod) => String(mod?.type || '') === type));
            const baseDone = hasBaseModules && this.modules
                .filter((mod) => ['personal', 'objective', 'experience'].includes(String(mod?.type || '')))
                .every((mod) => this.moduleCompletionPercent(mod) >= 60);

            const previewDone = this.onboardingPreviewTouched
                || this.previewScale !== 100
                || this.isPreviewFullscreen;

            const optimizeDone = this.hasTargetJob()
                && (this.optimizedContentForDiff !== '' || this.resumeAtsScore !== null);

            return [
                { key: 'base', done: baseDone, label: '先补基础模块', desc: '优先完成个人信息、求职意向、实习经历，空模块可直接点"插入示例"。' },
                { key: 'preview', done: previewDone, label: '边改边看右侧预览', desc: '可切换模板、主题、缩放和全屏预览，观察最终投递版式。' },
                { key: 'optimize', done: optimizeDone, label: '再做 AI 优化与 ATS', desc: '目标岗位写得越具体，优化结果和 ATS 评分越准确。' },
            ];
        },

        onboardingCompletedStepCount() {
            return this.onboardingStepItems().filter((item) => item.done).length;
        },

        onboardingProgressPercent() {
            const total = this.onboardingStepItems().length;
            if (total === 0) {
                return 0;
            }
            return Math.round((this.onboardingCompletedStepCount() / total) * 100);
        },

        onboardingStepActionLabel(stepKey, done = false) {
            if (done) {
                return '查看';
            }
            const labels = {
                base: '去补齐',
                preview: '去操作',
                optimize: '去优化',
            };
            return labels[String(stepKey || '')] || '去完成';
        },

        onboardingStepAction(stepKey) {
            const key = String(stepKey || '');
            if (key === 'base') {
                this.startEditingFromOnboarding();
                return;
            }

            if (key === 'preview') {
                this.dismissOnboarding(false);
                this.onboardingPreviewTouched = true;
                this.$nextTick(() => {
                    const previewArea = document.getElementById('editor-preview-area');
                    previewArea?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                this.showToast('已定位到右侧预览区，可切换模板/主题继续操作', 'info');
                return;
            }

            if (key === 'optimize') {
                this.dismissOnboarding(false);
                this.$nextTick(() => {
                    const panel = document.getElementById('onboarding-ai-tools-panel');
                    panel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const input = document.getElementById('onboarding-target-job-input');
                    if (input && typeof input.focus === 'function') {
                        input.focus();
                        if (typeof input.select === 'function') {
                            input.select();
                        }
                    }
                });
                this.showToast('已定位到 AI 工具台，请先填写目标岗位再执行优化或 ATS', 'info');
                return;
            }

            this.dismissOnboarding(false);
        },

        onboardingFocusModule(type) {
            const targetType = String(type || '').trim();
            if (targetType === '') {
                return;
            }
            const index = this.modules.findIndex((mod) => String(mod?.type || '') === targetType);
            if (index >= 0) {
                this.dismissOnboarding(false);
                this.focusFirstIncompleteField(index);
                return;
            }
            this.dismissOnboarding(false);
            this.addModuleOfType(targetType);
            this.showToast(`已新增「${this.moduleTypeLabel(targetType)}」模块`, 'success');
        },

        onboardingQuickSave() {
            this.dismissOnboarding(false);
            const form = document.querySelector('form[action]');
            if (!form) {
                this.showToast('未找到可保存的表单', 'warning');
                return;
            }
            this.submitForm({ target: form, preventDefault() {}, forceSubmit: false });
        },

        onboardingQuickUndo() {
            this.dismissOnboarding(false);
            this.undo();
        },

        onboardingOpenShortcuts() {
            this.dismissOnboarding(false);
            this.showShortcuts = true;
        },

        startEditingFromOnboarding() {
            this.dismissOnboarding(false);
            const preferredTypes = ['personal', 'objective', 'experience', 'project', 'skill', 'summary'];
            for (const type of preferredTypes) {
                const index = this.modules.findIndex((mod) => String(mod?.type || '') === type);
                if (index < 0) {
                    continue;
                }
                if (this.moduleCompletionPercent(this.modules[index]) < 90) {
                    this.focusFirstIncompleteField(index);
                    return;
                }
            }
            if (this.modules.length > 0) {
                this.scrollToModule(0);
                return;
            }
            this.addModuleOfType('personal');
            this.showToast('已为你创建个人信息模块，可直接开始填写', 'success');
        },
