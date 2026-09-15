<div class="editor-hero-card editor-hero-card--horizontal mb-3">
    <div class="editor-hero-header-row">
        <div class="editor-hero-header-main">
            <div class="editor-section-label mb-1">
                <i class="ti ti-layout-dashboard"></i>编辑工作台
            </div>
            <div class="editor-hero-title-row">
                <i class="ti ti-edit text-primary"></i>
                <span class="editor-hero-title">可视化编辑</span>
                <span class="editor-hero-subtitle">左侧模块编辑，右侧实时预览。</span>
            </div>
        </div>
        <div class="editor-hero-header-side">
            {{-- 草稿提示：放在保存状态 pill 旁边 --}}
            <div class="editor-hero-hint-compact" x-show="draftAvailable" x-cloak
                x-data="{ showDraft: false }"
                @click.outside="showDraft = false">
                <button type="button" class="editor-hero-hint-btn is-header"
                    @click="showDraft = !showDraft"
                    title="未保存草稿">
                    <i class="ti ti-exclamation-circle"></i>
                </button>
                <div class="editor-hero-hint-popover is-draft is-header" x-show="showDraft" x-cloak x-transition>
                    <p class="mb-2">上次编辑内容保存在当前浏览器中，可直接恢复继续编辑。</p>
                    <div class="editor-draft-meta mb-2" x-text="draftRecoverText()"></div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="restoreLocalDraft()">恢复草稿</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="discardLocalDraft()">忽略</button>
                    </div>
                </div>
            </div>
            <span class="editor-status-pill" :class="dirty ? 'is-dirty' : ''">
                <i :class="dirty ? 'ti ti-alert-circle' : 'ti ti-check'"></i>
                <span x-text="dirty ? '有未保存修改' : '当前内容已保存'"></span>
            </span>
            <span class="editor-hero-doc-title">《{{ $resume->title }}》</span>
            <span class="editor-draft-meta" x-show="lastDraftSavedAt" x-text="draftSavedText()"></span>
        </div>
    </div>

    <div class="editor-hero-metrics-inline">
        <div class="editor-hero-metric-inline">
            <div class="editor-hero-metric-icon is-blue">
                <i class="ti ti-cube"></i>
            </div>
            <div class="editor-hero-metric-text">
                <span class="editor-hero-metric-label">模块数量</span>
                <span class="editor-hero-metric-value" x-text="modules.length + ' 个模块'"></span>
            </div>
        </div>
        <div class="editor-hero-metric-inline">
            <div class="editor-hero-metric-icon is-violet">
                <i class="ti ti-briefcase"></i>
            </div>
            <div class="editor-hero-metric-text">
                <span class="editor-hero-metric-label">目标岗位</span>
                <span class="editor-hero-metric-value" x-text="streamTargetJob && streamTargetJob.trim() ? streamTargetJob : '待设置岗位'"></span>
            </div>
        </div>
        <div class="editor-hero-metric-inline">
            {{-- 编辑建议：放在当前字数卡片左侧 --}}
            <div class="editor-hero-hint-compact"
                x-data="{ showHint: false }"
                @click.outside="showHint = false">
                <button type="button" class="editor-hero-hint-btn is-inside"
                    @click="showHint = !showHint"
                    title="编辑建议">
                    <i class="ti ti-exclamation-circle"></i>
                </button>
                <div class="editor-hero-hint-popover" x-show="showHint" x-cloak x-transition>
                    <p>建议先补齐"个人信息"和"求职意向"，再逐步完善经历与成果。</p>
                    <template x-if="moduleOptimizeSuggestions.length > 0">
                        <ul>
                            <template x-for="sug in moduleOptimizeSuggestions.slice(0, 3)" :key="sug.id">
                                <li x-text="sug.text"></li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>
            <div class="editor-hero-metric-icon is-emerald">
                <i class="ti ti-letter-case"></i>
            </div>
            <div class="editor-hero-metric-text">
                <span class="editor-hero-metric-label">当前字数</span>
                <span class="editor-hero-metric-value" x-text="wordCount().chars + ' 字'"></span>
            </div>
        </div>
    </div>

</div>
