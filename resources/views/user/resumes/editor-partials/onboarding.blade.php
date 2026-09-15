<div id="editor-onboarding-overlay" class="editor-onboarding-overlay" x-cloak x-show="showOnboarding && !onboardingIsHiddenByCurrentScope()" x-transition.opacity @click.self="dismissOnboarding(false)" @keydown.escape.window="dismissOnboarding(false)">
    <div class="editor-onboarding-card">
        <div class="editor-onboarding-grid">
            <div class="editor-onboarding-side">
                <div class="editor-section-label mb-2">
                    <i class="ti ti-rocket"></i>首次使用引导
                </div>
                <h3 class="mb-2">3 步快速上手这个编辑器</h3>
                <div class="d-flex align-items-center justify-content-between mb-2 small">
                    <span class="text-muted" x-text="`当前进度 ${onboardingCompletedStepCount()}/3`"></span>
                    <span class="fw-semibold text-primary" x-text="`${onboardingProgressPercent()}%`"></span>
                </div>
                <div class="editor-onboarding-progress mb-3">
                    <div class="editor-onboarding-progress-bar" :style="`width:${onboardingProgressPercent()}%`"></div>
                </div>
                <div class="text-muted mb-3">这个页面已经支持模块编辑、实时预览、AI 优化、草稿恢复和 PDF 导出，第一次进入建议先看这几个关键点。</div>
                <template x-for="(step, stepIdx) in onboardingStepItems()" :key="step.key">
                    <div class="editor-onboarding-step">
                        <span class="editor-onboarding-step-index" :class="step.done ? 'is-done' : ''" x-text="step.done ? '✓' : (stepIdx + 1)"></span>
                        <div class="w-100">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="fw-semibold" x-text="step.label"></div>
                                <span class="badge" :class="step.done ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning'" x-text="step.done ? '已完成' : '待完成'"></span>
                            </div>
                            <div class="small text-muted" x-text="step.desc"></div>
                            <div class="mt-2">
                                <button type="button"
                                        class="btn btn-sm"
                                        :class="step.done ? 'btn-outline-secondary' : 'btn-outline-primary'"
                                        @click="onboardingStepAction(step.key)"
                                        x-text="onboardingStepActionLabel(step.key, step.done)"></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="editor-onboarding-side is-highlight">
                <div class="fw-semibold mb-2">你可以直接这样用</div>
                <div class="small text-muted mb-3">推荐编辑顺序：</div>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" class="btn btn-sm editor-onboarding-chip is-primary" @click="onboardingFocusModule('personal')">个人信息</button>
                    <button type="button" class="btn btn-sm editor-onboarding-chip is-primary" @click="onboardingFocusModule('objective')">求职意向</button>
                    <button type="button" class="btn btn-sm editor-onboarding-chip is-primary" @click="onboardingFocusModule('experience')">实习经历</button>
                    <button type="button" class="btn btn-sm editor-onboarding-chip" @click="onboardingFocusModule('project')">项目经验</button>
                    <button type="button" class="btn btn-sm editor-onboarding-chip" @click="onboardingFocusModule('skill')">技能证书</button>
                    <button type="button" class="btn btn-sm editor-onboarding-chip" @click="onboardingFocusModule('summary')">自我评价</button>
                </div>
                <div class="small text-muted mb-3">常用快捷方式：</div>
                <div class="small mb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1" @click="onboardingQuickSave()"><kbd>Ctrl</kbd> + <kbd>S</kbd> 保存</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1" @click="onboardingQuickUndo()"><kbd>Ctrl</kbd> + <kbd>Z</kbd> 撤销</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1" @click="onboardingOpenShortcuts()"><kbd>?</kbd> 打开快捷键帮助</button>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button id="editor-onboarding-start" type="button" class="btn btn-primary" @click="startEditingFromOnboarding()">
                        <i class="ti ti-check me-1"></i>开始编辑
                    </button>
                    <button id="editor-onboarding-dismiss" type="button" class="btn btn-outline-secondary" @click="dismissOnboarding(true)">
                        <i class="ti ti-eye-off me-1"></i>不再提示
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
