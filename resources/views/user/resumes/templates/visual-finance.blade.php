<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); padding: 28px 26px; --heading-fs: var(--rs-heading-font-size, 1em); --heading-c: var(--rs-heading-color, var(--accent-color, #1d4ed8)); --accent-c: var(--rs-accent-color, var(--accent-color, #1d4ed8)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 22px; padding: 18px 22px; background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-end, #1e40af) 100%); border-radius: 12px; color: #fff;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`display: flex; align-items: center; gap: 18px; margin-bottom: 22px; padding: 18px 22px; background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-end, #1e40af) 100%); border-radius: 12px; color: #fff; ${moduleStyleOverride(mod)}`">
            <div x-show="mod.data.avatar" style="flex-shrink: 0;">
                <img :src="mod.data.avatar" alt="头像" style="width: 64px; height: 64px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(255,255,255,0.4);">
            </div>
            <div style="flex: 1; min-width: 180px;">
                <h1 style="margin: 0 0 6px 0; font-size: 1.72em; font-weight: 700;">
                    <span class="inline-edit" contenteditable="true" data-placeholder="姓名" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)" x-text="mod.data.name || ''"></span>
                </h1>
                <div style="display: flex; gap: 14px; flex-wrap: wrap; font-size: 0.88em; opacity: 0.9;">
                    <span><i class="ti ti-phone me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)" x-text="mod.data.phone || ''"></span></span>
                    <span><i class="ti ti-mail me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)" x-text="mod.data.email || ''"></span></span>
                    <span><i class="ti ti-map-pin me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)" x-text="mod.data.location || ''"></span></span>
                </div>
            </div>
        </div>
    </template>
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'objective')" :key="mod._key">
        <div style="background: var(--accent-bg, #eff6ff); border-radius: 8px; padding: 12px 18px; margin-bottom: var(--section-mb); border-left: 4px solid var(--accent-c);" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`background: var(--accent-bg, #eff6ff); border-radius: 8px; padding: 12px 18px; margin-bottom: var(--section-mb); border-left: 4px solid var(--accent-c); ${moduleStyleOverride(mod)}`">
            <span style="font-size: 0.82em; font-weight: 600; color: var(--accent-c);">💰 求职意向</span>
            <strong style="font-size: 1.05em; margin-left: 8px; color: var(--body-c);"><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)" x-text="mod.data.target_job || ''"></span></strong>
            <div style="font-size: 0.89em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;" class="inline-edit block" contenteditable="true" data-placeholder="简要描述..." @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)" x-text="mod.data.content || ''"></div>
        </div>
    </template>
    <div :style="dualColumnGridStyle('16px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal','objective'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'finance', defaultFullTypes: ['summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'education'">
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
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
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
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
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能特长" @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)" x-text="mod.data.title || '技能特长'"></span>
                        </h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 4px 12px; background: var(--accent-bg, #eff6ff); color: var(--accent-c); border-radius: 6px; font-size: 0.85em; font-weight: 500;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能" @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)" @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)" x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                    </div>
                </template>
                <template x-if="mod.type === 'summary'">
                    <div style="margin-bottom: var(--section-mb); background: var(--accent-bg, #eff6ff); border-radius: 8px; padding: 14px 18px;">
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
    .theme-blue { --accent-color: #1d4ed8; --accent-end: #1e40af; --accent-bg: #eff6ff; }
    .theme-coral { --accent-color: #be185d; --accent-end: #9d174d; --accent-bg: #fdf2f8; }
    .theme-green { --accent-color: #15803d; --accent-end: #166534; --accent-bg: #f0fdf4; }
    .theme-purple { --accent-color: #7c3aed; --accent-end: #6d28d9; --accent-bg: #f5f3ff; }
    .theme-orange { --accent-color: #c2410c; --accent-end: #9a3412; --accent-bg: #fff7ed; }
    </style>
</div>
