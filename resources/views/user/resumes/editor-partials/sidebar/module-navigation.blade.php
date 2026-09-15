<div class="editor-field-surface mb-3" x-show="modules.length > 0">
    <div class="editor-surface-head">
        <div>
            <div class="editor-surface-title"><i class="ti ti-list-search"></i>模块导航</div>
            <div class="editor-surface-note">支持快速跳到编辑模块，或定位到右侧预览对应位置。</div>
        </div>
    </div>
    <div class="editor-nav-strip">
        <template x-for="(mod, navIndex) in modules" :key="'nav-' + mod._key">
            <div class="editor-nav-chip" :class="{ 'is-active': activeIndex === navIndex }">
                <button type="button" class="btn btn-link p-0 text-decoration-none editor-nav-chip-label"
                    @click="scrollToModule(navIndex, 'editor')"
                    :class="activeIndex === navIndex ? 'text-primary fw-semibold' : 'text-secondary'">
                    <span x-text="moduleTypeLabel(mod.type)"></span>
                </button>
                <button type="button" class="btn btn-ghost-secondary btn-sm p-0" @click="scrollToModule(navIndex, 'preview')" title="定位到预览">
                    <i class="ti ti-eye"></i>
                </button>
            </div>
        </template>
    </div>
</div>
