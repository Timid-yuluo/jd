{{-- 右侧预览区 --}}
    <div class="col-12 col-lg-8 d-flex flex-column editor-preview-area" id="editor-preview-area" @keydown.escape.window="closeModuleDetail()">
        <div class="editor-preview-surface"
            :style="previewDockOpen ? '--editor-preview-safe-space: clamp(92px, 30vw, 380px);' : '--editor-preview-safe-space: 92px;'">
            @include('user.resumes.editor-partials.preview.a4-stage')
            @include('user.resumes.editor-partials.preview.dock-toolbar')
        </div>

        @include('user.resumes.editor-partials.preview.module-detail-overlay')
    </div>
