<div id="module-list" class="editor-module-list p-3">
    <div class="editor-module-tip mb-2" x-show="recommendedModuleOrder.length > 0">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div class="small text-muted" x-text="`排序建议：${recommendedModuleOrderText()}`"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" @click.stop="applyRecommendedModuleOrder()">
                <i class="ti ti-arrows-sort me-1"></i>应用排序建议
            </button>
        </div>
    </div>
    <div x-show="modules.length === 0" class="text-center py-5 text-muted">
        <i class="ti ti-layout-list" style="font-size: 48px; opacity: 0.3;"></i>
        <p class="mt-2 mb-1">暂无模块</p>
        <p class="small">点击上方「添加模块」开始编辑简历</p>
    </div>
    <template x-for="(mod, index) in modules" :key="mod._key">
        <div>
            <div class="card mb-2 editor-module-card" :class="{'is-active': activeIndex === index}" @click="activeIndex = index" :data-module-key="mod._key">
                @include('user.resumes.editor-partials.sidebar.module-card-header')
                <div class="card-body p-2" x-show="activeIndex === index && !isCollapsed(mod._key)" x-transition>
                    <div class="editor-module-visual-hint">
                        <div class="editor-module-visual-hint-copy">
                            <div class="editor-module-visual-hint-title">预览区编辑</div>
                            <div class="editor-module-visual-hint-text">状态和模块操作已移到右侧简历预览区域，点击该模块即可直接操作。</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click.stop="scrollToModule(index, 'preview')">
                            <i class="ti ti-eye me-1"></i>去预览区
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
