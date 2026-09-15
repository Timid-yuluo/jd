{{-- 左侧编辑区 --}}
<div class="col-12 col-lg-4 d-flex flex-column editor-left-col">
    <div class="editor-lite-sticky-bar px-3 py-2">
        <div class="editor-lite-sticky-inner">
            <span class="editor-status-pill editor-status-pill--xs" :class="dirty ? 'is-dirty' : ''">
                <i :class="dirty ? 'ti ti-alert-circle' : 'ti ti-check'"></i>
                <span x-text="dirty ? '未保存' : '已保存'"></span>
            </span>
            <span class="editor-lite-doc" title="{{ $resume->title }}">《{{ $resume->title }}》</span>
            <span class="editor-lite-meta" x-text="modules.length + ' 模块'"></span>
            <span class="editor-lite-meta" x-text="wordCount().chars + ' 字'"></span>
        </div>
    </div>
    <div class="p-3 border-bottom editor-sidebar-top editor-section-shell">
        @include('user.resumes.editor-partials.sidebar.toolbar')
    </div>

    @include('user.resumes.editor-partials.sidebar.shortcuts')
    <div class="editor-section-shell editor-section-shell--modules">
        @include('user.resumes.editor-partials.sidebar.module-list')
    </div>
    @include('user.resumes.editor-partials.sidebar.save-panel')
</div>
