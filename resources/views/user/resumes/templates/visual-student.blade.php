<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #374151); padding: 28px 24px; --heading-fs: var(--rs-heading-font-size, 1.04em); --heading-c: var(--rs-heading-color, var(--accent-color, #6366f1)); --accent-c: var(--rs-accent-color, var(--accent-color, #6366f1)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #374151); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 顶部色带 + 头部 --}}
    <div style="background: var(--accent-c); height: 8px; border-radius: 8px 8px 0 0; margin: -28px -24px 0 -24px;"></div>
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="text-align: center; margin-bottom: 20px; padding: 16px 0 12px 0;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`text-align: center; margin-bottom: 20px; padding: 16px 0 12px 0; ${moduleStyleOverride(mod)}`">
            <div x-show="mod.data.avatar" style="margin-bottom: 10px;">
                <img :src="mod.data.avatar" alt="头像" style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent-c); box-shadow: 0 4px 12px rgba(99,102,241,0.2);">
            </div>
            <h1 style="margin: 0 0 8px 0; font-size: 1.78em; font-weight: 700; color: var(--accent-c);">
                <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                    x-text="mod.data.name || ''"></span>
            </h1>
            <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.65; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <span><i class="ti ti-phone me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                    x-text="mod.data.phone || ''"></span></span>
                <span><i class="ti ti-mail me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                    x-text="mod.data.email || ''"></span></span>
                <span><i class="ti ti-map-pin me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                    x-text="mod.data.location || ''"></span></span>
            </div>
        </div>
    </template>

    {{-- 求职意向 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'objective')" :key="mod._key">
        <div style="background: var(--accent-bg, #eef2ff); border-radius: 10px; padding: 14px 18px; margin-bottom: var(--section-mb);" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`background: var(--accent-bg, #eef2ff); border-radius: 10px; padding: 14px 18px; margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 0.85em; font-weight: 600; color: var(--accent-c);">🎓 求职意向</span>
                <strong style="font-size: 1em;"><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                    x-text="mod.data.target_job || ''"></span></strong>
            </div>
            <div style="font-size: 0.89em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;"
                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                x-text="mod.data.content || ''"></div>
        </div>
    </template>

    {{-- 双栏布局 --}}
    <div :style="dualColumnGridStyle('14px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal','objective'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'student', defaultFullTypes: ['summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'education'">
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 10px; padding: 14px 16px; border: 1px solid #e5e7eb; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);">
                            📚 <span class="inline-edit" contenteditable="true" data-placeholder="教育经历"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '教育经历'"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.6; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;"><span class="inline-edit" contenteditable="true" data-placeholder="学校"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                x-text="mod.data.date || ''"></span></span>
                        </div>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.89em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 3px;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </li>
                            </template>
                        </ul>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                    </div>
                </template>

                <template x-if="['experience','project','certificate'].includes(mod.type)">
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 10px; padding: 14px 16px; border: 1px solid #e5e7eb; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.6; margin-bottom: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                x-text="mod.data.date || ''"></span></span>
                            <span x-show="mod.data.location"><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                x-text="mod.data.location || ''"></span></span>
                        </div>
                        <template x-if="mod.data.content">
                            <div style="font-size: 0.89em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content"></div>
                        </template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.89em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 3px;">
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
                    <div style="margin-bottom: var(--section-mb); background: #fff; border-radius: 10px; padding: 14px 16px; border: 1px solid #e5e7eb; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                            ✨ <span class="inline-edit" contenteditable="true" data-placeholder="技能特长"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '技能特长'"></span>
                        </h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 4px 12px; background: var(--accent-bg, #eef2ff); color: var(--accent-c); border-radius: 16px; font-size: 0.85em; font-weight: 500;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.89em; color: var(--body-c); white-space: pre-wrap; margin-top: 6px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                    </div>
                </template>

                <template x-if="mod.type === 'summary'">
                    <div style="margin-bottom: var(--section-mb); background: var(--accent-bg, #eef2ff); border-radius: 10px; padding: 16px 18px;">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--accent-c);">
                            💬 <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
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
    .theme-blue { --accent-color: #6366f1; --accent-bg: #eef2ff; }
    .theme-coral { --accent-color: #f472b6; --accent-bg: #fdf2f8; }
    .theme-green { --accent-color: #34d399; --accent-bg: #ecfdf5; }
    .theme-purple { --accent-color: #a78bfa; --accent-bg: #f5f3ff; }
    .theme-orange { --accent-color: #fb923c; --accent-bg: #fff7ed; }
    </style>
</div>
