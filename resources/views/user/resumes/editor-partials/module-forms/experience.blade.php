<template x-if="mod.type === 'experience'">
    <div class="editor-form-stack">
        <div class="editor-field-surface">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="small text-muted mb-0">编辑模式</label>
                <select class="form-select form-select-sm" style="max-width: 200px;"
                    :value="moduleDetailMode(mod)"
                    @change="setModuleDetailMode(index, $event.target.value)">
                    <option value="structured">结构化模式</option>
                    <option value="raw">自由文本模式</option>
                </select>
                <span class="small text-muted">可粘贴岗位经历后自动拆分。</span>
            </div>
        </div>

        <template x-if="moduleDetailMode(mod) === 'structured'">
            <div class="editor-form-stack">
                <div class="editor-field-surface">
                    <div class="editor-surface-head">
                        <div>
                            <div class="editor-surface-title"><i class="ti ti-layout-list"></i>基础信息</div>
                            <div class="editor-surface-note">先写标题和基本元信息，再补充职责与成果。</div>
                        </div>
                    </div>
                    <label class="form-label form-label-sm" x-text="moduleTitleLabel(mod)"></label>
                    <input type="text" class="form-control form-control-sm mb-2" x-model="mod.data.title" :placeholder="moduleTitlePlaceholder(mod)" data-field="title">
                </div>
                <div class="editor-field-surface">
                    <div class="editor-surface-head">
                        <div>
                            <div class="editor-surface-title"><i class="ti ti-briefcase-2"></i>元信息</div>
                            <div class="editor-surface-note">补充公司、时间和地点，便于招聘方快速建立经历上下文。</div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm" x-text="moduleSubtitleLabel(mod)"></label>
                            <input type="text" class="form-control form-control-sm" x-model="mod.data.subtitle" :placeholder="moduleSubtitlePlaceholder(mod)" data-field="subtitle">
                        </div>
                        <div class="col-3">
                            <label class="form-label form-label-sm" x-text="moduleDateLabel(mod)"></label>
                            <input type="text" class="form-control form-control-sm" x-model="mod.data.date" :placeholder="moduleDatePlaceholder(mod)" data-field="date">
                        </div>
                        <div class="col-3">
                            <label class="form-label form-label-sm" x-text="moduleLocationLabel(mod)"></label>
                            <input type="text" class="form-control form-control-sm" x-model="mod.data.location" :placeholder="moduleLocationPlaceholder(mod)" data-field="location">
                        </div>
                    </div>
                </div>
                <div class="editor-field-surface">
                    <div class="editor-surface-head">
                        <div>
                            <div class="editor-surface-title"><i class="ti ti-align-left"></i>概述说明</div>
                            <div class="editor-surface-note">用 1 到 2 句话概括岗位范围、业务线和你的职责边界。</div>
                        </div>
                    </div>
                    <label class="form-label form-label-sm" x-text="moduleContentLabel(mod)"></label>
                    <textarea class="form-control form-control-sm" x-model="mod.data.content" rows="3" :placeholder="moduleContentPlaceholder(mod)" data-field="content"></textarea>
                </div>
                <div class="editor-field-surface">
                    <div class="editor-surface-head">
                        <div>
                            <div class="editor-surface-title"><i class="ti ti-list-details"></i>条目亮点</div>
                            <div class="editor-surface-note">建议每条突出动作、结果和量化数据。</div>
                        </div>
                    </div>
                    <label class="form-label form-label-sm" x-text="moduleItemsLabel(mod)"></label>
                    <div x-show="!hasFilledItems(mod.data.items)" class="editor-module-empty-tip" x-text="moduleEmptyItemsText(mod)"></div>
                    <div class="editor-item-list">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <div class="input-group input-group-sm editor-item-row">
                                <input type="text" class="form-control" x-model="mod.data.items[i]" :placeholder="moduleItemPlaceholder(mod)" data-field="items">
                                <button type="button" class="btn btn-outline-danger" @click="mod.data.items = removeItem(mod.data.items, i)">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2 editor-item-add-btn" @click="mod.data.items = [...mod.data.items, '']" data-add-item="true">
                        <i class="ti ti-plus me-1"></i><span x-text="moduleAddItemText(mod)"></span>
                    </button>
                </div>
            </div>
        </template>

        <template x-if="moduleDetailMode(mod) === 'raw'">
            <div class="editor-field-surface">
                <div class="editor-surface-head">
                    <div>
                        <div class="editor-surface-title"><i class="ti ti-file-description"></i>自由文本录入</div>
                        <div class="editor-surface-note">首行可写“公司/职位 | 时间 | 地点”，其余行写描述和条目。</div>
                    </div>
                </div>
                <textarea class="form-control form-control-sm"
                    rows="8"
                    x-model="mod.data.content_raw"
                    placeholder="XX 公司 前端实习生 | 2024.06 - 2024.09 | 北京&#10;负责招聘系统重构，覆盖核心流程。&#10;- 接口响应时间下降 35%&#10;- 建立埋点体系支撑漏斗分析"></textarea>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <div class="small text-muted">可直接粘贴原始经历文本，再一键拆分。</div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="previewAutoFixModuleRawText(index)">
                            <i class="ti ti-adjustments me-1"></i>预览修正
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="applyModuleRawTextToStructured(index)">
                            <i class="ti ti-wand me-1"></i>一键拆分
                        </button>
                    </div>
                </div>
                <div class="border rounded p-2 mt-2 bg-light-subtle" x-show="moduleRawPreviewHasData(mod)">
                    <div class="small fw-semibold mb-1">实时预解析预览</div>
                    <div class="alert alert-warning py-2 mb-2" x-show="moduleRawPreviewWarnings(mod).length > 0">
                        <div class="small fw-semibold mb-1">解析提示</div>
                        <template x-for="(warn, warnIdx) in moduleRawPreviewWarnings(mod)" :key="`warn-exp-${warnIdx}`">
                            <div class="small" x-text="`• ${warn}`"></div>
                        </template>
                    </div>
                    <div class="small text-muted mb-1" x-show="moduleRawPreview(mod).subtitle">公司/职位：<span class="text-dark" x-text="moduleRawPreview(mod).subtitle"></span></div>
                    <div class="small text-muted mb-1" x-show="moduleRawPreview(mod).date">时间：<span class="text-dark" x-text="moduleRawPreview(mod).date"></span></div>
                    <div class="small text-muted mb-1" x-show="moduleRawPreview(mod).location">地点：<span class="text-dark" x-text="moduleRawPreview(mod).location"></span></div>
                    <div class="small text-muted mb-1" x-show="moduleRawPreview(mod).content">概述：<span class="text-dark" x-text="moduleRawPreview(mod).content"></span></div>
                    <div class="small text-muted" x-show="moduleRawPreview(mod).items.length > 0">条目：<span class="text-dark" x-text="moduleRawPreview(mod).items.length + ' 条'"></span></div>
                </div>
            </div>
        </template>
    </div>
</template>
