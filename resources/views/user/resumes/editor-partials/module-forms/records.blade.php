<template x-if="['education','experience','project','certificate'].includes(mod.type)">
                            <div class="editor-form-stack">
                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-layout-list"></i>基础信息</div>
                                            <div class="editor-surface-note">先写标题和基本元信息，再补充描述与成果。</div>
                                        </div>
                                    </div>
                                    <label class="form-label form-label-sm" x-text="moduleTitleLabel(mod)"></label>
                                    <input type="text" class="form-control form-control-sm mb-2" x-model="mod.data.title" :placeholder="moduleTitlePlaceholder(mod)" data-field="title">
                                    <div class="editor-field-note" x-show="mod.type === 'certificate'">例如证书名称、荣誉标题或获奖事项。</div>
                                </div>
                                <template x-if="['education','experience','project'].includes(mod.type)">
                                    <div class="editor-field-surface">
                                        <div class="editor-surface-head">
                                            <div>
                                                <div class="editor-surface-title"><i class="ti ti-briefcase-2"></i>元信息</div>
                                                <div class="editor-surface-note">补充学校/公司、时间和地点，右侧会自动形成更完整的排版结构。</div>
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
                                </template>
                                <template x-if="['education','experience','project'].includes(mod.type)">
                                    <div class="editor-field-surface">
                                        <div class="editor-surface-head">
                                            <div>
                                                <div class="editor-surface-title"><i class="ti ti-align-left"></i>概述说明</div>
                                                <div class="editor-surface-note">用 1 到 2 句话概括职责、项目背景或学习方向。</div>
                                            </div>
                                        </div>
                                        <label class="form-label form-label-sm" x-text="moduleContentLabel(mod)"></label>
                                        <textarea class="form-control form-control-sm" x-model="mod.data.content" rows="3" :placeholder="moduleContentPlaceholder(mod)" data-field="content"></textarea>
                                    </div>
                                </template>
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
