<div class="editor-module-tip mb-2" x-data="{ expanded: false }">
    <div class="d-flex align-items-start justify-content-between gap-2">
        <div class="min-w-0 flex-grow-1">
            <div class="editor-module-tip-title" x-text="moduleHintTitle(mod)"></div>
            <div class="small text-muted editor-module-tip-summary" x-show="!expanded" x-text="(moduleHintText(mod) || '').slice(0, 34) + ((moduleHintText(mod) || '').length > 34 ? '…' : '')"></div>
            <div class="small text-muted" x-show="expanded" x-text="moduleHintText(mod)"></div>
        </div>
        <div class="editor-module-tip-actions">
            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                x-show="moduleHasMissingFields(mod)"
                @click.stop="focusFirstIncompleteField(index)"
            >
                <i class="ti ti-target-arrow me-1"></i>去补齐
            </button>
            <span class="badge" :class="moduleCompletionBadgeClass(mod)" x-text="moduleCompletionPercent(mod) + '%'"></span>
        </div>
    </div>
    <div class="editor-module-tip-expand mt-1">
        <button type="button" class="btn btn-link btn-sm p-0" @click.stop="expanded = !expanded">
            <span x-text="expanded ? '收起详情' : '展开详情'"></span>
        </button>
    </div>
    <div class="editor-module-tip-extra mt-1" x-show="expanded">
        <div class="border rounded p-2 mb-2" x-show="['objective','education','experience','project','skill','certificate','summary'].includes(mod.type)">
            <div class="small fw-semibold mb-2">图一区域细化设置</div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                            :checked="moduleDisplayConfig(mod).showBadge"
                            @change.stop="setModuleDisplayConfig(index, 'showBadge', $event.target.checked)">
                        <span class="form-check-label small">显示模块标签</span>
                    </label>
                </div>
                <div class="col-6">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                            :checked="moduleDisplayConfig(mod).showDivider"
                            @change.stop="setModuleDisplayConfig(index, 'showDivider', $event.target.checked)">
                        <span class="form-check-label small">显示分隔线</span>
                    </label>
                </div>
                <div class="col-12" x-show="moduleDisplayConfig(mod).showBadge">
                    <input type="text" class="form-control form-control-sm"
                        :value="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"
                        @input.stop="setModuleDisplayConfig(index, 'badgeText', $event.target.value)"
                        placeholder="标签文案（如：核心 / 重点）">
                </div>
                <div class="col-4" x-show="moduleSupportsMetaField(mod, 'subtitle')">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                            :checked="moduleDisplayConfig(mod).showSubtitle"
                            @change.stop="setModuleDisplayConfig(index, 'showSubtitle', $event.target.checked)">
                        <span class="form-check-label small">副标题</span>
                    </label>
                </div>
                <div class="col-4" x-show="moduleSupportsMetaField(mod, 'date')">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                            :checked="moduleDisplayConfig(mod).showDate"
                            @change.stop="setModuleDisplayConfig(index, 'showDate', $event.target.checked)">
                        <span class="form-check-label small">时间</span>
                    </label>
                </div>
                <div class="col-4" x-show="moduleSupportsMetaField(mod, 'location')">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                            :checked="moduleDisplayConfig(mod).showLocation"
                            @change.stop="setModuleDisplayConfig(index, 'showLocation', $event.target.checked)">
                        <span class="form-check-label small">地点</span>
                    </label>
                </div>
                <div class="col-12" x-show="moduleSupportsItems(mod)">
                    <label class="small text-muted mb-1">条目标记</label>
                    <select class="form-select form-select-sm"
                        :value="moduleDisplayConfig(mod).itemMarker"
                        @change.stop="setModuleDisplayConfig(index, 'itemMarker', $event.target.value)">
                        <option value="dot">圆点</option>
                        <option value="dash">横线</option>
                        <option value="none">无标记</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            <label class="small text-muted mb-0">优化策略</label>
            <select class="form-select form-select-sm" style="max-width: 160px;"
                :value="moduleStrategies?.[mod._key] || 'balanced'"
                @change.stop="setModuleStrategy(mod, $event.target.value)">
                <template x-for="opt in moduleStrategyOptions()" :key="`strategy-${mod._key}-${opt.key}`">
                    <option :value="opt.key" x-text="opt.label"></option>
                </template>
            </select>
        </div>
        <div class="small text-muted mb-2" x-show="moduleExplainabilityTips(mod).length > 0">
            <template x-for="(tip, tipIdx) in moduleExplainabilityTips(mod)" :key="`tip-${mod._key}-${tipIdx}`">
                <div class="mb-1 d-flex align-items-start gap-1">
                    <i class="ti ti-point-filled text-primary" style="font-size: 10px; margin-top: 4px;"></i>
                    <span x-text="tip"></span>
                </div>
            </template>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2" x-show="moduleKeywordCoverage(mod).total > 0">
            <span class="badge bg-primary-lt text-primary" x-text="`ATS 命中 ${moduleKeywordCoverage(mod).hit}/${moduleKeywordCoverage(mod).total}`"></span>
            <span class="badge bg-warning-lt text-warning" x-show="moduleKeywordCoverage(mod).miss > 0" x-text="`待补 ${moduleKeywordCoverage(mod).miss}`"></span>
        </div>
        <div class="d-flex align-items-center gap-1 flex-wrap mb-2" x-show="moduleKeywordCoverage(mod).missedKeywords.length > 0">
            <template x-for="(kw, kwIdx) in moduleKeywordCoverage(mod).missedKeywords.slice(0, 3)" :key="`kw-${mod._key}-${kwIdx}`">
                <button type="button" class="btn btn-outline-primary btn-sm" @click.stop="injectKeywordToModule(index, kw)">
                    <i class="ti ti-plus me-1"></i><span x-text="kw"></span>
                </button>
            </template>
        </div>
        <div class="d-flex align-items-center gap-1 flex-wrap mb-2" x-show="moduleWeakWordHits(mod).length > 0">
            <span class="badge bg-warning-lt text-warning" x-text="`弱词 ${moduleWeakWordHits(mod).length}`"></span>
            <template x-for="(hit, hitIdx) in moduleWeakWordHits(mod).slice(0, 3)" :key="`weak-${mod._key}-${hitIdx}`">
                <span class="badge bg-light text-muted" x-text="`${hit.word}→${hit.suggestion}`"></span>
            </template>
            <button type="button" class="btn btn-outline-warning btn-sm" @click.stop="rewriteModuleWeakWords(index)">
                <i class="ti ti-magic-wand me-1"></i>优化弱词
            </button>
        </div>
        <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click.stop="saveModuleSnapshot(index)">
                <i class="ti ti-bookmark-plus me-1"></i>保存快照
            </button>
            <template x-if="moduleSnapshotList(mod).length > 0">
                <select class="form-select form-select-sm" style="max-width: 200px;"
                    @change.stop="restoreModuleSnapshot(index, $event.target.value)">
                    <option value="">回滚到快照...</option>
                    <template x-for="snap in moduleSnapshotList(mod)" :key="`snap-${mod._key}-${snap.ts}`">
                        <option :value="snap.ts" x-text="`快照 ${snap.label}`"></option>
                    </template>
                </select>
            </template>
        </div>
        <div class="editor-import-preview-card mb-2" x-show="moduleSnapshotList(mod).length > 0">
            <div class="small fw-semibold mb-2">模块历史对比</div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                <select class="form-select form-select-sm" style="max-width: 180px;"
                    :value="moduleSnapshotCompareSelectionOf(mod).left"
                    @change.stop="setModuleSnapshotCompareSelection(mod, 'left', $event.target.value)">
                    <option value="">选择左侧版本</option>
                    <option :value="moduleSnapshotCurrentOptionValue()">当前内容</option>
                    <template x-for="snap in moduleSnapshotList(mod)" :key="`compare-left-${mod._key}-${snap.ts}`">
                        <option :value="snap.ts" x-text="`快照 ${snap.label}`"></option>
                    </template>
                </select>
                <i class="ti ti-arrow-right text-muted"></i>
                <select class="form-select form-select-sm" style="max-width: 180px;"
                    :value="moduleSnapshotCompareSelectionOf(mod).right"
                    @change.stop="setModuleSnapshotCompareSelection(mod, 'right', $event.target.value)">
                    <option value="">选择右侧版本</option>
                    <option :value="moduleSnapshotCurrentOptionValue()">当前内容</option>
                    <template x-for="snap in moduleSnapshotList(mod)" :key="`compare-right-${mod._key}-${snap.ts}`">
                        <option :value="snap.ts" x-text="`快照 ${snap.label}`"></option>
                    </template>
                </select>
            </div>
            <div class="d-flex align-items-center gap-1 flex-wrap mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" @click.stop="swapModuleSnapshotCompareSelection(mod)">
                    <i class="ti ti-arrows-exchange me-1"></i>互换左右
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" @click.stop="quickCompareLatestWithCurrent(mod)">
                    <i class="ti ti-bolt me-1"></i>最新 vs 当前
                </button>
            </div>
            <div class="small text-muted mb-2" x-show="moduleSnapshotCompareVersionText(mod)" x-text="moduleSnapshotCompareVersionText(mod)"></div>
            <div class="small text-muted mb-2" x-text="moduleSnapshotDiffSummary(mod)"></div>
            <label class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    :checked="moduleSnapshotCompareOptionsOf(mod).importantOnly"
                    @change.stop="setModuleSnapshotCompareOption(mod, 'importantOnly', $event.target.checked)">
                <span class="form-check-label small">只看关键差异</span>
            </label>
            <label class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    :checked="moduleSnapshotCompareOptionsOf(mod).focusOnly"
                    @change.stop="setModuleSnapshotCompareOption(mod, 'focusOnly', $event.target.checked)">
                <span class="form-check-label small">仅显示差异片段</span>
            </label>
            <div class="editor-import-preview-raw" style="max-height: 180px;" x-show="moduleSnapshotVisibleDiffRows(mod).length > 0">
                <template x-for="(row, rowIdx) in moduleSnapshotVisibleDiffRows(mod)" :key="`diff-row-${mod._key}-${rowIdx}`">
                    <div class="mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <div class="fw-semibold small" x-text="row.label"></div>
                            <span class="badge" :class="moduleSnapshotDiffTypeBadgeClass(row.type)" x-text="moduleSnapshotDiffTypeLabel(row.type)"></span>
                        </div>
                        <div class="small text-muted mb-1">变更前：</div>
                        <div class="small mb-1 editor-snapshot-diff-block" style="white-space: pre-wrap;" x-html="moduleSnapshotDiffBeforeHtml(row)"></div>
                        <div class="small text-muted mb-1">变更后：</div>
                        <div class="small editor-snapshot-diff-block" style="white-space: pre-wrap;" x-html="moduleSnapshotDiffAfterHtml(row)"></div>
                    </div>
                </template>
            </div>
            <div class="small text-muted" x-show="moduleSnapshotDiffRows(mod).length > 0 && moduleSnapshotVisibleDiffRows(mod).length === 0">
                当前对比仅包含轻微改动，关闭“只看关键差异”可查看完整差异。
            </div>
        </div>
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            x-show="shouldSuggestExample(mod)"
            @click.stop="applyModuleExample(index)"
        >
            <i class="ti ti-wand me-1"></i>插入岗位示例
        </button>
    </div>
</div>
