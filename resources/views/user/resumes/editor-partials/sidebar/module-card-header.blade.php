<div class="card-header py-1 d-flex align-items-center justify-content-between" @click="activeIndex = index">
    <div class="d-flex align-items-start gap-2 w-100">
        <span class="editor-module-handle">
            <i class="ti ti-grip-vertical"></i>
        </span>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <span class="fw-medium editor-module-title" x-text="moduleTypeLabel(mod.type)"></span>
                <span class="badge bg-primary-lt text-primary editor-module-active-badge" x-show="activeIndex === index">编辑中</span>
                <span class="badge bg-secondary-lt text-secondary editor-module-layout-badge" x-show="moduleLayoutBadgeText(mod)" x-text="moduleLayoutBadgeText(mod)"></span>
            </div>
            <span class="editor-module-summary" x-show="moduleSummary(mod)" x-text="moduleSummary(mod)"></span>
        </div>
    </div>
    <div class="d-flex gap-1 editor-module-actions" @click.stop>
        <button type="button" class="btn btn-ghost-secondary btn-sm p-1 d-md-none" @click.stop="moveUp(index)" :disabled="index === 0" title="上移">
            <i class="ti ti-chevron-up"></i>
        </button>
        <button type="button" class="btn btn-ghost-secondary btn-sm p-1 d-md-none" @click.stop="moveDown(index)" :disabled="index === modules.length - 1" title="下移">
            <i class="ti ti-chevron-down"></i>
        </button>
        <button type="button" class="btn btn-ghost-secondary btn-sm p-1" @click.stop="toggleCollapse(mod._key)" :title="isCollapsed(mod._key) ? '展开' : '折叠'">
            <i :class="isCollapsed(mod._key) ? 'ti ti-chevron-down' : 'ti ti-chevron-up'"></i>
        </button>
    </div>
</div>
