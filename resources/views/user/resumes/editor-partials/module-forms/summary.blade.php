<template x-if="mod.type === 'summary'">
                            <div class="editor-field-surface">
                                <div class="editor-surface-head">
                                    <div>
                                        <div class="editor-surface-title"><i class="ti ti-message-2-star"></i>自我总结</div>
                                        <div class="editor-surface-note">用简洁语言突出你的经验年限、核心优势和业务价值。</div>
                                    </div>
                                </div>
                                <label class="form-label form-label-sm">标题</label>
                                <input type="text" class="form-control form-control-sm mb-2" x-model="mod.data.title" placeholder="例如：个人优势 / 自我评价" data-field="title">
                                <label class="form-label form-label-sm">内容</label>
                                <textarea class="form-control form-control-sm" x-model="mod.data.content" rows="4" placeholder="建议写 3-5 句，突出年限、核心技术、业务经验和协作优势，例如：5 年 Laravel 开发经验，熟悉高并发与企业级后台系统建设" data-field="content"></textarea>
                                <div class="editor-inline-helper" x-text="moduleAssistText(mod)"></div>
                            </div>
                        </template>
