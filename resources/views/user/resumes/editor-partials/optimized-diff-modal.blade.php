<div class="modal fade" id="optimized-diff-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-git-compare me-2"></i>优化差异速览</h5>
                    <label class="form-check form-switch m-0 me-3">
                        <input class="form-check-input" type="checkbox" x-model="diffOnlyChanges" @change="prepareDiff()">
                        <span class="form-check-label small">仅看变化行</span>
                    </label>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary py-2 mb-3">
                        蓝色为新增或变化内容，淡红色为原文中被替换或删除内容。
                    </div>
                    <div class="border rounded p-2 mb-3" style="border-color:#ffe8b3 !important; background:#fffdf5;">
                        <div class="small fw-medium mb-1">关键词高亮</div>
                        <div class="small text-muted mb-1">差异内容中的关键词会以黄色标注，便于快速确认匹配程度。</div>
                        <div class="small text-muted" x-show="diffHighlightedKeywords?.hit?.length">
                            <span class="fw-medium">已命中：</span>
                            <span x-text="diffHighlightedKeywords.hit.slice(0, 10).join('、')"></span>
                        </div>
                        <div class="small text-muted" x-show="diffHighlightedKeywords?.miss?.length">
                            <span class="fw-medium">待补齐：</span>
                            <span x-text="diffHighlightedKeywords.miss.slice(0, 10).join('、')"></span>
                        </div>
                    </div>
                    <template x-if="diffRiskTips.length > 0">
                        <div class="alert alert-warning py-2 mb-3">
                            <div class="fw-medium mb-1">风险提示</div>
                            <template x-for="tip in diffRiskTips" :key="tip">
                                <div class="small">- <span x-text="tip"></span></div>
                            </template>
                        </div>
                    </template>
                    <div class="mb-3">
                        <div class="text-secondary small mb-2">变化分组</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-sm"
                                :class="selectedDiffSection === 'ALL' ? 'btn-primary' : 'btn-outline-secondary'"
                                @click="selectedDiffSection = 'ALL'; prepareDiff()"
                            >
                                全部（<span x-text="diffSections.reduce((sum, sec) => sum + sec.changed_count, 0)"></span>）
                            </button>
                            <template x-for="sec in diffSections" :key="sec.name">
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    :class="selectedDiffSection === sec.name ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="focusSection(sec.name)"
                                >
                                    <span x-text="sec.name"></span>
                                    （<span x-text="sec.changed_count"></span>）
                                </button>
                            </template>
                        </div>
                    </div>
                    <template x-if="diffSections.length > 0">
                        <div class="mb-3">
                            <div class="text-secondary small mb-2">分组变化类型统计</div>
                            <div class="d-flex flex-wrap gap-2">
                                <template x-for="sec in diffSections" :key="sec.name + '-stats'">
                                    <span class="badge bg-light text-secondary border">
                                        <span x-text="sec.name"></span>
                                        <span class="ms-1 text-success">+<span x-text="sec.added_count"></span></span>
                                        <span class="ms-1 text-danger">-<span x-text="sec.removed_count"></span></span>
                                        <span class="ms-1 text-primary">~<span x-text="sec.replaced_count"></span></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="border rounded h-100">
                                <div class="p-2 border-bottom bg-light fw-medium">原文（当前保存版本）</div>
                                <div class="p-2 diff-pane" x-html="diffHtmlOriginal"></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="border rounded h-100">
                                <div class="p-2 border-bottom bg-primary-lt fw-medium">优化后版本</div>
                                <div class="p-2 diff-pane" x-html="diffHtmlOptimized"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="copyOptimizedDelta()">
                        <i class="ti ti-copy me-1"></i>复制优化增量
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
