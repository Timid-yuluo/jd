<div class="editor-module-overview mb-2">
    <div class="editor-overview-chip is-high">
        <span class="editor-overview-chip-label">较完整</span>
        <span class="editor-overview-chip-value" x-text="moduleCompletionCount('high')"></span>
    </div>
    <div class="editor-overview-chip is-medium">
        <span class="editor-overview-chip-label">待完善</span>
        <span class="editor-overview-chip-value" x-text="moduleCompletionCount('medium')"></span>
    </div>
    <div class="editor-overview-chip is-low">
        <span class="editor-overview-chip-label">待填写</span>
        <span class="editor-overview-chip-value" x-text="moduleCompletionCount('low')"></span>
    </div>
</div>
<div class="small text-muted mb-3" x-text="moduleCompletionOverviewText()"></div>
