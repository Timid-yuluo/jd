        <template x-for="(mod, index) in modules" :key="'detail-' + mod._key">
            <div class="editor-module-detail-overlay" x-show="detailOpen && detailIndex === index" x-cloak x-transition>
                <div class="editor-module-detail-mask" @click="closeModuleDetail()"></div>
                <div class="editor-module-detail-panel" @click.stop>
                    <div class="editor-module-detail-head">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-settings text-primary"></i>
                            <span class="fw-semibold" x-text="moduleTypeLabel(mod.type) + ' · 详细设置'"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost-secondary" @click="closeModuleDetail()">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="editor-module-detail-body">
                        @include('user.resumes.editor-partials.sidebar.module-card-tip')
                        @include('user.resumes.editor-partials.sidebar.module-card-forms')

                        {{-- 模块排版 - 直接显示在详细设置面板中 --}}
                        <div class="border rounded p-3 mt-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                <div class="fw-semibold"><i class="ti ti-typography me-1"></i>模块排版</div>
                                <button type="button" class="btn btn-sm btn-ghost-secondary"
                                    x-show="mod.data && mod.data._display"
                                    @click.stop="clearModuleDisplayConfig(index)">
                                    <i class="ti ti-restore me-1"></i>恢复模块默认
                                </button>
                            </div>

                            <div class="small text-secondary mb-2 fw-medium"><i class="ti ti-layout-grid me-1"></i>结构展示</div>
                            <div class="row g-2 mb-3">
                                <div class="col-6" x-show="moduleSupportsMetaField(mod, 'subtitle')">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            :checked="moduleDisplayConfig(mod).showSubtitle"
                                            @change.stop="setModuleDisplayConfig(index, 'showSubtitle', $event.target.checked)">
                                        <span class="form-check-label small">显示副标题</span>
                                    </label>
                                </div>
                                <div class="col-6" x-show="moduleSupportsMetaField(mod, 'date')">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            :checked="moduleDisplayConfig(mod).showDate"
                                            @change.stop="setModuleDisplayConfig(index, 'showDate', $event.target.checked)">
                                        <span class="form-check-label small">显示日期</span>
                                    </label>
                                </div>
                                <div class="col-6" x-show="moduleSupportsMetaField(mod, 'location')">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            :checked="moduleDisplayConfig(mod).showLocation"
                                            @change.stop="setModuleDisplayConfig(index, 'showLocation', $event.target.checked)">
                                        <span class="form-check-label small">显示地点</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            :checked="moduleDisplayConfig(mod).showDivider"
                                            @change.stop="setModuleDisplayConfig(index, 'showDivider', $event.target.checked)">
                                        <span class="form-check-label small">显示分隔线</span>
                                    </label>
                                </div>
                                <div class="col-12" x-show="moduleSupportsItems(mod)">
                                    <label class="form-label small mb-1">条目标记</label>
                                    <select class="form-select form-select-sm"
                                        :value="moduleDisplayConfig(mod).itemMarker"
                                        @change.stop="setModuleDisplayConfig(index, 'itemMarker', $event.target.value)">
                                        <option value="dot">圆点</option>
                                        <option value="dash">短横线</option>
                                        <option value="none">无标记</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">模块栏位</label>
                                    <select class="form-select form-select-sm"
                                        :value="moduleDisplayConfig(mod).layoutColumn || 'auto'"
                                        @change.stop="setModuleDisplayConfig(index, 'layoutColumn', $event.target.value)">
                                        <option value="auto">自动布局</option>
                                        <option value="left">左栏优先</option>
                                        <option value="right">右栏优先</option>
                                        <option value="full">通栏展示</option>
                                    </select>
                                    <div class="form-text" style="font-size: 11px;">双栏模板中会优先生效，单栏模板会自动回退为更接近的展示方式。</div>
                                </div>
                            </div>

                            {{-- 标题设置 --}}
                            <div class="small text-secondary mb-2 fw-medium"><i class="ti ti-heading me-1"></i>标题</div>
                            <div class="editor-module-heading-card mb-3">
                                <div class="editor-module-heading-preview"
                                    :style="`font-size:${moduleDisplayConfig(mod).headingFontSize || headingFontSize}em;color:${moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937'};`">
                                    <span class="editor-module-heading-preview-label">当前效果</span>
                                    <span x-text="moduleTypeLabel(mod.type) || '模块标题'"></span>
                                </div>
                                <div class="form-text mb-3" style="font-size: 11px;">
                                    这里的字号和颜色只控制标题样式，不会修改标题文字内容。
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">标题字号</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).headingFontSize || headingFontSize) === '0.95' }"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', '0.95')">
                                                紧凑
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).headingFontSize || headingFontSize) === '1.11' }"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', '1.11')">
                                                标准
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).headingFontSize || headingFontSize) === '1.25' }"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', '1.25')">
                                                醒目
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).headingFontSize"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', Math.max(0.8, Number(moduleDisplayConfig(mod).headingFontSize || headingFontSize) - 0.05).toFixed(2))"
                                                title="缩小标题">
                                                <i class="ti ti-minus"></i>
                                            </button>
                                            <input type="range" class="form-range" min="0.8" max="1.5" step="0.05"
                                                :value="moduleDisplayConfig(mod).headingFontSize || headingFontSize"
                                                @input.stop="setModuleDisplayConfig(index, 'headingFontSize', $event.target.value)">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'headingFontSize', Math.min(1.5, Number(moduleDisplayConfig(mod).headingFontSize || headingFontSize) + 0.05).toFixed(2))"
                                                title="放大标题">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                            <span class="text-muted small editor-module-heading-metric"
                                                x-text="Number(moduleDisplayConfig(mod).headingFontSize || headingFontSize).toFixed(2) + 'em'"></span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small mb-1">标题颜色</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937').toLowerCase() === '#1f2937' }"
                                                style="--chip-color:#1f2937"
                                                @click.stop="setModuleDisplayConfig(index, 'headingColor', '#1f2937')">
                                                深灰
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937').toLowerCase() === '#2563eb' }"
                                                style="--chip-color:#2563eb"
                                                @click.stop="setModuleDisplayConfig(index, 'headingColor', '#2563eb')">
                                                蓝色
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937').toLowerCase() === '#0f766e' }"
                                                style="--chip-color:#0f766e"
                                                @click.stop="setModuleDisplayConfig(index, 'headingColor', '#0f766e')">
                                                青绿
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937').toLowerCase() === '#7c3aed' }"
                                                style="--chip-color:#7c3aed"
                                                @click.stop="setModuleDisplayConfig(index, 'headingColor', '#7c3aed')">
                                                紫色
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).headingColor"
                                                @click.stop="setModuleDisplayConfig(index, 'headingColor', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" style="width: 32px; height: 32px; padding: 2px; cursor: pointer;"
                                                :value="moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937'"
                                                @input.stop="setModuleDisplayConfig(index, 'headingColor', $event.target.value)">
                                            <input type="text"
                                                class="form-control form-control-sm"
                                                style="max-width: 120px;"
                                                :value="moduleDisplayConfig(mod).headingColor || headingColor || '#1f2937'"
                                                @input.stop="setModuleDisplayConfig(index, 'headingColor', $event.target.value)"
                                                placeholder="#1f2937">
                                            <span class="text-muted" style="font-size: 11px;" x-text="moduleDisplayConfig(mod).headingColor ? '已单独覆盖' : '跟随全局标题色'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-module-heading-card mb-3"
                                x-show="['education', 'experience', 'project', 'skill', 'certificate', 'summary'].includes(String(mod.type || ''))">
                                <div class="editor-module-body-preview mb-0">
                                    <span class="editor-module-heading-preview-label">标题文案</span>
                                    <label class="form-label small mb-1" x-text="moduleTitleLabel(mod)"></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text"
                                            class="form-control"
                                            x-model="mod.data.title"
                                            @input="dirty = true"
                                            :placeholder="moduleTitlePlaceholder(mod)">
                                        <button type="button"
                                            class="btn btn-outline-secondary"
                                            x-show="mod.data && mod.data.title"
                                            @click.stop="mod.data.title = ''; dirty = true"
                                            title="清空标题内容">
                                            <i class="ti ti-eraser"></i>
                                        </button>
                                    </div>
                                    <div class="form-text" style="font-size: 11px;">这里只改标题文字本身，字号和颜色请使用上面的标题样式设置。</div>
                                </div>
                            </div>

                            {{-- 正文设置 --}}
                            <div class="small text-secondary mb-2 fw-medium"><i class="ti ti-letter-case me-1"></i>正文</div>
                            <div class="editor-module-heading-card mb-3">
                                <div class="editor-module-body-preview"
                                    :style="`font-size:${moduleDisplayConfig(mod).bodyFontSize || fontSize}px;line-height:${moduleDisplayConfig(mod).bodyLineHeight || lineHeight};color:${moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937'};`">
                                    <span class="editor-module-heading-preview-label">当前效果</span>
                                    <span x-text="moduleSummary(mod) || '建议用 1-2 句概括职责、亮点与结果，方便快速观察正文样式变化。'"></span>
                                </div>
                                <div class="form-text mb-3" style="font-size: 11px;">
                                    这里控制正文的字号、颜色和行距，不会修改正文文字内容。
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">正文大小</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyFontSize || fontSize) === '11' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', '11')">
                                                紧凑
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyFontSize || fontSize) === '12' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', '12')">
                                                标准
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyFontSize || fontSize) === '13.5' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', '13.5')">
                                                易读
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).bodyFontSize"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', Math.max(11, Number(moduleDisplayConfig(mod).bodyFontSize || fontSize) - 0.5).toFixed(1))"
                                                title="缩小正文字号">
                                                <i class="ti ti-minus"></i>
                                            </button>
                                            <input type="range" class="form-range" min="11" max="16" step="0.5"
                                                :value="moduleDisplayConfig(mod).bodyFontSize || fontSize"
                                                @input.stop="setModuleDisplayConfig(index, 'bodyFontSize', $event.target.value)">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontSize', Math.min(16, Number(moduleDisplayConfig(mod).bodyFontSize || fontSize) + 0.5).toFixed(1))"
                                                title="放大正文字号">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                            <span class="text-muted small editor-module-heading-metric"
                                                x-text="Number(moduleDisplayConfig(mod).bodyFontSize || fontSize).toFixed(1) + 'px'"></span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small mb-1">行距</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyLineHeight || lineHeight) === '1.4' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', '1.4')">
                                                紧凑
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyLineHeight || lineHeight) === '1.6' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', '1.6')">
                                                标准
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).bodyLineHeight || lineHeight) === '1.9' }"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', '1.9')">
                                                舒展
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).bodyLineHeight"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', Math.max(1.3, Number(moduleDisplayConfig(mod).bodyLineHeight || lineHeight) - 0.1).toFixed(1))"
                                                title="减小行距">
                                                <i class="ti ti-minus"></i>
                                            </button>
                                            <input type="range" class="form-range" min="1.3" max="2.2" step="0.1"
                                                :value="moduleDisplayConfig(mod).bodyLineHeight || lineHeight"
                                                @input.stop="setModuleDisplayConfig(index, 'bodyLineHeight', $event.target.value)">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyLineHeight', Math.min(2.2, Number(moduleDisplayConfig(mod).bodyLineHeight || lineHeight) + 0.1).toFixed(1))"
                                                title="增大行距">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                            <span class="text-muted small editor-module-heading-metric"
                                                x-text="Number(moduleDisplayConfig(mod).bodyLineHeight || lineHeight).toFixed(1)"></span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small mb-1">正文字体颜色</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937').toLowerCase() === '#1f2937' }"
                                                style="--chip-color:#1f2937"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontColor', '#1f2937')">
                                                深灰
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937').toLowerCase() === '#334155' }"
                                                style="--chip-color:#334155"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontColor', '#334155')">
                                                石板灰
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937').toLowerCase() === '#475569' }"
                                                style="--chip-color:#475569"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontColor', '#475569')">
                                                柔和灰
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).bodyFontColor"
                                                @click.stop="setModuleDisplayConfig(index, 'bodyFontColor', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" style="width: 32px; height: 32px; padding: 2px; cursor: pointer;"
                                                :value="moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937'"
                                                @input.stop="setModuleDisplayConfig(index, 'bodyFontColor', $event.target.value)">
                                            <input type="text"
                                                class="form-control form-control-sm"
                                                style="max-width: 120px;"
                                                :value="moduleDisplayConfig(mod).bodyFontColor || bodyFontColor || '#1f2937'"
                                                @input.stop="setModuleDisplayConfig(index, 'bodyFontColor', $event.target.value)"
                                                placeholder="#1f2937">
                                            <span class="text-muted" style="font-size: 11px;" x-text="moduleDisplayConfig(mod).bodyFontColor ? '已单独覆盖' : '跟随全局正文色'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="editor-module-heading-card mb-3"
                                x-show="String(mod.type || '') !== 'personal'">
                                <div class="editor-module-body-preview mb-0">
                                    <span class="editor-module-heading-preview-label">正文内容</span>
                                    <div class="form-text mb-3" style="font-size: 11px;">
                                        这里只改正文文字本身，字号、颜色和行距请使用上面的正文样式设置。
                                    </div>

                                    <div class="mb-3"
                                        x-show="['objective', 'summary', 'education', 'experience', 'project'].includes(String(mod.type || ''))">
                                        <label class="form-label small mb-1" x-text="moduleContentLabel(mod)"></label>
                                        <textarea class="form-control form-control-sm"
                                            rows="4"
                                            x-model="mod.data.content"
                                            @input="dirty = true"
                                            :placeholder="moduleContentPlaceholder(mod)"></textarea>
                                    </div>

                                    <div class="mb-0"
                                        x-show="['education', 'experience', 'project'].includes(String(mod.type || '')) || (['certificate', 'skill'].includes(String(mod.type || '')) && moduleContentMode(mod) !== 'text')">
                                        <label class="form-label small mb-1" x-text="moduleItemsLabel(mod) + '（每行一条）'"></label>
                                        <textarea class="form-control form-control-sm"
                                            rows="5"
                                            :value="moduleItemsText(mod)"
                                            @input="mod.data.items = parseModuleItemsFromText($event.target.value); dirty = true"
                                            :placeholder="moduleItemPlaceholder(mod) + '\n' + moduleItemPlaceholder(mod)"></textarea>
                                        <div class="form-text" style="font-size: 11px;" x-text="moduleAddItemText(mod) + '，回车换一行即可。'"></div>
                                    </div>

                                    <div class="mb-0"
                                        x-show="['skill', 'certificate'].includes(String(mod.type || '')) && moduleContentMode(mod) === 'text'">
                                        <label class="form-label small mb-1 mt-3" x-text="moduleContentLabel(mod)"></label>
                                        <textarea class="form-control form-control-sm"
                                            rows="4"
                                            x-model="mod.data.content"
                                            @input="dirty = true"
                                            :placeholder="moduleContentPlaceholder(mod)"></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- 间距与装饰 --}}
                            <div class="small text-secondary mb-2 fw-medium"><i class="ti ti-layout-spacing me-1"></i>间距与装饰</div>
                            <div class="editor-module-heading-card mb-3">
                                <div class="editor-module-body-preview">
                                    <span class="editor-module-heading-preview-label">布局节奏</span>
                                    <div class="editor-module-spacing-preview"
                                        :style="`--spacing-preview-gap:${moduleDisplayConfig(mod).sectionSpacing || sectionSpacing}px;--spacing-preview-accent:${moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb'};`">
                                        <span class="editor-module-spacing-bar"></span>
                                        <span class="editor-module-spacing-text">这里只控制模块之间的留白和阅读节奏，不会改变标题颜色或正文内容。</span>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">模块间距</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) === '10' }"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', '10')">
                                                紧凑
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) === '16' }"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', '16')">
                                                标准
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                :class="{ 'active': String(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) === '24' }"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', '24')">
                                                舒展
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).sectionSpacing"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', Math.max(8, Number(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) - 2).toString())"
                                                title="减小模块间距">
                                                <i class="ti ti-minus"></i>
                                            </button>
                                            <input type="range" class="form-range" min="8" max="32" step="2"
                                                :value="moduleDisplayConfig(mod).sectionSpacing || sectionSpacing"
                                                @input.stop="setModuleDisplayConfig(index, 'sectionSpacing', $event.target.value)">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click.stop="setModuleDisplayConfig(index, 'sectionSpacing', Math.min(32, Number(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) + 2).toString())"
                                                title="增大模块间距">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                            <span class="text-muted small editor-module-heading-metric"
                                                x-text="Number(moduleDisplayConfig(mod).sectionSpacing || sectionSpacing) + 'px'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-module-heading-card">
                                <div class="editor-module-body-preview">
                                    <span class="editor-module-heading-preview-label">强调样式</span>
                                    <div class="editor-module-spacing-preview"
                                        :style="`--spacing-preview-gap:10px;--spacing-preview-accent:${moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb'};`">
                                        <span class="editor-module-spacing-bar"></span>
                                        <span class="editor-module-spacing-text">强调色会影响标题分隔线、图标、条目标记和局部高亮，但不会修改标题文字本身。</span>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">强调色</label>
                                        <div class="editor-module-heading-preset-wrap mb-2">
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb').toLowerCase() === '#2563eb' }"
                                                style="--chip-color:#2563eb"
                                                @click.stop="setModuleDisplayConfig(index, 'accentColor', '#2563eb')">
                                                蓝色
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb').toLowerCase() === '#0f766e' }"
                                                style="--chip-color:#0f766e"
                                                @click.stop="setModuleDisplayConfig(index, 'accentColor', '#0f766e')">
                                                青绿
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb').toLowerCase() === '#ea580c' }"
                                                style="--chip-color:#ea580c"
                                                @click.stop="setModuleDisplayConfig(index, 'accentColor', '#ea580c')">
                                                橙色
                                            </button>
                                            <button type="button" class="editor-color-chip"
                                                :class="{ 'is-active': (moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb').toLowerCase() === '#7c3aed' }"
                                                style="--chip-color:#7c3aed"
                                                @click.stop="setModuleDisplayConfig(index, 'accentColor', '#7c3aed')">
                                                紫色
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-ghost-secondary"
                                                x-show="moduleDisplayConfig(mod).accentColor"
                                                @click.stop="setModuleDisplayConfig(index, 'accentColor', '')">
                                                跟随全局
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" style="width: 32px; height: 32px; padding: 2px; cursor: pointer;"
                                                :value="moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb'"
                                                @input.stop="setModuleDisplayConfig(index, 'accentColor', $event.target.value)">
                                            <input type="text"
                                                class="form-control form-control-sm"
                                                style="max-width: 120px;"
                                                :value="moduleDisplayConfig(mod).accentColor || accentColor || '#2563eb'"
                                                @input.stop="setModuleDisplayConfig(index, 'accentColor', $event.target.value)"
                                                placeholder="#2563eb">
                                            <span class="text-muted" style="font-size: 11px;" x-text="moduleDisplayConfig(mod).accentColor ? '已单独覆盖' : '跟随全局强调色'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2" style="font-size: 11px;" x-show="moduleDisplayConfig(mod).headingColor || moduleDisplayConfig(mod).accentColor || moduleDisplayConfig(mod).headingFontSize || moduleDisplayConfig(mod).sectionSpacing || moduleDisplayConfig(mod).bodyFontColor || moduleDisplayConfig(mod).bodyFontSize || moduleDisplayConfig(mod).bodyLineHeight">
                                自定义设置会覆盖全局排版，点击 × 可重置为全局值
                            </div>
                        </div>
                    </div>
                </div>
                <div class="editor-module-detail-mask" x-show="rawAutoFixPreview.visible" x-transition.opacity @click="closeRawAutoFixPreview()"></div>
                <div class="editor-module-detail-panel" x-show="rawAutoFixPreview.visible" x-transition @click.stop style="max-width: 760px; z-index: 1066;">
                    <div class="editor-module-detail-head">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-adjustments text-primary"></i>
                            <span class="fw-semibold">自动修正预览</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost-secondary" @click="closeRawAutoFixPreview()">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="editor-module-detail-body">
                        <div class="small text-muted mb-2">请先确认修正内容，再应用到当前自由文本。</div>
                        <div class="row g-2">
                            <div class="col-12 col-lg-6">
                                <div class="small fw-semibold mb-1">修正前</div>
                                <pre class="border rounded p-2 bg-light-subtle mb-0" style="min-height: 140px; white-space: pre-wrap;" x-text="rawAutoFixPreview.before || '（空）'"></pre>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="small fw-semibold mb-1">修正后</div>
                                <pre class="border rounded p-2 bg-light-subtle mb-0" style="min-height: 140px; white-space: pre-wrap;" x-text="rawAutoFixPreview.after || '（空）'"></pre>
                            </div>
                        </div>
                    </div>
                    <div class="editor-module-detail-head border-top">
                        <div class="small text-muted">若无差异将不会覆盖原文。</div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="closeRawAutoFixPreview()">取消</button>
                            <button type="button" class="btn btn-primary btn-sm" @click="confirmAutoFixModuleRawText()">确认应用</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
