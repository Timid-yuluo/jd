<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); display: flex; min-height: 100%; --heading-fs: var(--rs-heading-font-size, 1em); --heading-c: var(--rs-heading-color, #fff); --accent-c: var(--rs-accent-color, var(--accent-color, #c9a84c)); --section-mb: var(--rs-section-spacing, 18px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 左侧深色侧边栏 --}}
    <div :style="`${splitColumnStyle('left')} background: var(--sidebar-bg, #1a2332); color: #fff; padding: 28px 18px; flex-shrink: 0;`">
        <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'left', 'professional'))" :key="mod._key">
            <div style="margin-bottom: 22px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 22px; ${moduleStyleOverride(mod)}`">
                <template x-if="mod.type === 'personal'">
                    <div>
                        <div x-show="mod.data.avatar" style="margin-bottom: 14px; text-align: center;">
                            <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent-c);">
                        </div>
                        <h2 style="margin: 0 0 12px 0; font-size: 1.48em; font-weight: 700; color: #fff; letter-spacing: 1px;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                                x-text="mod.data.name || ''"></span>
                        </h2>
                        <div style="width: 40px; height: 2px; background: var(--accent-c); margin-bottom: 14px;"></div>
                        <div style="font-size: 0.89em; color: #a0aec0; line-height: 2;">
                            <div x-show="mod.data.phone"><i class="ti ti-phone me-2" style="color: var(--accent-c); width: 16px;"></i><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                                x-text="mod.data.phone || ''"></span></div>
                            <div x-show="mod.data.email"><i class="ti ti-mail me-2" style="color: var(--accent-c); width: 16px;"></i><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                                x-text="mod.data.email || ''"></span></div>
                            <div x-show="mod.data.location"><i class="ti ti-map-pin me-2" style="color: var(--accent-c); width: 16px;"></i><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                x-text="mod.data.location || ''"></span></div>
                        </div>
                    </div>
                </template>

                <template x-if="mod.type === 'skill'">
                    <div>
                        <div style="font-size: 0.78em; color: var(--accent-c); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; font-weight: 600;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '技能证书'"></span>
                        </div>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="font-size: 0.81em; padding: 3px 10px; background: rgba(201,168,76,0.15); color: var(--accent-c); border-radius: 3px; border: 1px solid rgba(201,168,76,0.3);">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 4px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.89em; color: #a0aec0; white-space: pre-wrap; margin-top: 6px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                        <div class="inline-add-btn" style="color: var(--accent-c);" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                    </div>
                </template>

                <template x-if="mod.type === 'certificate'">
                    <div>
                        <div style="font-size: 0.78em; color: var(--accent-c); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; font-weight: 600;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="资质认证"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '资质认证'"></span>
                        </div>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 14px; font-size: 0.89em; color: #a0aec0;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 4px;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="证书"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </li>
                            </template>
                        </ul>
                        <div class="inline-add-btn" style="color: var(--accent-c);" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                    </div>
                </template>

                <template x-if="mod.type === 'summary'">
                    <div>
                        <div style="font-size: 0.78em; color: var(--accent-c); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; font-weight: 600;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '自我评价'"></span>
                        </div>
                        <div style="font-size: 0.89em; color: #a0aec0; white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- 右侧主内容 --}}
    <div :style="`${splitColumnStyle('right')} padding: 28px 30px; background: #fff;`">
        <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'right', 'professional'))" :key="mod._key">
            <div :style="`margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'objective'">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--primary-color, #1a2332); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-left: 3px solid var(--accent-c); padding-left: 10px;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="求职意向"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '求职意向'"></span>
                        </h3>
                        <div style="font-size: 0.96em; padding-left: 13px;"><strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span></div>
                        <div style="font-size: 0.96em; color: var(--body-c); margin-top: 4px; white-space: pre-wrap; padding-left: 13px;"
                            class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>

                <template x-if="['education','experience','project'].includes(mod.type)">
                    <div>
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--primary-color, #1a2332); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none); border-left: 3px solid var(--accent-c); padding-left: 10px;">
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.6; margin-bottom: 6px; padding-left: 13px; display: flex; gap: 12px; flex-wrap: wrap;">
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
                            <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 6px; white-space: pre-wrap; padding-left: 13px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content"></div>
                        </template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 30px; font-size: 0.96em;">
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
            </div>
        </template>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --primary-color: #1a2332; --accent-color: #c9a84c; --sidebar-bg: #1a2332; }
    .theme-coral { --primary-color: #2d1b1b; --accent-color: #e07a5f; --sidebar-bg: #2d1b1b; }
    .theme-green { --primary-color: #1a2e1a; --accent-color: #6b9f78; --sidebar-bg: #1a2e1a; }
    .theme-purple { --primary-color: #1e1a2e; --accent-color: #9d8ec7; --sidebar-bg: #1e1a2e; }
    .theme-orange { --primary-color: #2e2318; --accent-color: #d4915c; --sidebar-bg: #2e2318; }
    </style>
</div>
