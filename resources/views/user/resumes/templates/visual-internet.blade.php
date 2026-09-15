<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); --heading-fs: var(--rs-heading-font-size, 1em); --heading-c: var(--rs-heading-color, #fff); --accent-c: var(--rs-accent-color, var(--accent-color, #6366f1)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 渐变头部 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-end, #4f46e5) 100%); color: #fff; padding: 28px 30px; margin-bottom: 0;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-end, #4f46e5) 100%); color: #fff; padding: 28px 30px; margin-bottom: 0; ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                <div x-show="mod.data.avatar" style="flex-shrink: 0;">
                    <img :src="mod.data.avatar" alt="头像" style="width: 64px; height: 64px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <h1 style="margin: 0 0 6px 0; font-size: 1.78em; font-weight: 700; color: #fff;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                            x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 0.89em; color: rgba(255,255,255,0.85); display: flex; gap: 14px; flex-wrap: wrap;">
                        <span><i class="ti ti-phone me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                            x-text="mod.data.phone || ''"></span></span>
                        <span><i class="ti ti-mail me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                            x-text="mod.data.email || ''"></span></span>
                        <span><i class="ti ti-map-pin me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                            x-text="mod.data.location || ''"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- 求职意向横幅 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'objective')" :key="mod._key">
        <div style="background: var(--accent-bg, #eef2ff); padding: 14px 30px; border-bottom: 2px solid var(--accent-c);" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`background: var(--accent-bg, #eef2ff); padding: 14px 30px; border-bottom: 2px solid var(--accent-c); ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="background: var(--accent-c); color: #fff; padding: 3px 12px; border-radius: 4px; font-size: 0.85em; font-weight: 600;">求职意向</span>
                <strong style="font-size: 1em;"><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                    x-text="mod.data.target_job || ''"></span></strong>
                <span style="font-size: 0.89em; color: var(--body-c); opacity: 0.7; white-space: pre-wrap;"
                    class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                    x-text="mod.data.content || ''"></span>
            </div>
        </div>
    </template>

    {{-- 主体内容 --}}
    <div style="padding: 24px 30px;" :style="dualColumnGridStyle('16px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal','objective'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'internet', defaultFullTypes: ['summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.65; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date" style="background: #f1f5f9; padding: 1px 8px; border-radius: 3px; font-size: 0.93em;"><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                x-text="mod.data.date || ''"></span></span>
                            <span x-show="mod.data.location"><i class="ti ti-map-pin me-1" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                x-text="mod.data.location || ''"></span></span>
                        </div>
                        <template x-if="mod.data.content">
                            <div style="font-size: 0.93em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content"></div>
                        </template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.93em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 4px;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </li>
                            </template>
                        </ul>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加条目</div>
                    </div>
                </template>

                <template x-if="mod.type === 'skill'">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '技能'"></span>
                        </h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 4px 12px; background: var(--accent-c); color: #fff; border-radius: 6px; font-size: 0.85em; font-weight: 500;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 5px; color: rgba(255,255,255,0.7);" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.93em; color: var(--body-c); white-space: pre-wrap; margin-top: 6px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                    </div>
                </template>

                <template x-if="mod.type === 'summary'">
                    <div style="margin-bottom: var(--section-mb); background: var(--accent-bg, #eef2ff); border-radius: 10px; padding: 18px; border: 1px solid rgba(99,102,241,0.15);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); display: flex; align-items: center; gap: 6px;">
                            <span style="width: 4px; height: 18px; background: var(--accent-c); border-radius: 2px; display: inline-block;"></span>
                            <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '自我评价'"></span>
                        </h3>
                        <div style="font-size: 0.93em; color: var(--body-c); white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #6366f1; --accent-end: #4f46e5; --accent-bg: #eef2ff; }
    .theme-coral { --accent-color: #f43f5e; --accent-end: #e11d48; --accent-bg: #fff1f2; }
    .theme-green { --accent-color: #10b981; --accent-end: #059669; --accent-bg: #ecfdf5; }
    .theme-purple { --accent-color: #8b5cf6; --accent-end: #7c3aed; --accent-bg: #f5f3ff; }
    .theme-orange { --accent-color: #f59e0b; --accent-end: #d97706; --accent-bg: #fffbeb; }
    </style>
</div>
