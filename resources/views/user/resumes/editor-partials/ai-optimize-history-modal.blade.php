<div class="modal fade" id="ai-optimize-history-modal" tabindex="-1" aria-hidden="true" @click.self="closeOptimizeHistoryModal()">
    <div class="modal-dialog modal-lg modal-dialog-scrollable editor-ai-panel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="ti ti-history me-2"></i>AI 优化任务记录</h5>
                    <div class="small text-muted">每次 AI 优化请求都会保留任务记录。成功任务可加载或查看对比页；失败任务可在此直接重试。</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="fetchOptimizeSessionHistory()" :disabled="optimizeSessionHistoryLoading">
                        <i class="ti ti-refresh me-1"></i>刷新
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeOptimizeHistoryModal()"></button>
                </div>
            </div>
            <div class="modal-body">
                <div x-show="optimizeSessionHistoryLoading" class="text-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm me-2"></span>正在加载历史版本...
                </div>

                <div x-show="!optimizeSessionHistoryLoading && optimizeSessionHistory.length === 0" class="text-center py-4 text-muted">
                    暂无 AI 优化任务记录
                </div>

                <div class="d-flex flex-column gap-2" x-show="!optimizeSessionHistoryLoading && optimizeSessionHistory.length > 0">
                    <template x-for="item in optimizeSessionHistory" :key="item.session_id">
                        <div class="border rounded p-2">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div class="small fw-semibold">
                                    <span x-text="item.target_job || '未填写目标岗位'"></span>
                                    <span class="text-muted ms-2" x-text="`模式：${item.optimize_mode || 'balanced'}`"></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-lt text-secondary" x-show="item.used_driver" x-text="`模型 ${normalizeAiDriverLabel(item.used_driver)}`"></span>
                                    <span class="badge bg-warning-lt text-warning" x-show="item.fallback_from_driver && item.used_driver" x-text="`已降级 ${normalizeAiDriverLabel(item.fallback_from_driver)} → ${normalizeAiDriverLabel(item.used_driver)}`"></span>
                                    <span class="badge" :class="optimizeSessionStatusMeta(item.status).badge" x-text="item.status_label || optimizeSessionStatusMeta(item.status).label"></span>
                                    <div class="small text-muted" x-text="item.finished_at || item.created_at"></div>
                                </div>
                            </div>
                            <div class="small text-muted mt-1" x-show="item.status === 'queued' || item.status === 'running'">
                                当前进度：<span x-text="`${item.progress || 0}%`"></span>。
                                任务仍在后台处理中，如长时间未变化可稍后刷新列表。
                            </div>
                            <div class="small text-danger mt-1" x-show="item.status === 'failed' && item.error_message" x-text="item.error_message"></div>
                            <div class="small text-muted mt-1" x-show="item.status === 'succeeded' || item.status === 'applied'" x-text="item.after_preview || '（该版本暂无预览内容）'"></div>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" x-show="item.compare_ready && item.after_raw" @click="applyOptimizeHistoryVersion(item)">
                                    <i class="ti ti-file-import me-1"></i>加载此版本
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" x-show="item.compare_ready" @click="goToOptimizeHistoryCompare(item)">
                                    <i class="ti ti-git-compare me-1"></i>打开对比页
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" x-show="item.can_retry" @click="retryOptimizeSession(item)">
                                    <i class="ti ti-refresh-dot me-1"></i>重新发起
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
