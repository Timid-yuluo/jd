<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); padding: 30px 20px; --heading-fs: var(--rs-heading-font-size, 1.11em); --heading-c: var(--rs-heading-color, #1f2937); --accent-c: var(--rs-accent-color, var(--accent-color, #2563eb)); --section-mb: var(--rs-section-spacing, 24px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 头部 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid var(--accent-soft, rgba(37, 99, 235, 0.22));" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid var(--accent-soft, rgba(37, 99, 235, 0.22)); ${moduleStyleOverride(mod)}`">
            <div x-show="mod.data.avatar" style="margin-bottom: 12px;">
                <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent-c);">
            </div>
            <h1 style="margin: 0 0 10px 0; font-size: 2.07em; font-weight: 700; color: #1f2937;">
                <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                    x-text="mod.data.name || ''"></span>
            </h1>
            <div style="font-size: 1.04em; color: var(--body-c); opacity: 0.7; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
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
    </template>

    {{-- 通栏模块 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'full', 'timeline') && m.type !== 'personal')" :key="mod._key">
        <div style="margin-bottom: 20px; padding: 16px; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 20px; padding: 16px; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; ${moduleStyleOverride(mod)}`">
            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 2px) solid var(--rs-title-rule-color, var(--accent-c)); padding-bottom: var(--rs-title-rule-padding, 6px); display: inline-block;">
                <template x-if="mod.type === 'objective'"><span><i class="ti ti-briefcase me-1" style="color: var(--accent-c);"></i>求职意向</span></template>
                <template x-if="mod.type === 'summary'"><span><i class="ti ti-user-check me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                    x-text="mod.data.title || '自我评价'"></span></span></template>
            </h3>
            <template x-if="mod.type === 'objective'">
                <div>
                    <div style="font-size: 1.04em; margin-bottom: 6px;"><strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                        x-text="mod.data.target_job || ''"></span></div>
                    <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap;"
                        class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                        x-text="mod.data.content || ''"></div>
                </div>
            </template>
            <template x-if="mod.type === 'summary'">
                <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap;"
                    class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                    x-text="mod.data.content || ''"></div>
            </template>
        </div>
    </template>

    {{-- 时间线双栏主体 --}}
    <div :style="dualColumnGridStyle('18px')">
        <div style="position: relative; padding-left: 24px;">
            <div style="position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, #dbe4ef 0%, #e2e8f0 72%, transparent 100%);"></div>

            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'left', 'timeline'))" :key="mod._key">
                <div style="position: relative; margin-bottom: var(--section-mb);" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`position: relative; margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`">
                    <div style="position: absolute; left: -22px; top: 4px; width: 13px; height: 13px; border-radius: 50%; background: var(--accent-c); border: 3px solid #fff; box-shadow: 0 0 0 2px var(--accent-soft, rgba(37, 99, 235, 0.26));"></div>
                    <div style="background: #ffffff; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);">
                        <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="(mod.data.title && mod.data.title.trim()) ? mod.data.title.trim() : (moduleTypeLabel(mod.type) || '模块')"></span>
                        </h3>
                        <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.6; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                            <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                x-text="mod.data.subtitle || ''"></span></span>
                            <span x-show="mod.data.date"><i class="ti ti-calendar me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                x-text="mod.data.date || ''"></span></span>
                            <span x-show="mod.data.location"><i class="ti ti-map-pin me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                x-text="mod.data.location || ''"></span></span>
                        </div>
                        <template x-if="mod.data.content">
                            <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content"></div>
                        </template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 18px; font-size: 1em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 5px;">
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
                </div>
            </template>
        </div>

        <div>
            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'right', 'timeline') && !moduleBelongsToColumn(m, 'full', 'timeline'))" :key="mod._key">
                <div style="margin-bottom: 20px; padding: 16px; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 20px; padding: 16px; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; ${moduleStyleOverride(mod)}`">
                    <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 2px) solid var(--rs-title-rule-color, var(--accent-c)); padding-bottom: var(--rs-title-rule-padding, 6px); display: inline-block;">
                        <template x-if="mod.type === 'skill'"><span><i class="ti ti-tools me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                            x-text="mod.data.title || '技能证书'"></span></span></template>
                        <template x-if="mod.type === 'certificate'"><span><i class="ti ti-certificate me-1" style="color: var(--accent-c);"></i><span class="inline-edit" contenteditable="true" data-placeholder="获奖情况"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                            x-text="mod.data.title || '获奖情况'"></span></span></template>
                    </h3>
                    <template x-if="mod.type === 'skill'">
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-block; padding: 4px 12px; background: var(--accent-bg, #eff6ff); color: var(--accent-c); border-radius: 20px; font-size: 0.89em; font-weight: 500;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                            <div class="inline-add-btn" style="display: inline-flex;" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.96em; color: var(--body-c); white-space: pre-wrap; margin-top: 8px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                    </template>
                    <template x-if="mod.type === 'certificate'">
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 18px; font-size: 0.96em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 5px;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="证书内容"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #2563eb; --accent-bg: #eff6ff; --accent-soft: rgba(37, 99, 235, 0.22); }
    .theme-coral { --accent-color: #ff6b6b; --accent-bg: #fff0f0; --accent-soft: rgba(255, 107, 107, 0.22); }
    .theme-green { --accent-color: #27ae60; --accent-bg: #e8f5e9; --accent-soft: rgba(39, 174, 96, 0.22); }
    .theme-purple { --accent-color: #7c3aed; --accent-bg: #f3e8ff; --accent-soft: rgba(124, 58, 237, 0.22); }
    .theme-orange { --accent-color: #f59e0b; --accent-bg: #fff7ed; --accent-soft: rgba(245, 158, 11, 0.24); }
    </style>
</div>
