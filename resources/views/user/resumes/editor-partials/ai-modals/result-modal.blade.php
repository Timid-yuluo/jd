<div class="modal fade" id="ai-result-modal" tabindex="-1" aria-hidden="true" @click.self="closeAiPanelModal('result')">
    <div class="modal-dialog modal-xl modal-dialog-scrollable editor-ai-panel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="editor-ai-modal-head w-100">
                    <div class="editor-ai-modal-top">
                        <div>
                            <h5 class="modal-title"><i class="ti ti-git-compare me-2"></i>优化结果</h5>
                            <div class="small text-muted">先确认质量，再选择版本与回写范围，最后应用到当前简历。</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeAiPanelModal('result')"></button>
                    </div>
                    <div class="editor-ai-modal-switcher">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('result', 'analysis')">关键词分析</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('result', 'suggestion')">建议评分</button>
                        <button type="button" class="btn btn-primary btn-sm">优化结果</button>
                    </div>
                    <div class="editor-ai-modal-summary">
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">评分变化</span>
                            <span class="editor-ai-modal-summary-value"
                                :class="(optimizeMetricsCompare?.delta ?? 0) >= 0 ? 'text-success' : 'text-danger'"
                                x-text="`${(optimizeMetricsCompare?.delta ?? 0) >= 0 ? '+' : ''}${optimizeMetricsCompare?.delta ?? 0}`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">可选版本</span>
                            <span class="editor-ai-modal-summary-value" x-text="`${optimizeVersionHistory?.length || 0} 个`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">回归状态</span>
                            <span class="editor-ai-modal-summary-value"
                                :class="regressionValidationSummary?.status === 'ok' ? 'text-success' : 'text-warning'"
                                x-text="regressionValidationSummary?.status === 'ok' ? '通过' : '需复核'"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <template x-if="optimizedContentForDiff === ''">
                    <div class="alert alert-info py-2 mb-0">当前暂无优化结果，请先执行 AI 优化。</div>
                </template>
                <section class="editor-ai-section editor-ai-modal-section" x-show="optimizedContentForDiff !== ''">
                    <div class="editor-ai-section-head">
                        <div>
                            <div class="editor-ai-section-kicker">结果</div>
                            <div class="editor-ai-section-title">优化结果</div>
                        </div>
                        <div class="editor-ai-section-side small text-muted">可先对比，再决定是否应用</div>
                    </div>

                    <div class="editor-ai-result-decision mb-2">
                        <div class="editor-ai-result-decision-head">
                            <div>
                                <div class="editor-ai-result-decision-title">结果决策台</div>
                                <div class="editor-ai-result-decision-note">先确认质量，再选择版本与回写范围，最后应用到当前简历。</div>
                            </div>
                            <div class="editor-ai-result-decision-badges">
                                <span class="badge bg-success-lt text-success" x-show="(optimizeMetricsCompare?.delta ?? 0) >= 0"
                                    x-text="`评分 ${((optimizeMetricsCompare?.delta ?? 0) >= 0 ? '+' : '') + (optimizeMetricsCompare?.delta ?? 0)}`"></span>
                                <span class="badge bg-warning-lt text-warning" x-show="regressionValidationSummary?.status !== 'ok'">
                                    需复核
                                </span>
                                <span class="badge bg-primary-lt text-primary"
                                    x-text="`版本 ${(optimizeVersionHistory?.length || 0)}`"></span>
                                <span class="badge bg-secondary-lt text-secondary"
                                    x-text="`回写 ${(applySelectedModuleTypes?.length || 0) > 0 ? applySelectedModuleTypes.length + ' 项' : '全部模块'}`"></span>
                            </div>
                        </div>

                        <div class="editor-ai-result-steps">
                            <div class="editor-ai-result-step is-ready">
                                <span class="editor-ai-result-step-index">1</span>
                                <span>查看优化摘要</span>
                            </div>
                            <div class="editor-ai-result-step"
                                :class="(optimizeVersionHistory?.length || 0) >= 2 ? 'is-ready' : 'is-current'">
                                <span class="editor-ai-result-step-index">2</span>
                                <span>A/B 选择版本</span>
                            </div>
                            <div class="editor-ai-result-step"
                                :class="(applySelectedModuleTypes?.length || 0) > 0 ? 'is-ready' : 'is-current'">
                                <span class="editor-ai-result-step-index">3</span>
                                <span>确认回写范围</span>
                            </div>
                            <div class="editor-ai-result-step"
                                :class="regressionValidationSummary?.status === 'ok' ? 'is-ready' : ''">
                                <span class="editor-ai-result-step-index">4</span>
                                <span>应用到简历</span>
                            </div>
                        </div>

                        <div class="editor-ai-result-summary-grid">
                            <div class="editor-ai-result-summary-card">
                                <span class="editor-ai-result-summary-label">结构化总分</span>
                                <span class="editor-ai-result-summary-value"
                                    x-text="`${optimizeMetricsCompare?.before?.score ?? 0} → ${optimizeMetricsCompare?.after?.score ?? 0}`"></span>
                            </div>
                            <div class="editor-ai-result-summary-card">
                                <span class="editor-ai-result-summary-label">回归状态</span>
                                <span class="editor-ai-result-summary-value"
                                    :class="regressionValidationSummary?.status === 'ok' ? 'text-success' : 'text-warning'"
                                    x-text="regressionValidationSummary?.status === 'ok' ? '通过' : '需复核'"></span>
                            </div>
                            <div class="editor-ai-result-summary-card">
                                <span class="editor-ai-result-summary-label">可选版本</span>
                                <span class="editor-ai-result-summary-value"
                                    x-text="`${optimizeVersionHistory?.length || 0} 个`"></span>
                            </div>
                            <div class="editor-ai-result-summary-card">
                                <span class="editor-ai-result-summary-label">本次回写</span>
                                <span class="editor-ai-result-summary-value"
                                    x-text="(applySelectedModuleTypes?.length || 0) > 0 ? `${applySelectedModuleTypes.length} 个模块` : '默认全部模块'"></span>
                            </div>
                        </div>

                        <div class="editor-ai-result-decision-tip"
                            x-text="regressionValidationSummary?.status !== 'ok'
                                ? '当前结果存在需复核项，建议先查看差异和回归验证，再决定是否应用。'
                                : (applySelectedModuleTypes?.length || 0) > 0
                                    ? '当前已切换为模块化回写，只会应用你选中的模块。'
                                    : '当前未限制回写范围，点击应用后会默认覆盖全部可回写模块。'"></div>
                    </div>

                    <div class="editor-ai-card mb-2" x-show="optimizeChangesSummary">
                        <div class="small text-muted" x-text="optimizeChangesSummary"></div>
                    </div>

                    <div class="editor-ai-card mb-2" x-show="optimizeMetricsCompare">
                        <div class="small fw-medium mb-1">优化效果对比（结构化评分）</div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span x-text="`优化前：${optimizeMetricsCompare?.before?.score ?? 0}`"></span>
                            <span x-text="`优化后：${optimizeMetricsCompare?.after?.score ?? 0}`"></span>
                            <span class="fw-medium" :class="(optimizeMetricsCompare?.delta ?? 0) >= 0 ? 'text-success' : 'text-danger'"
                                x-text="`变化：${(optimizeMetricsCompare?.delta ?? 0) >= 0 ? '+' : ''}${optimizeMetricsCompare?.delta ?? 0}`"></span>
                        </div>
                        <div class="mt-2">
                            <template x-for="metric in [
                                { key: 'structure', label: '结构' },
                                { key: 'quantified', label: '量化' },
                                { key: 'ats', label: 'ATS匹配' },
                                { key: 'clarity', label: '表达清晰' },
                                { key: 'richness', label: '内容丰富' },
                            ]" :key="metric.key">
                                <div class="small d-flex justify-content-between text-muted">
                                    <span x-text="metric.label"></span>
                                    <span x-text="`${optimizeMetricsCompare?.before?.details?.[metric.key] ?? 0} → ${optimizeMetricsCompare?.after?.details?.[metric.key] ?? 0}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="editor-ai-card mb-2" x-show="regressionValidationSummary">
                        <div class="small fw-medium mb-1 d-flex justify-content-between">
                            <span>回归验证面板</span>
                            <span :class="regressionValidationSummary?.status === 'ok' ? 'text-success' : 'text-warning'"
                                x-text="regressionValidationSummary?.status === 'ok' ? '通过' : '需复核'"></span>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>ATS 预测</span>
                            <span x-text="`${regressionValidationSummary?.atsBefore ?? 0} → ${regressionValidationSummary?.atsAfter ?? 0}`"></span>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>关键词覆盖</span>
                            <span x-text="`${regressionValidationSummary?.keywordRateBefore ?? 0}% → ${regressionValidationSummary?.keywordRateAfter ?? 0}%`"></span>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>模块短板数</span>
                            <span x-text="`${regressionValidationSummary?.weakModulesBefore ?? 0} → ${regressionValidationSummary?.weakModulesAfter ?? 0}`"></span>
                        </div>
                        <div class="small text-muted d-flex justify-content-between">
                            <span>风险提示数</span>
                            <span x-text="regressionValidationSummary?.riskCount ?? 0"></span>
                        </div>
                    </div>

                    <div class="editor-ai-card mb-2">
                        <div class="small fw-medium mb-2">A/B 优化版本对比</div>
                        <template x-if="(optimizeVersionHistory?.length || 0) < 2">
                            <div class="small text-muted">至少完成 2 次优化后可进行 A/B 对比。</div>
                        </template>
                        <div x-show="(optimizeVersionHistory?.length || 0) >= 2">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <select class="form-select form-select-sm" x-model="selectedOptimizeVersionA">
                                        <template x-for="item in optimizeVersionOptions()" :key="item.id">
                                            <option :value="item.id" x-text="`A：${item.label}`"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select class="form-select form-select-sm" x-model="selectedOptimizeVersionB">
                                        <template x-for="item in optimizeVersionOptions()" :key="item.id">
                                            <option :value="item.id" x-text="`B：${item.label}`"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="small text-muted mb-2" x-show="optimizeVersionCompareSummary()">
                                <div class="d-flex gap-2 align-items-center mb-1">
                                    <span class="badge" :class="optimizeVersionCompareSummary()?.deltaScore > 0 ? 'bg-success-lt text-success' : (optimizeVersionCompareSummary()?.deltaScore < 0 ? 'bg-danger-lt text-danger' : 'bg-secondary-lt')" x-text="`分值 ${optimizeVersionCompareSummary()?.deltaScore >= 0 ? '+' : ''}${optimizeVersionCompareSummary()?.deltaScore || 0}`"></span>
                                    <span class="badge bg-secondary-lt text-secondary" x-text="`文本 ${optimizeVersionCompareSummary()?.deltaLength >= 0 ? '+' : ''}${optimizeVersionCompareSummary()?.deltaLength || 0}字`"></span>
                                    <span class="badge bg-warning-lt text-warning" x-show="optimizeVersionCompareSummary()?.modeChanged" x-text="`模式 ${optimizeVersionCompareSummary()?.versionA?.mode || 'balanced'} → ${optimizeVersionCompareSummary()?.versionB?.mode || 'balanced'}`"></span>
                                    <span class="badge bg-info-lt text-info" x-show="optimizeVersionCompareSummary()?.goalsChanged">方向变更</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="useOptimizeVersionAsCurrent(selectedOptimizeVersionA)">
                                    使用 A 版本
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="useOptimizeVersionAsCurrent(selectedOptimizeVersionB)">
                                    使用 B 版本
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="editor-ai-card mb-2">
                        <div class="small fw-medium mb-1">模块化回写（可选）</div>
                        <div class="small text-muted mb-2">不勾选则默认回写全部模块。</div>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="selectAllApplyModuleTypes()" :disabled="isAnyAiActionRunning()">全选模块</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearApplyModuleTypes()" :disabled="isAnyAiActionRunning()">清空选择</button>
                            <span class="small text-muted ms-auto" x-text="`已选 ${applySelectedModuleTypes.length}`"></span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <template x-for="item in applyModuleTypeOptions()" :key="item.type">
                                <button type="button" class="btn btn-sm"
                                    :class="applySelectedModuleTypes.includes(item.type) ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="toggleApplyModuleType(item.type)"
                                    x-text="item.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="editor-ai-result-actionbar">
                        <div class="editor-ai-result-actionhint"
                            x-text="regressionValidationSummary?.status === 'ok'
                                ? '结果已通过基础回归校验，可继续查看差异或直接应用。'
                                : '建议先看差异和风险提示，确认无误后再执行应用。'"></div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="openOptimizedDiff()">
                                <i class="ti ti-git-compare me-1"></i>查看差异
                            </button>
                            <button type="button" class="btn btn-success btn-sm w-100" @click="applyOptimized()" :disabled="isAnyAiActionRunning()">
                                <span x-show="!isActionLocked('applyOptimized')"><i class="ti ti-check me-1"></i>应用到当前简历</span>
                                <span x-show="isActionLocked('applyOptimized')" class="spinner-border spinner-border-sm"></span>
                            </button>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="closeAiPanelModal('result')">关闭</button>
            </div>
        </div>
    </div>
</div>
