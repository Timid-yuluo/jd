<div class="editor-toolbar-head editor-toolbar-head--compact">
    <div class="editor-toolbar-meta">
        <span class="editor-toolbar-meta-item" x-text="modules.length + ' 个模块'"></span>
        <span class="editor-toolbar-meta-item">支持拖拽排序</span>
        <span class="editor-toolbar-meta-item">实时预览</span>
    </div>
    <div class="editor-toolbar-actions-inline">
        <span class="editor-save-status" :class="'editor-save-status--' + draftSaveState" x-show="draftSaveState !== 'idle'" x-text="draftStatusText()" x-transition></span>
        <button type="button" class="btn btn-ghost-secondary btn-sm" @click="toggleCollapseAll()" :title="allCollapsed() ? '展开全部' : '折叠全部'">
            <i :class="allCollapsed() ? 'ti ti-chevrons-down' : 'ti ti-chevrons-up'" class="me-1"></i>
            <span x-text="allCollapsed() ? '展开' : '折叠'"></span>
        </button>
        <button type="button" class="btn btn-ghost-secondary btn-sm" @click="showShortcuts = !showShortcuts" title="快捷键帮助">
            <i class="ti ti-keyboard me-1"></i>快捷键
        </button>
    </div>
</div>
