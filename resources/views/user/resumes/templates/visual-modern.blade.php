<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); display: flex; min-height: 100%; --heading-fs: var(--rs-heading-font-size, 1.04em); --heading-c: var(--rs-heading-color, var(--primary-color, #1e3a5f)); --accent-c: var(--rs-accent-color, var(--accent-color, #1e3a5f)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 左侧边栏 --}}
    <div :style="`${splitColumnStyle('left')} background: var(--sidebar-bg, #1e3a5f); color: #fff; padding: 24px 16px; flex-shrink: 0;`">
        <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'left', 'modern'))" :key="mod._key">
            <div style="margin-bottom: 20px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 20px; ${moduleStyleOverride(mod)}`">
                <template x-if="mod.type === 'personal'">
                    <div>
                        <div x-show="mod.data.avatar" style="margin-bottom: 12px;">
                            <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                        </div>
                        <h2 style="margin: 0 0 10px 0; font-size: 1.48em; font-weight: 700; color: #fff;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                                x-text="mod.data.name || ''"></span>
                        </h2>
                        <div style="font-size: 0.89em; color: #93c5fd;">
                            <div><i class="ti ti-phone me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                                x-text="mod.data.phone || ''"></span></div>
                            <div><i class="ti ti-mail me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                                x-text="mod.data.email || ''"></span></div>
                            <div><i class="ti ti-map-pin me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                x-text="mod.data.location || ''"></span></div>
                        </div>
                    </div>
                </template>

                <template x-if="mod.type === 'skill'">
                    <div>
                        <div style="font-size: 0.81em; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; font-weight: 600;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '技能证书'"></span>
                        </div>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="font-size: 0.81em; padding: 2px 8px; background: rgba(255,255,255,0.15); border-radius: 3px;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 3px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.89em; color: #dbeafe; white-space: pre-wrap; margin-top: 6px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                        <div class="inline-add-btn" style="color: #93c5fd;" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                    </div>
                </template>

                <template x-if="mod.type === 'summary'">
                    <div>
                        <div style="font-size: 0.81em; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; font-weight: 600;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '自我评价'"></span>
                        </div>
                        <div style="font-size: 0.89em; color: #dbeafe; white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- 右侧主内容 --}}
    <div :style="`${splitColumnStyle('right')} padding: 24px 28px; background: #fff;`">
        <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'right', 'modern'))" :key="mod._key">
            <div :style="`margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'objective'">
                    <div>
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 2px) solid var(--rs-title-rule-color, var(--accent-c)); padding-bottom: var(--rs-title-rule-padding, 4px);">
                            <i class="ti ti-briefcase me-1"></i>求职意向
                        </h3>
                        <div style="font-size: 0.96em;"><strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span></div>
                        <div style="font-size: 0.96em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>

                <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                    <div>
                        <h3 style="margin: 0 0 6px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-bottom: var(--rs-title-rule-width, 2px) solid var(--rs-title-rule-color, var(--accent-c)); padding-bottom: var(--rs-title-rule-padding, 4px);">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.65; margin-bottom: 4px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <span><strong class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                x-text="mod.data.subtitle || ''"></strong></span>
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
                        <ul class="inline-edit-list" style="margin: 6px 0 0 0; padding-left: 16px; font-size: 0.96em;">
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
            </div>
        </template>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --primary-color: #1e3a5f; --accent-color: #1e3a5f; --sidebar-bg: #1e3a5f; }
    .theme-coral { --primary-color: #1a1a1a; --accent-color: #ff6b6b; --sidebar-bg: #1a1a1a; }
    .theme-green { --primary-color: #1e3a5f; --accent-color: #27ae60; --sidebar-bg: #1b4d3e; }
    .theme-purple { --primary-color: #1a1a1a; --accent-color: #7c3aed; --sidebar-bg: #2e1065; }
    .theme-orange { --primary-color: #1a1a1a; --accent-color: #f59e0b; --sidebar-bg: #78350f; }
    </style>
</div>
