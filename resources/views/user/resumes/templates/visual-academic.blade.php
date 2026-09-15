<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13px)); line-height: var(--body-lh, var(--rs-line-height, 1.65)); color: var(--body-c, #2c3e50); padding: 32px 28px; --heading-fs: var(--rs-heading-font-size, 0.93em); --heading-c: var(--rs-heading-color, var(--accent-color, #2c3e50)); --accent-c: var(--rs-accent-color, var(--accent-color, #2c3e50)); --section-mb: var(--rs-section-spacing, 16px); --body-c: var(--rs-body-color, #2c3e50); --body-fs: var(--rs-font-size, 13px); --body-lh: var(--rs-line-height, 1.65);`">
    {{-- 头部 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="margin-bottom: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 14px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 16px; border-bottom: 1px solid #d1d5db; padding-bottom: 14px; ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: baseline; gap: 16px; flex-wrap: wrap;">
                <h1 style="margin: 0; font-size: 1.78em; font-weight: 700; color: var(--accent-c); letter-spacing: 0.5px;">
                    <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                        x-text="mod.data.name || ''"></span>
                </h1>
                <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.7; display: flex; gap: 14px; flex-wrap: wrap;">
                    <span x-show="mod.data.email"><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                        x-text="mod.data.email || ''"></span></span>
                    <span x-show="mod.data.phone"><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                        x-text="mod.data.phone || ''"></span></span>
                    <span x-show="mod.data.location"><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                        x-text="mod.data.location || ''"></span></span>
                </div>
            </div>
        </div>
    </template>

    {{-- 双栏紧凑布局 --}}
    <div :style="dualColumnGridStyle('14px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'academic', defaultFullTypes: ['objective', 'summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'objective'">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.05em); text-transform: var(--rs-title-transform, uppercase); border-bottom: 1px solid var(--accent-c); padding-bottom: 3px; display: inline-block;">
                            求职意向
                        </h3>
                        <div style="font-size: 0.93em; margin-top: 6px;"><strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span></div>
                        <div style="font-size: 0.89em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>

                <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.05em); text-transform: var(--rs-title-transform, uppercase); border-bottom: 1px solid var(--accent-c); padding-bottom: 3px; display: inline-block;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.85em; color: var(--body-c); opacity: 0.65; margin-bottom: 5px; margin-top: 6px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="font-weight: 600;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
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
                            <div style="font-size: 0.89em; color: var(--body-c); margin-bottom: 5px; white-space: pre-wrap;"
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
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.05em); text-transform: var(--rs-title-transform, uppercase); border-bottom: 1px solid var(--accent-c); padding-bottom: 3px; display: inline-block;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '技能'"></span>
                        </h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: var(--body-c); border-radius: 3px; font-size: 0.85em;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 3px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
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
                    <div style="margin-bottom: var(--section-mb);">
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.05em); text-transform: var(--rs-title-transform, uppercase); border-bottom: 1px solid var(--accent-c); padding-bottom: 3px; display: inline-block;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '自我评价'"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); white-space: pre-wrap; margin-top: 6px;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #2c3e50; }
    .theme-coral { --accent-color: #8b3a3a; }
    .theme-green { --accent-color: #2d5a3d; }
    .theme-purple { --accent-color: #4a3560; }
    .theme-orange { --accent-color: #7c4a1e; }
    </style>
</div>
