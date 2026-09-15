<div class="modal fade" id="ai-fresh-graduate-preset-modal" tabindex="-1" aria-hidden="true" @click.self="closeFreshGraduatePresetModal()">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <span x-text="freshGraduateTrackMeta().title"></span>
                        <span class="badge bg-primary-lt text-primary" x-text="freshGraduateTrackMeta().badge"></span>
                    </h5>
                    <div class="small text-muted" x-text="freshGraduateTrackMeta().summary"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeFreshGraduatePresetModal()"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2">该预设将自动切换到应届生策略包，并优先优化以下内容：</div>
                <ul class="small mb-3 ps-3">
                    <template x-for="(tip, idx) in freshGraduateGuideTips()" :key="`fresh-guide-tip-${idx}`">
                        <li x-text="tip"></li>
                    </template>
                </ul>
                <div class="border rounded p-2 bg-light-subtle mb-3">
                    <div class="small fw-medium mb-1">本次将应用</div>
                    <div class="small text-muted mb-1">优化模式：<span x-text="freshGraduatePresetPlan().mode"></span></div>
                    <div class="d-flex flex-wrap gap-1">
                        <template x-for="(label, idx) in freshGraduatePresetPlanLabels()" :key="`fresh-plan-${idx}`">
                            <span class="badge bg-primary-lt text-primary" x-text="label"></span>
                        </template>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">预计影响模块</div>
                            <div class="fw-semibold" x-text="`${freshGraduateEstimatedTouchedModulesCount()} 个`"></div>
                            <div class="small text-muted" x-show="freshGraduateEstimatedTouchedModuleTypes().length > 0"
                                x-text="freshGraduateEstimatedTouchedModuleTypes().slice(0, 3).map((type) => moduleTypeLabel(type)).join(' / ')"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">关键词提升机会</div>
                            <div class="fw-semibold"
                                x-text="freshGraduateKeywordOpportunitySummary().total > 0
                                    ? `+${freshGraduateKeywordOpportunitySummary().potentialGain}`
                                    : '待提取关键词'"></div>
                            <div class="small text-muted"
                                x-text="freshGraduateKeywordOpportunitySummary().total > 0
                                    ? `缺口 ${freshGraduateKeywordOpportunitySummary().missing} / ${freshGraduateKeywordOpportunitySummary().total}`
                                    : '建议先补充 JD 并提取关键词'"></div>
                        </div>
                    </div>
                </div>
                <div class="border rounded p-2 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="small fw-medium">应用前完整度检查</div>
                        <span class="badge" :class="freshGraduateReadinessBadgeClass()" x-text="freshGraduateReadinessSummary().label"></span>
                    </div>
                    <div class="small text-muted mb-2" x-text="`已满足 ${freshGraduateReadinessSummary().passedCount}/${freshGraduateReadinessSummary().total} 项核心模块`"></div>
                    <div class="d-flex flex-column gap-1">
                        <template x-for="row in freshGraduateReadinessSummary().checks" :key="`fresh-ready-${row.key}`">
                            <div class="small d-flex align-items-center justify-content-between">
                                <span :class="{ 'fw-semibold text-success': isFreshGraduateModuleJustAdded(row.key) }" x-text="row.label"></span>
                                <span :class="row.passed ? 'text-success' : 'text-warning'"
                                    x-text="row.passed ? (isFreshGraduateModuleJustAdded(row.key) ? '刚补齐' : '已具备') : '建议补齐'"></span>
                            </div>
                        </template>
                    </div>
                    <div class="small text-warning mt-2" x-show="freshGraduateReadinessSummary().missing.length > 0"
                        x-text="`建议优先补齐：${freshGraduateReadinessSummary().missing.map((item) => item.label).join(' / ')}`"></div>
                    <div class="d-flex flex-wrap gap-1 mt-2" x-show="freshGraduateReadinessSummary().missing.length > 0">
                        <template x-for="item in freshGraduateReadinessSummary().missing" :key="`fresh-missing-action-${item.key}`">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addFreshGraduateMissingModule(item.key)" x-text="freshGraduateMissingModuleActionLabel(item.key)"></button>
                        </template>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="freshGraduateDontShowAgain" x-model="freshGraduateGuideDontShowAgain">
                    <label class="form-check-label small" for="freshGraduateDontShowAgain">不再提示</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" @click="closeFreshGraduatePresetModal()">取消</button>
                <button type="button" class="btn btn-primary" :class="{ 'btn-warning': freshGraduateReadinessSummary().level === 'low' }" @click="confirmFreshGraduatePresetFromModal()" x-text="freshGraduateApplyButtonLabel()"></button>
            </div>
        </div>
    </div>
</div>
