<div class="modal fade" id="ai-workbench-modal" tabindex="-1" aria-hidden="true" @click.self="closeAiWorkbenchModal()">
    <div class="modal-dialog modal-xl modal-dialog-scrollable editor-ai-panel-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="ti ti-sparkles me-2"></i>AI 岗位定向优化面板</h5>
                    <div class="small text-muted">配置真实优先策略后发起任务，完成后自动进入前后对比页。</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeAiWorkbenchModal()"></button>
            </div>
            <div class="modal-body">
                @include('user.resumes.editor-partials.sidebar.ai-tools')
            </div>
        </div>
    </div>
</div>
