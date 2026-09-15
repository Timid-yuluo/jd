<div class="modal fade" id="ai-analysis-modal" tabindex="-1" aria-hidden="true" @click.self="closeAiPanelModal('analysis')">
    <div class="modal-dialog modal-xl modal-dialog-scrollable editor-ai-panel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="editor-ai-modal-head w-100">
                    <div class="editor-ai-modal-top">
                        <div>
                            <h5 class="modal-title"><i class="ti ti-search me-2"></i>关键词分析</h5>
                            <div class="small text-muted">提取 JD 关键词，并查看当前简历与优化结果的命中情况。</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeAiPanelModal('analysis')"></button>
                    </div>
                    <div class="editor-ai-modal-switcher">
                        <button type="button" class="btn btn-primary btn-sm">关键词分析</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('analysis', 'suggestion')">建议评分</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="switchAiPanelModal('analysis', 'result')">优化结果</button>
                    </div>
                    <div class="editor-ai-modal-summary">
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">已选关键词</span>
                            <span class="editor-ai-modal-summary-value" x-text="`${selectedJdKeywords.length} 个`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">当前命中率</span>
                            <span class="editor-ai-modal-summary-value" x-text="`${keywordCoverageCurrent?.rate ?? 0}%`"></span>
                        </div>
                        <div class="editor-ai-modal-summary-item">
                            <span class="editor-ai-modal-summary-label">优化后命中</span>
                            <span class="editor-ai-modal-summary-value" x-text="keywordCoverageOptimized ? `${keywordCoverageOptimized?.rate ?? 0}%` : '待生成'"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <section class="editor-ai-section editor-ai-modal-section">
                    <div class="editor-ai-section-head">
                        <div>
                            <div class="editor-ai-section-kicker">分析</div>
                            <div class="editor-ai-section-title">关键词与命中分析</div>
                        </div>
                        <div class="editor-ai-section-side small text-muted" x-text="`已选 ${selectedJdKeywords.length} / ${jdExtractedKeywords.length}`"></div>
                    </div>
                    <div class="editor-ai-section-grid">
                        <div class="editor-ai-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="small fw-medium">JD 关键词提取</div>
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="refreshJdKeywordsFromDescription(true)" :disabled="isAnyAiActionRunning()">
                                    提取关键词
                                </button>
                            </div>
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="selectAllJdKeywords()" :disabled="!jdExtractedKeywords.length || isAnyAiActionRunning()">全选</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearSelectedJdKeywords()" :disabled="!selectedJdKeywords.length || isAnyAiActionRunning()">清空</button>
                                <span class="small text-muted ms-auto" x-text="`已选 ${selectedJdKeywords.length} / ${jdExtractedKeywords.length}`"></span>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <template x-if="!jdExtractedKeywords.length">
                                    <span class="small text-muted">先填写岗位描述并点击“提取关键词”</span>
                                </template>
                                <template x-for="keyword in jdExtractedKeywords" :key="keyword">
                                    <button type="button" class="btn btn-sm"
                                        :class="selectedJdKeywords.includes(keyword) ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="toggleJdKeyword(keyword)"
                                        x-text="keyword"></button>
                                </template>
                            </div>
                            <div class="small text-warning mt-2" x-show="keywordConflictTips?.length">
                                <template x-for="(tip, idx) in keywordConflictTips" :key="idx">
                                    <div>- <span x-text="tip"></span></div>
                                </template>
                            </div>
                        </div>

                        <div class="editor-ai-card">
                            <div class="small fw-medium mb-2">关键词命中分析</div>
                            <div class="small text-muted d-flex justify-content-between" x-show="keywordCoverageCurrent">
                                <span x-text="`当前命中：${keywordCoverageCurrent?.hit ?? 0}/${keywordCoverageCurrent?.total ?? 0}`"></span>
                                <span x-text="`命中率：${keywordCoverageCurrent?.rate ?? 0}%`"></span>
                            </div>
                            <div class="small text-muted d-flex justify-content-between mb-2" x-show="keywordCoverageOptimized">
                                <span x-text="`优化后命中：${keywordCoverageOptimized?.hit ?? 0}/${keywordCoverageOptimized?.total ?? 0}`"></span>
                                <span x-text="`命中率：${keywordCoverageOptimized?.rate ?? 0}%`"></span>
                            </div>
                            <div class="small text-muted" x-show="(keywordCoverageCurrent?.missedKeywords?.length || 0) > 0">
                                <span class="fw-medium">当前缺失：</span>
                                <span x-text="(keywordCoverageCurrent?.missedKeywords || []).slice(0, 8).join('、')"></span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="closeAiPanelModal('analysis')">关闭</button>
            </div>
        </div>
    </div>
</div>
