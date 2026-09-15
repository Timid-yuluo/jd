<template x-if="mod.type === 'skill'">
                            <div class="editor-form-stack">
                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-tools"></i>技能模块</div>
                                            <div class="editor-surface-note">可以按技术栈、工具链、业务能力或语言能力拆分。</div>
                                        </div>
                                    </div>
                                    <label class="form-label form-label-sm" x-text="moduleTitleLabel(mod)"></label>
                                    <input type="text" class="form-control form-control-sm mb-2" x-model="mod.data.title" :placeholder="moduleTitlePlaceholder(mod)" data-field="title">
                                </div>
                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-list-check"></i>技能条目</div>
                                            <div class="editor-surface-note">支持条目模式和文本模式，内容会自动同步。</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                        <label class="small text-muted mb-0">编辑模式</label>
                                        <select class="form-select form-select-sm" style="max-width: 180px;"
                                            :value="moduleContentMode(mod)"
                                            @change="setModuleContentMode(index, $event.target.value)">
                                            <option value="items">条目模式</option>
                                            <option value="text">文本模式</option>
                                        </select>
                                    </div>
                                    <label class="form-label form-label-sm" x-text="moduleItemsLabel(mod)"></label>
                                    <template x-if="moduleContentMode(mod) === 'items'">
                                        <div>
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
                                    </template>
                                    <template x-if="moduleContentMode(mod) === 'text'">
                                        <div>
                                            <textarea class="form-control form-control-sm"
                                                rows="5"
                                                :value="moduleItemsText(mod)"
                                                @input="setModuleItemsText(index, $event.target.value)"
                                                placeholder="每行一个技能，例如：Laravel（5年）&#10;MySQL 性能优化&#10;Docker / CI-CD"></textarea>
                                            <div class="editor-field-note mt-2">按行输入会自动转成技能条目，预览保持兼容。</div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
