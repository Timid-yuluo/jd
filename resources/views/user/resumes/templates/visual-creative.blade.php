<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #1f2937); padding: 30px; --heading-fs: var(--rs-heading-font-size, 1.04em); --heading-c: var(--rs-heading-color, var(--accent-color, #6366f1)); --accent-c: var(--rs-accent-color, var(--accent-color, #6366f1)); --section-mb: var(--rs-section-spacing, 16px); --body-c: var(--rs-body-color, #1f2937); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 创意头部卡片 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-c) 100%); color: #fff; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.15);" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`background: linear-gradient(135deg, var(--accent-c) 0%, var(--accent-c) 100%); color: #fff; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); ${moduleStyleOverride(mod)}`">
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                    <img x-show="mod.data.avatar" :src="mod.data.avatar" alt="头像" style="width: 100%; height: 100%; object-fit: cover;">
                    <div x-show="!mod.data.avatar" style="width: 100%; height: 100%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 2.07em; font-weight: 700;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                            x-text="(mod.data.name ? mod.data.name.charAt(0) : '')"></span>
                    </div>
                </div>
                <div>
                    <h1 style="margin: 0 0 8px 0; font-size: 1.93em; font-weight: 700; color: #fff;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                            x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 1.04em; color: rgba(255,255,255,0.9); display: flex; gap: 16px; flex-wrap: wrap;">
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

    {{-- 模块网格 --}}
    <div :style="dualColumnGridStyle('16px')">
        <template x-for="(mod, modIdx) in modules.filter(m => !['personal'].includes(m.type))" :key="mod._key">
            <div :style="moduleGridColumnStyle(mod, { template: 'creative', defaultFullTypes: ['objective', 'skill', 'summary'] }) + ' ' + moduleStyleOverride(mod)" :data-mod-index="modules.findIndex(m => m._key === mod._key)">
                <template x-if="mod.type === 'objective'">
                    <div style="background: var(--accent-bg, #eef2ff); border-radius: 12px; padding: 20px; border: 1px solid var(--accent-bg, #e5e7eb);">
                        <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);"><i class="ti ti-briefcase me-1"></i>求职意向</h3>
                        <div style="font-size: 1.04em; margin-bottom: 6px;"><strong>目标岗位：</strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span></div>
                        <div style="font-size: 0.96em; color: var(--body-c); white-space: pre-wrap;"
                            class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </div>
                </template>

                <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                    <div style="background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                        <h3 style="margin: 0 0 8px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);">
                            <i class="ti" :class="{ 'ti-school': mod.type === 'education', 'ti-building': mod.type === 'experience', 'ti-code': mod.type === 'project', 'ti-certificate': mod.type === 'certificate' }"></i>
                            <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                x-text="mod.data.title || '未命名'"></span>
                        </h3>
                        <div style="font-size: 0.89em; color: var(--body-c); opacity: 0.6; margin-bottom: 8px; display: flex; gap: 10px; flex-wrap: wrap;">
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
                            <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 8px; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content"></div>
                        </template>
                        <ul class="inline-edit-list" style="margin: 0; padding-left: 16px; font-size: 0.96em;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <li class="inline-item-wrap" style="margin-bottom: 4px; color: var(--body-c);">
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
                    <div style="background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 12px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);"><i class="ti ti-tools me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                            x-text="mod.data.title || '技能证书'"></span></h3>
                        <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <template x-for="(item, i) in mod.data.items" :key="i">
                                <span class="inline-item-wrap" x-show="item !== undefined" style="display: inline-flex; align-items: center; padding: 6px 14px; background: var(--accent-c); color: #fff; border-radius: 20px; font-size: 0.89em; font-weight: 500;">
                                    <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                        @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                        @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                        x-text="item"></span>
                                    <span class="inline-item-del" style="position: static; transform: none; margin-left: 6px; color: rgba(255,255,255,0.8);" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                            <div style="font-size: 0.96em; color: var(--body-c); white-space: pre-wrap; margin-top: 8px;"
                                class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </template>
                        <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                    </div>
                </template>

                <template x-if="mod.type === 'summary'">
                    <div style="background: var(--accent-bg, #eef2ff); border-radius: 12px; padding: 20px; border: 1px solid var(--accent-bg, #e5e7eb);">
                        <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 700); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0); text-transform: var(--rs-title-transform, none);"><i class="ti ti-user-check me-1"></i><span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                            @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                            x-text="mod.data.title || '自我评价'"></span></h3>
                        <div style="font-size: 0.96em; color: var(--body-c); white-space: pre-wrap;"
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
    .theme-coral { --accent-color: #ff6b6b; --accent-bg: #fff0f0; }
    .theme-green { --accent-color: #27ae60; --accent-bg: #e8f5e9; }
    .theme-purple { --accent-color: #7c3aed; --accent-bg: #f3e8ff; }
    .theme-orange { --accent-color: #f59e0b; --accent-bg: #fff7ed; }
    </style>
</div>
