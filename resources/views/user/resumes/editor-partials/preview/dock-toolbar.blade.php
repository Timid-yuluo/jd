        <div class="editor-preview-toolbar-stack p-3 pb-0" :class="{ 'is-collapsed': !previewDockOpen }" @click.stop>
            <div class="editor-preview-dock-rail">
                <button type="button" class="editor-preview-dock-btn is-add-entry" data-label="添加模块" :class="{ 'is-active': previewToolTab === 'module' }" @click="if (previewToolTab === 'module' && previewDockOpen) { previewDockOpen = false; } else { previewToolTab = 'module'; previewDockOpen = true; }" title="添加模块" aria-label="添加模块面板" :aria-pressed="(previewToolTab === 'module' && previewDockOpen).toString()" aria-controls="preview-dock-panel">
                    <i class="ti ti-plus"></i>
                </button>
                <button type="button" class="editor-preview-dock-btn" data-label="样式" :class="{ 'is-active': previewToolTab === 'style' }" @click="if (previewToolTab === 'style' && previewDockOpen) { previewDockOpen = false; } else { previewToolTab = 'style'; previewDockOpen = true; }" title="样式" aria-label="样式面板" :aria-pressed="(previewToolTab === 'style' && previewDockOpen).toString()" aria-controls="preview-dock-panel">
                    <i class="ti ti-palette"></i>
                </button>
                <button type="button" class="editor-preview-dock-btn" data-label="导入导出" :class="{ 'is-active': previewToolTab === 'io' }" @click="if (previewToolTab === 'io' && previewDockOpen) { previewDockOpen = false; } else { previewToolTab = 'io'; previewDockOpen = true; }" title="导入导出" aria-label="导入导出面板" :aria-pressed="(previewToolTab === 'io' && previewDockOpen).toString()" aria-controls="preview-dock-panel">
                    <i class="ti ti-exchange"></i>
                </button>
                <button type="button" class="editor-preview-dock-btn" data-label="预览控制" :class="{ 'is-active': previewToolTab === 'preview' }" @click="if (previewToolTab === 'preview' && previewDockOpen) { previewDockOpen = false; } else { previewToolTab = 'preview'; previewDockOpen = true; }" title="预览控制" aria-label="预览控制面板" :aria-pressed="(previewToolTab === 'preview' && previewDockOpen).toString()" aria-controls="preview-dock-panel">
                    <i class="ti ti-adjustments-horizontal"></i>
                </button>
                <button type="button" class="editor-preview-dock-btn" data-label="收起/展开" @click="previewDockOpen = !previewDockOpen" :title="previewDockOpen ? '收起面板' : '展开面板'" aria-label="收起或展开工具面板" :aria-expanded="previewDockOpen.toString()" aria-controls="preview-dock-panel">
                    <i :class="previewDockOpen ? 'ti ti-chevron-right' : 'ti ti-chevron-left'"></i>
                </button>
            </div>

            @include('user.resumes.editor-partials.preview.dock-panel')

