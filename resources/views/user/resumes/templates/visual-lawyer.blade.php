<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1e293b); padding: 0; --heading-fs: var(--rs-heading-font-size, 1em); --heading-c: var(--rs-heading-color, #fff); --accent-c: var(--rs-accent-color, var(--accent-color, #1e3a5f)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1e293b); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    <div style="display: flex; min-height: 100%;">
        <div style="width: 200px; flex-shrink: 0; background: linear-gradient(180deg, var(--accent-c) 0%, var(--accent-end, #0f172a) 100%); color: #fff; padding: 28px 18px;">
            <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
                <div style="text-align: center; margin-bottom: 20px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`text-align: center; margin-bottom: 20px; ${moduleStyleOverride(mod)}`">
                    <div x-show="mod.data.avatar" style="margin-bottom: 12px;">
                        <img :src="mod.data.avatar" alt="头像" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.4);">
                    </div>
                    <h1 style="margin: 0 0 8px 0; font-size: 1.3em; font-weight: 700;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)" x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 0.82em; opacity: 0.85; display: flex; flex-direction: column; gap: 6px; align-items: center;">
                        <span><i class="ti ti-phone me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)" x-text="mod.data.phone || ''"></span></span>
                        <span><i class="ti ti-mail me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)" x-text="mod.data.email || ''"></span></span>
                        <span><i class="ti ti-map-pin me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)" x-text="mod.data.location || ''"></span></span>
                    </div>
                </div>
            </template>
            <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'objective')" :key="mod._key">
                <div style="margin-bottom: 20px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 20px; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; ${moduleStyleOverride(mod)}`">
                    <div style="font-size: 0.78em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.8; margin-bottom: 6px;">⚖️ 求职意向</div>
                    <div style="font-size: 0.92em; font-weight: 600;"><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)" x-text="mod.data.target_job || ''"></span></div>
                    <div style="font-size: 0.82em; opacity: 0.85; margin-top: 4px; white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="简要描述..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content || ''"></div>
                </div>
            </template>
            <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'skill')" :key="mod._key">
                <div style="margin-bottom: 20px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 20px; ${moduleStyleOverride(mod)}`">
                    <h3 style="margin: 0 0 10px 0; font-size: 0.82em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.8; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 6px;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="技能特长" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '技能特长'"></span>
                    </h3>
                    <div class="inline-edit-list" style="display: flex; flex-direction: column; gap: 5px;">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 3px 10px; background: rgba(255,255,255,0.12); border-radius: 4px; font-size: 0.82em;">
                                <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span>
                                <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px; opacity: 0.6;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                            </span>
                        </template>
                    </div>
                    <div class="inline-add-btn" style="color: rgba(255,255,255,0.6);" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                </div>
            </template>
        </div>
        <div style="flex: 1; padding: 28px 26px;">
            <template x-for="(mod, modIdx) in modules.filter(m => !['personal','objective','skill'].includes(m.type))" :key="mod._key">
                <div :style="moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                    <template x-if="mod.type === 'education'">
                        <div style="margin-bottom: var(--section-mb);">
                            <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); text-transform: uppercase; letter-spacing: 0.04em;">
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
                        <div style="margin-bottom: var(--section-mb); border-left: 3px solid var(--accent-c); padding-left: 14px;">
                            <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="模块标题" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                            </h3>
                            <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.65; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                                <span style="font-weight: 600;"><span class="inline-edit" contenteditable="true" data-placeholder="机构" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)" x-text="mod.data.subtitle || ''"></span></span>
                                <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)" x-text="mod.data.date || ''"></span></span>
                            </div>
                            <template x-if="mod.data.content"><div style="font-size: 0.89em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="简要描述..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content"></div></template>
                            <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.89em;">
                                <template x-for="(item, i) in mod.data.items" :key="i"><li class="inline-item-wrap" style="margin-bottom: 3px;"><span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span><span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span></li></template>
                            </ul>
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加条目</div>
                        </div>
                    </template>
                    <template x-if="mod.type === 'summary'">
                        <div style="margin-bottom: var(--section-mb); background: var(--accent-bg, #f0f4f8); border-radius: 8px; padding: 14px 18px;">
                            <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="自我评价" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '自我评价'"></span>
                            </h3>
                            <div style="font-size: 0.93em; color: var(--body-c); white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content || ''"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #1e3a5f; --accent-end: #0f172a; --accent-bg: #f0f4f8; }
    .theme-coral { --accent-color: #831843; --accent-end: #4c0519; --accent-bg: #fdf2f8; }
    .theme-green { --accent-color: #14532d; --accent-end: #052e16; --accent-bg: #f0fdf4; }
    .theme-purple { --accent-color: #3b0764; --accent-end: #1e0533; --accent-bg: #f5f3ff; }
    .theme-orange { --accent-color: #7c2d12; --accent-end: #431407; --accent-bg: #fff7ed; }
    </style>
</div>
