<div class="modal fade" id="ai-suggestion-modal" tabindex="-1" aria-hidden="true" @click.self="closeAiPanelModal('suggestion')">
    <div class="modal-dialog modal-xl modal-dialog-scrollable editor-ai-panel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="editor-ai-modal-head w-100">
                    <div class="editor-ai-modal-top">
                        <div>
                            <h5 class="modal-title"><i class="ti ti-list-check me-2"></i>建议评分</h5>
                            <div class="small text-muted">集中查看模块建议、评分拆解和趋势变化。</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeAiPanelModal('suggestion')"></button>
                    </div>
                    <div class="editor-ai-modal-switcher">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('suggestion', 'analysis')">关键词分析</button>
                        <button type="button" class="btn btn-primary btn-sm">建议评分</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('suggestion', 'result')">优化结果</button>
                    </div>
                    <div class="editor-ai-modal-summary">
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">总览评分</span>
                            <span class="editor-ai-modal-summary-value" x-text="`${currentModuleOverallScore || 0} 分`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">建议数量</span>
                            <span class="editor-ai-modal-summary-value" x-text="`${moduleOptimizeSuggestions?.length || 0} 条`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">趋势变化</span>
                            <span class="editor-ai-modal-summary-value"
                                :class="moduleScoreTrendDelta() >= 0 ? 'text-success' : 'text-danger'"
                                x-text="`${moduleScoreTrendDelta() >= 0 ? '+' : ''}${moduleScoreTrendDelta()} 分`"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <section class="editor-ai-section editor-ai-modal-section">
                    <div class="editor-ai-section-head">
                        <div>
                            <div class="editor-ai-section-kicker">建议</div>
                            <div class="editor-ai-section-title">建议与评分</div>
                        </div>
                        <div class="editor-ai-section-side small text-muted" x-text="`总览 ${currentModuleOverallScore || 0} 分`"></div>
                    </div>
                    <div class="editor-ai-card mb-2">
                        <div class="small fw-medium mb-2">模块优化建议</div>
                        <template x-if="!moduleOptimizeSuggestions?.length">
                            <div class="small text-muted">当前暂无建议，可直接执行优化。</div>
                        </template>
                        <ul class="small text-muted ps-3 mb-0" x-show="moduleOptimizeSuggestions?.length">
                            <template x-for="(tip, idx) in moduleOptimizeSuggestions" :key="tip.id || idx">
                                <li>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-start" @click="previewApplyOptimizeSuggestion(tip)" x-text="tip.text"></button>
                                    <div class="small text-muted" x-show="tip.impact">
                                        <span x-text="`预计 +${tip.impact?.delta ?? 0} 分`"></span>
                                        <span x-show="(tip?.impact?.addGoals?.length || 0) > 0" x-text="` · 目标：${(tip?.impact?.addGoals || []).join('、')}`"></span>
                                        <span x-show="tip?.impact?.nextMode" x-text="` · 模式：${tip?.impact?.nextMode || ''}`"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                        <div class="border rounded p-2 mt-2" x-show="suggestionConfirmPayload" style="border-color:#ffe8b3 !important; background:#fffdf5;">
                            <div class="small fw-medium mb-1">确认应用建议</div>
                            <div class="small text-muted mb-1" x-text="suggestionConfirmPayload?.text"></div>
                            <div class="small text-muted mb-2" x-show="suggestionConfirmPayload?.impact">
                                <span x-text="`预计提升：+${suggestionConfirmPayload?.impact?.delta ?? 0} 分`"></span>
                                <span x-show="(suggestionConfirmPayload?.impact?.addGoals?.length || 0) > 0" x-text="` · 启用目标：${(suggestionConfirmPayload?.impact?.addGoals || []).join('、')}`"></span>
                                <span x-show="suggestionConfirmPayload?.impact?.nextMode" x-text="` · 切换模式：${suggestionConfirmPayload?.impact?.nextMode || ''}`"></span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" @click="confirmApplyOptimizeSuggestion()">确认应用</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="cancelApplyOptimizeSuggestion()">取消</button>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 mt-2 mb-0" x-show="suggestionConflictTips?.length">
                            <div class="small fw-medium mb-1">策略冲突提示</div>
                            <template x-for="(tip, idx) in suggestionConflictTips" :key="idx">
                                <div class="small">- <span x-text="tip"></span></div>
                            </template>
                        </div>
                    </div>

                    <div class="editor-ai-section-grid">
                        <div class="editor-ai-card">
                            <div class="small fw-medium mb-2 d-flex justify-content-between">
                                <span>模块评分拆解（当前）</span>
                                <span class="text-muted" x-text="`总览 ${currentModuleOverallScore || 0} 分`"></span>
                            </div>
                            <template x-if="!moduleScoreBreakdown?.length">
                                <div class="small text-muted">暂无可分析模块。</div>
                            </template>
                            <div class="d-flex flex-column gap-1" x-show="moduleScoreBreakdown?.length">
                                <template x-for="mod in moduleScoreBreakdown.slice(0, 6)" :key="mod.id">
                                    <div class="border rounded px-2 py-1">
                                        <div class="small d-flex justify-content-between">
                                            <span x-text="mod.label"></span>
                                            <span class="fw-medium" x-text="`${mod.score} 分`"></span>
                                        </div>
                                        <div class="small text-muted" x-text="mod.weakReasons.length ? mod.weakReasons.join('、') : '结构完整，可继续精修文案表达'"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="editor-ai-card">
                            <div class="small fw-medium mb-2">分组优化建议</div>
                            <template x-if="!moduleGroupedSuggestions?.length">
                                <div class="small text-muted">当前模块整体较稳定，暂无分组建议。</div>
                            </template>
                            <div class="d-flex flex-column gap-2" x-show="moduleGroupedSuggestions?.length">
                                <template x-for="(row, idx) in moduleGroupedSuggestions" :key="idx">
                                    <div class="border rounded px-2 py-1">
                                        <div class="small fw-medium" x-text="row.group"></div>
                                        <template x-for="(tip, tipIdx) in row.tips" :key="tipIdx">
                                            <div class="small text-muted">- <span x-text="tip"></span></div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="editor-ai-card">
                        <div class="small fw-medium mb-2 d-flex justify-content-between">
                            <span>评分趋势</span>
                            <span class="small" :class="moduleScoreTrendDelta() >= 0 ? 'text-success' : 'text-danger'"
                                x-text="`${moduleScoreTrendDelta() >= 0 ? '+' : ''}${moduleScoreTrendDelta()} 分`"></span>
                        </div>
                        <template x-if="!moduleScoreHistory?.length">
                            <div class="small text-muted">暂无历史记录。</div>
                        </template>
                        <div class="d-flex flex-column gap-1" x-show="moduleScoreHistory?.length">
                            <template x-for="(row, idx) in moduleScoreHistory.slice(-6)" :key="idx">
                                <div class="small d-flex justify-content-between text-muted">
                                    <span x-text="row.label"></span>
                                    <span x-text="`${row.score} 分`"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="closeAiPanelModal('suggestion')">关闭</button>
            </div>
        </div>
    </div>
</div>
