<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1e293b); padding: 28px 26px; --heading-fs: var(--rs-heading-font-size, 1em); --heading-c: var(--rs-heading-color, var(--accent-color, #0f766e)); --accent-c: var(--rs-accent-color, var(--accent-color, #0f766e)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1e293b); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid #e2e8f0;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid #e2e8f0; ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: flex-start; gap: 18px;">
                <div x-show="mod.data.avatar" style="flex-shrink: 0;">
                    <img :src="mod.data.avatar" alt="头像" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-c);">
                </div>
                <div style="flex: 1;">
                    <h1 style="margin: 0 0 4px 0; font-size: 1.72em; font-weight: 700; color: var(--accent-c);">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)" x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.86em; color: var(--body-c); opacity: 0.7;">
                        <span><i class="ti ti-phone me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)" x-text="mod.data.phone || ''"></span></span>
                        <span><i class="ti ti-mail me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)" x-text="mod.data.email || ''"></span></span>
                        <span><i class="ti ti-map-pin me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)" x-text="mod.data.location || ''"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'objective')" :key="mod._key">
        <div style="margin-bottom: var(--section-mb); padding: 12px 0; border-bottom: 1px dashed #cbd5e1;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: var(--section-mb); padding: 12px 0; border-bottom: 1px dashed #cbd5e1; ${moduleStyleOverride(mod)}`">
            <span style="font-size: 0.82em; font-weight: 600; color: var(--accent-c); text-transform: uppercase; letter-spacing: 0.04em;">🎯 求职意向</span>
            <strong style="font-size: 1.05em; margin-left: 8px; color: var(--body-c);"><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)" x-text="mod.data.target_job || ''"></span></strong>
            <div style="font-size: 0.89em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="简要描述..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content || ''"></div>
        </div>
    </template>
    <div :style="dualColumnGridStyle('16px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal','objective'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'consulting', defaultFullTypes: ['summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'education'">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--accent-c); padding-bottom: 4px;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="教育经历" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '教育经历'"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.65; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600;"><span class="inline-edit" contenteditable="true" data-placeholder="学校" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)" x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)" x-text="mod.data.date || ''"></span></span>
                        </div>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.89em;">
                            <template x-for="(item, i) in mod.data.items" :key="i"><li class="inline-item-wrap" style="margin-bottom: 3px;"><span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span><span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span></li></template>
                        </ul>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                    </div>
                </template>
                <template x-if="['experience','project','certificate'].includes(mod.type)">
                    <div style="margin-bottom: var(--section-mb); position: relative; padding-left: 16px; border-left: 2px solid var(--accent-c);">
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.65; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600;"><span class="inline-edit" contenteditable="true" data-placeholder="公司" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)" x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)" x-text="mod.data.date || ''"></span></span>
                        </div>
                        <template x-if="mod.data.content"><div style="font-size: 0.89em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="简要描述..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content"></div></template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.89em;">
                            <template x-for="(item, i) in mod.data.items" :key="i"><li class="inline-item-wrap" style="margin-bottom: 3px;"><span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span><span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span></li></template>
                        </ul>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加条目</div>
                    </div>
                </template>
                <template x-if="mod.type === 'skill'">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--accent-c); padding-bottom: 4px;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能特长" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '技能特长'"></span>
                        </h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 4px 12px; background: var(--accent-bg, #f0fdfa); color: var(--accent-c); border-radius: 4px; font-size: 0.85em; font-weight: 500; border: 1px solid var(--accent-c);">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                    </div>
                </template>
                <template x-if="mod.type === 'summary'">
                    <div style="margin-bottom: var(--section-mb); background: var(--accent-bg, #f0fdfa); border-radius: 8px; padding: 14px 18px; border-left: 4px solid var(--accent-c);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                            <span class="inline-edit" contenteditable="true" data-placeholder="自我评价" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '自我评价'"></span>
                        </h3>
                        <div style="font-size: 0.93em; color: var(--body-c); white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content || ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>
    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #0f766e; --accent-bg: #f0fdfa; }
    .theme-coral { --accent-color: #be185d; --accent-bg: #fdf2f8; }
    .theme-green { --accent-color: #15803d; --accent-bg: #f0fdf4; }
    .theme-purple { --accent-color: #7c3aed; --accent-bg: #f5f3ff; }
    .theme-orange { --accent-color: #c2410c; --accent-bg: #fff7ed; }
    </style>
</div>
