<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 14px)); line-height: var(--body-lh, var(--rs-line-height, 1.75)); color: var(--body-c, #1a1a1a); padding: 44px 40px; --heading-fs: var(--rs-heading-font-size, 0.89em); --heading-c: var(--rs-heading-color, var(--accent-color, #1a1a1a)); --accent-c: var(--rs-accent-color, var(--accent-color, #1a1a1a)); --section-mb: var(--rs-section-spacing, 24px); --body-c: var(--rs-body-color, #1a1a1a); --body-fs: var(--rs-font-size, 14px); --body-lh: var(--rs-line-height, 1.75);`">
    {{-- 顶部大标题 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="margin-bottom: 32px;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: 32px; ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <h1 style="margin: 0 0 16px 0; font-size: 2.67em; font-weight: 300; color: var(--accent-c); letter-spacing: 2px;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                            x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 1em; color: var(--body-c); opacity: 0.5; display: flex; gap: 24px; flex-wrap: wrap; letter-spacing: 0.5px;">
                        <span x-show="mod.data.phone"><span class="inline-edit" contenteditable="true" data-placeholder="电话"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                            x-text="mod.data.phone || ''"></span></span>
                        <span x-show="mod.data.email"><span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                            x-text="mod.data.email || ''"></span></span>
                        <span x-show="mod.data.location"><span class="inline-edit" contenteditable="true" data-placeholder="城市"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                            x-text="mod.data.location || ''"></span></span>
                    </div>
                </div>
                <div x-show="mod.data.avatar" style="flex-shrink: 0;">
                    <img :src="mod.data.avatar" alt="头像" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 1px solid #e5e7eb;">
                </div>
            </div>
            <div style="width: 80px; height: 1px; background: var(--accent-c); margin-top: 24px; opacity: 0.3;"></div>
        </div>
    </template>

    {{-- 双栏布局 --}}
    <div style="display: flex; gap: 40px; flex-wrap: nowrap;">
        {{-- 左侧 60% --}}
        <div :style="splitColumnStyle('left')">
            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'left', 'executive') && m.type !== 'personal')" :key="mod._key">
                <div :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`">
                    <template x-if="['experience','project'].includes(mod.type)">
                        <div>
                            <h3 style="margin: 0 0 12px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                    x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                            </h3>
                            <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.55; margin-bottom: 8px; display: flex; gap: 14px; flex-wrap: wrap;">
                                <span style="font-weight: 600; color: var(--body-c); opacity: 0.8;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                    x-text="mod.data.subtitle || ''"></span></span>
                                <span x-show="mod.data.date"><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                    x-text="mod.data.date || ''"></span></span>
                            </div>
                            <template x-if="mod.data.content">
                                <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 8px; white-space: pre-wrap;"
                                    class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                    x-text="mod.data.content"></div>
                            </template>
                            <ul class="inline-edit-list" style="margin: 0; padding-left: 0; font-size: 0.96em; list-style: none;">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <li class="inline-item-wrap" style="margin-bottom: 8px; padding-left: 14px; position: relative;">
                                        <span style="position: absolute; left: 0; top: 8px; width: 5px; height: 5px; background: var(--accent-c); border-radius: 50%; opacity: 0.4;"></span>
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

                    <template x-if="mod.type === 'objective'">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">求职意向</h3>
                            <div style="font-size: 1.04em; margin-bottom: 6px; color: var(--body-c);"><strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                                x-text="mod.data.target_job || ''"></span></strong></div>
                            <div style="font-size: 0.96em; color: var(--body-c); opacity: 0.7; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- 右侧 40% --}}
        <div :style="`${splitColumnStyle('right')} border-left: 1px solid #e5e7eb; padding-left: 30px;`">
            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'right', 'executive') && m.type !== 'personal')" :key="mod._key">
                <div :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: var(--section-mb); ${moduleStyleOverride(mod)}`">
                    <template x-if="mod.type === 'education'">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="教育经历"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                    x-text="mod.data.title || '教育经历'"></span>
                            </h3>
                            <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.55; margin-bottom: 6px;">
                                <span style="font-weight: 600; color: var(--body-c); opacity: 0.8;"><span class="inline-edit" contenteditable="true" data-placeholder="学校"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                    x-text="mod.data.subtitle || ''"></span></span>
                                <span x-show="mod.data.date" style="margin-left: 10px;"><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                    x-text="mod.data.date || ''"></span></span>
                            </div>
                            <ul class="inline-edit-list" style="margin: 0; padding-left: 14px; font-size: 0.89em;">
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

                    <template x-if="mod.type === 'skill'">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="核心技能"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                    x-text="mod.data.title || '核心技能'"></span>
                            </h3>
                            <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <span class="inline-item-wrap" x-show="item !== undefined" style="font-size: 0.85em; padding: 3px 10px; background: #f3f4f6; color: var(--body-c); border-radius: 3px;">
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
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加</div>
                        </div>
                    </template>

                    <template x-if="mod.type === 'certificate'">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="资质认证"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                    x-text="mod.data.title || '资质认证'"></span>
                            </h3>
                            <ul class="inline-edit-list" style="margin: 0; padding-left: 14px; font-size: 0.89em;">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <li class="inline-item-wrap" style="margin-bottom: 3px;">
                                        <span class="inline-edit inline-item" contenteditable="true" data-placeholder="证书"
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

                    <template x-if="mod.type === 'summary'">
                        <div>
                            <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.15em); text-transform: var(--rs-title-transform, uppercase);">
                                <span class="inline-edit" contenteditable="true" data-placeholder="个人简介"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                    x-text="mod.data.title || '个人简介'"></span>
                            </h3>
                            <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.8; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #1a1a1a; }
    .theme-coral { --accent-color: #6b2121; }
    .theme-green { --accent-color: #1a3a2a; }
    .theme-purple { --accent-color: #2d1a4e; }
    .theme-orange { --accent-color: #4a2c0a; }
    </style>
</div>
