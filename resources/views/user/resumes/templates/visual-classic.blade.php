<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); ${dualColumnGridStyle('18px')} --heading-fs: var(--rs-heading-font-size, 1.11em); --heading-c: var(--rs-heading-color, var(--primary-color, #1f2937)); --accent-c: var(--rs-accent-color, var(--accent-color, #2563eb)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    <template x-for="(mod, modIdx) in modules" :key="mod._key">
        <div class="resume-module" :class="`mod-${mod.type}`" :data-mod-index="modIdx" :style="`margin-bottom: var(--section-mb); ${moduleGridColumnStyle(mod, { template: 'classic', defaultFullTypes: ['personal', 'objective', 'summary'] })} ${moduleStyleOverride(mod)}`">
            {{-- 个人信息 --}}
            <template x-if="mod.type === 'personal'">
                <div style="text-align: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--accent-c);">
                    <div x-show="mod.data.avatar" style="margin-bottom: 10px;">
                        <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-c);">
                    </div>
                    <h1 style="margin: 0 0 8px 0; font-size: 1.93em; font-weight: 700; color: var(--heading-c);">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                            @input="inlineUpdate(modIdx, 'name', $event.target.innerText)"
                            x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 0.96em; color: var(--body-c); opacity: 0.7; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                        <span><i class="ti ti-phone me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                            @input="inlineUpdate(modIdx, 'phone', $event.target.innerText)"
                            x-text="mod.data.phone || ''"></span></span>
                        <span><i class="ti ti-mail me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                            @input="inlineUpdate(modIdx, 'email', $event.target.innerText)"
                            x-text="mod.data.email || ''"></span></span>
                        <span><i class="ti ti-map-pin me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                            @input="inlineUpdate(modIdx, 'location', $event.target.innerText)"
                            x-text="mod.data.location || ''"></span></span>
                    </div>
                </div>
            </template>

            {{-- 求职意向 --}}
            <template x-if="mod.type === 'objective'">
                <div>
                    <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 1px) solid var(--rs-title-rule-color, #e5e7eb); padding-bottom: var(--rs-title-rule-padding, 4px);">
                        <i class="ti ti-briefcase me-1" style="color: var(--accent-c);"></i>求职意向
                    </h3>
                    <div style="font-size: 1.04em;">
                        <strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modIdx, 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span>
                    </div>
                    <div style="font-size: 0.96em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;"
                        class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                        @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                        x-text="mod.data.content || ''"></div>
                </div>
            </template>

            {{-- 教育 / 工作 / 项目 / 证书 --}}
            <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                <div>
                    <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 1px) solid var(--rs-title-rule-color, #e5e7eb); padding-bottom: var(--rs-title-rule-padding, 4px);">
                        <i class="ti me-1" :class="{
                            'ti-school': mod.type === 'education',
                            'ti-building': mod.type === 'experience',
                            'ti-code': mod.type === 'project',
                            'ti-certificate': mod.type === 'certificate',
                        }" style="color: var(--accent-c);"></i>
                        <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                    </h3>
                    <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.65; margin-bottom: 6px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <span><strong class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                            @input="inlineUpdate(modIdx, 'subtitle', $event.target.innerText)"
                            x-text="mod.data.subtitle || ''"></strong></span>
                        <span x-show="mod.data.date"><i class="ti ti-calendar me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                            @input="inlineUpdate(modIdx, 'date', $event.target.innerText)"
                            x-text="mod.data.date || ''"></span></span>
                        <span x-show="mod.data.location"><i class="ti ti-map-pin me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                            @input="inlineUpdate(modIdx, 'location', $event.target.innerText)"
                            x-text="mod.data.location || ''"></span></span>
                    </div>
                    <template x-if="mod.data.content">
                        <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                            @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                            x-text="mod.data.content"></div>
                    </template>
                    <ul class="inline-edit-list" style="margin: 0; padding-left: 18px; font-size: 1em;">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <li class="inline-item-wrap" style="margin-bottom: 4px;">
                                <span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容"
                                    @input="inlineUpdateItem(modIdx, i, $event.target.innerText)"
                                    @keydown="onItemEnter($event, modIdx, i)"
                                    x-text="item"></span>
                                <span class="inline-item-del" @click="removeInlineItem(modIdx, i)"><i class="ti ti-x"></i></span>
                            </li>
                        </template>
                    </ul>
                    <div class="inline-add-btn" @click="addInlineItem(modIdx)"><i class="ti ti-plus"></i>添加条目</div>
                </div>
            </template>

            {{-- 技能 --}}
            <template x-if="mod.type === 'skill'">
                <div>
                    <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 1px) solid var(--rs-title-rule-color, #e5e7eb); padding-bottom: var(--rs-title-rule-padding, 4px);">
                        <i class="ti ti-tools me-1" style="color: var(--accent-c);"></i>
                        <span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || '技能证书'"></span>
                    </h3>
                    <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 3px 10px; background: var(--accent-bg, #eff6ff); color: var(--accent-c); border-radius: 4px; font-size: 0.89em;">
                                <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                    @input="inlineUpdateItem(modIdx, i, $event.target.innerText)"
                                    @keydown="onItemEnter($event, modIdx, i)"
                                    x-text="item"></span>
                                <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modIdx, i)"><i class="ti ti-x"></i></span>
                            </span>
                        </template>
                    </div>
                    <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                        <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap; margin-top: 6px;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                            @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </template>
                    <div class="inline-add-btn" @click="addInlineItem(modIdx)"><i class="ti ti-plus"></i>添加技能</div>
                </div>
            </template>

            {{-- 自我评价 --}}
            <template x-if="mod.type === 'summary'">
                <div>
                    <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 1px) solid var(--rs-title-rule-color, #e5e7eb); padding-bottom: var(--rs-title-rule-padding, 4px);">
                        <i class="ti ti-user-check me-1" style="color: var(--accent-c);"></i>
                        <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || '自我评价'"></span>
                    </h3>
                    <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap;"
                        class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                        @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                        x-text="mod.data.content || ''"></div>
                </div>
            </template>
        </div>
    </template>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --primary-color: #1f2937; --accent-color: #2563eb; --accent-bg: #eff6ff; --secondary-color: #4b5563; }
    .theme-coral { --primary-color: #1a1a1a; --accent-color: #ff6b6b; --accent-bg: #fff0f0; --secondary-color: #555; }
    .theme-green { --primary-color: #1f2937; --accent-color: #27ae60; --accent-bg: #e8f5e9; --secondary-color: #4b5563; }
    .theme-purple { --primary-color: #1f2937; --accent-color: #7c3aed; --accent-bg: #f3e8ff; --secondary-color: #4b5563; }
    .theme-orange { --primary-color: #1f2937; --accent-color: #f59e0b; --accent-bg: #fff7ed; --secondary-color: #4b5563; }
    </style>
</div>
