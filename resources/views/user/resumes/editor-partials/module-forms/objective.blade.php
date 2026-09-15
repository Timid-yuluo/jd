<template x-if="mod.type === 'objective'">
                            <div class="editor-field-surface">
                                <div class="editor-surface-head">
                                    <div>
                                        <div class="editor-surface-title"><i class="ti ti-target-arrow"></i>岗位方向</div>
                                        <div class="editor-surface-note">明确岗位关键词，AI 优化和 ATS 评分会更准确。</div>
                                    </div>
                                </div>
                                <label class="form-label form-label-sm">目标岗位</label>
                                <input type="text" class="form-control form-control-sm mb-2" x-model="mod.data.target_job" placeholder="例如：前端开发工程师" data-field="target_job">
                                <div class="editor-field-note">尽量填写具体岗位名称，而不是泛化描述。</div>
                                <label class="form-label form-label-sm mt-3">求职方向说明</label>
                                <textarea class="form-control form-control-sm" x-model="mod.data.content" rows="3" placeholder="一句话介绍自己，概括核心优势与职业定位" data-field="content"></textarea>
                                <div class="editor-inline-helper" x-text="moduleAssistText(mod)"></div>
                            </div>
                        </template>
