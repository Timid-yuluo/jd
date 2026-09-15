<div class="p-2 border-top bg-white editor-savebar" :class="dirty ? 'is-dirty' : ''">
    <form action="{{ route('user.resumes.save-modules', $resume) }}" method="POST" @submit.prevent="submitForm">
        @csrf
        <input type="hidden" name="modules" :value="JSON.stringify(modulesForSubmit())">
        <input type="hidden" name="template" :value="template">
        <input type="hidden" name="theme" :value="theme">
        <input type="hidden" name="font_settings" :value="JSON.stringify({ fontFamily, fontSize, lineHeight, headingFontSize, headingColor, accentColor, bodyFontColor, sectionSpacing, layoutProfile, titleStyleVariant, leftColumnRatio })">
        <input type="hidden" name="after_save_action" value="save">
        <div class="editor-save-panel p-2">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                <div>
                    <div class="editor-section-label mb-1">
                        <i class="ti ti-device-floppy"></i>保存中心
                    </div>
                    <div class="small text-muted">保存模块和排版配置，避免内容丢失。</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge" :class="dirty ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success'">
                        <i :class="dirty ? 'ti ti-alert-circle me-1' : 'ti ti-check me-1'"></i>
                        <span x-text="dirty ? '存在未保存修改' : '内容已保存'"></span>
                    </span>
                    <span class="small text-muted">
                        <i class="ti ti-keyboard me-1"></i>Ctrl+S 快速保存
                    </span>
                </div>
            </div>

            <div class="editor-save-checklist mb-2" x-show="saveChecklistItems().length > 0">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <div class="small fw-medium text-warning">
                        <i class="ti ti-shield-exclamation me-1"></i>保存前检查
                    </div>
                    <span class="badge bg-warning-lt text-warning" x-text="saveChecklistSummaryText()"></span>
                </div>
                <div class="small text-muted mb-1" x-text="saveChecklistGuideText()"></div>
                <template x-for="(issue, issueIndex) in saveChecklistItems()" :key="issue.type + '-' + issueIndex">
                    <div class="editor-save-checklist-item">
                        <div class="small">
                            <span class="fw-medium" x-text="issue.moduleLabel"></span>
                            <span class="text-muted"> · </span>
                            <span x-text="issue.message"></span>
                        </div>
                        <button type="button" class="btn btn-outline-warning btn-sm" @click="focusChecklistItem(issue)">
                            <i class="ti ti-arrow-right me-1"></i>去处理
                        </button>
                    </div>
                </template>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn" :class="dirty ? 'btn-warning' : 'btn-primary'">
                    <i class="ti ti-device-floppy me-2"></i><span x-text="dirty ? '保存简历 *' : '保存简历'"></span>
                </button>
                <button type="submit" class="btn btn-outline-secondary btn-sm"
                    @click.prevent="submitForm({ target: $el.closest('form'), preventDefault() {}, redirect: 'show' })">
                    <i class="ti ti-check me-1"></i>保存并返回
                </button>
            </div>
            <div class="text-center mt-1">
                <span class="text-muted small" x-show="!dirty" style="font-size: 11px;">
                    <i class="ti ti-check text-success"></i> 已保存
                </span>
                <span class="text-muted small" x-show="dirty" style="font-size: 11px;">
                    <i class="ti ti-clock text-warning"></i> 有未保存的更改
                </span>
            </div>
        </div>
    </form>
</div>
