<div class="modal fade" id="import-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="ti ti-file-search me-2"></i>导入预览</h5>
                    <div class="small text-muted mt-1">先确认识别结果，再决定是否覆盖当前模块。</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeImportPreviewModal(true)"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 mb-3">
                    确认导入后，会用识别出的模块覆盖当前编辑内容，并重置旧的 ATS 与优化结果。
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary-lt text-primary" x-text="importPreviewModules.length + ' 个模块'"></span>
                    <span class="badge bg-info-lt text-info" x-show="importPreviewTargetJob" x-text="'目标岗位：' + importPreviewTargetJob"></span>
                    <span class="badge bg-secondary-lt text-secondary" x-show="importPreviewFileName" x-text="importPreviewFileName"></span>
                    <span class="badge bg-success-lt text-success" x-show="importPreviewMeta.ocr_used">OCR 兜底已启用</span>
                    <span class="badge bg-warning-lt text-warning" x-show="!importPreviewMeta.ocr_used && importPreviewMeta.possible_scanned_pdf && !importPreviewMeta.ocr_available">扫描件且未启用 OCR</span>
                </div>
                <div class="alert alert-info py-2 mb-3" x-show="importPreviewMetaHintText()" x-text="importPreviewMetaHintText()"></div>
                <template x-if="importPreviewStatEntries().length > 0">
                    <div class="mb-3">
                        <div class="text-secondary small mb-2">识别到的模块分布</div>
                        <div class="d-flex flex-wrap gap-2">
                            <template x-for="stat in importPreviewStatEntries()" :key="stat.label">
                                <span class="badge bg-light text-secondary border">
                                    <span x-text="stat.label"></span>
                                    <span class="ms-1" x-text="stat.count"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </template>
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="text-secondary small">当前模块 vs 即将导入模块</div>
                        <span class="badge bg-primary-lt text-primary" x-text="'变化项 ' + importPreviewChangedCount()"></span>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <template x-for="row in importPreviewComparisonRows()" :key="'compare-' + row.index">
                            <div class="editor-import-compare-row">
                                <div class="row g-2 align-items-stretch">
                                    <div class="col-lg-5">
                                        <div class="small text-muted mb-1">当前模块</div>
                                        <template x-if="row.current">
                                            <div class="editor-import-preview-card h-100">
                                                <div class="fw-medium mb-1" x-text="moduleTypeLabel(row.current.type)"></div>
                                                <div class="small text-muted mb-1" x-text="modulePreviewHeading(row.current)"></div>
                                                <div class="small" x-text="moduleSummary(row.current)"></div>
                                                <div class="editor-import-diff-fields" x-show="row.fieldDiffs.length > 0">
                                                    <template x-for="diff in row.fieldDiffs" :key="'current-' + row.index + '-' + diff.key">
                                                        <span class="editor-import-diff-field">
                                                            <span class="editor-import-diff-field-label" x-text="diff.label"></span>
                                                            <span class="editor-import-diff-field-value" x-text="diff.currentText || '当前为空'"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!row.current">
                                            <div class="editor-import-preview-empty h-100">当前没有对应模块</div>
                                        </template>
                                    </div>
                                    <div class="col-lg-2 d-flex align-items-center justify-content-center">
                                        <span class="badge editor-import-compare-label" :class="importPreviewDiffBadgeClass(row.status)" x-text="importPreviewDiffLabel(row.status)"></span>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="small text-muted mb-1">即将导入</div>
                                        <template x-if="row.incoming">
                                            <div class="editor-import-preview-card h-100">
                                                <div class="fw-medium mb-1" x-text="moduleTypeLabel(row.incoming.type)"></div>
                                                <div class="small text-muted mb-1" x-text="modulePreviewHeading(row.incoming)"></div>
                                                <div class="small" x-text="moduleSummary(row.incoming)"></div>
                                                <div class="editor-import-diff-fields" x-show="row.fieldDiffs.length > 0">
                                                    <template x-for="diff in row.fieldDiffs" :key="'incoming-' + row.index + '-' + diff.key">
                                                        <span class="editor-import-diff-field">
                                                            <span class="editor-import-diff-field-label" x-text="diff.label"></span>
                                                            <span class="editor-import-diff-field-value" x-text="diff.incomingText || '导入后为空'"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!row.incoming">
                                            <div class="editor-import-preview-empty h-100">导入后将不存在这个位置的模块</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="text-secondary small mb-2">模块预览</div>
                        <div class="d-flex flex-column gap-2">
                            <template x-for="(mod, index) in importPreviewModules" :key="(mod.type || 'mod') + '-' + index">
                                <div class="editor-import-preview-card">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                        <div class="fw-medium" x-text="moduleTypeLabel(mod.type)"></div>
                                        <span class="badge" :class="moduleCompletionBadgeClass(mod)" x-text="moduleCompletionLabel(mod)"></span>
                                    </div>
                                    <div class="small text-muted mb-1" x-text="modulePreviewHeading(mod)"></div>
                                    <div class="small" x-text="moduleSummary(mod)"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-secondary small mb-2">提取文本预览</div>
                        <div class="editor-import-preview-raw" x-text="importPreviewRawText || '暂无提取文本'"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="closeImportPreviewModal(true)">取消</button>
                <button type="button" class="btn btn-primary" @click="confirmImportDocument()" :disabled="importing">
                    <span x-show="!importing"><i class="ti ti-check me-1"></i>确认导入并覆盖</span>
                    <span x-show="importing" class="spinner-border spinner-border-sm"></span>
                </button>
            </div>
        </div>
    </div>
</div>
