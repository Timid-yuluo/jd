<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #2c3e50); padding: 40px 36px; --heading-fs: var(--rs-heading-font-size, 0.89em); --heading-c: var(--rs-heading-color, var(--accent-color, #2c3e50)); --accent-c: var(--rs-accent-color, var(--accent-color, #2c3e50)); --section-mb: var(--rs-section-spacing, 28px); --body-c: var(--rs-body-color, #2c3e50); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    {{-- 顶部装饰线 --}}
    <div style="width: 60px; height: 4px; background: var(--accent-c); margin-bottom: var(--section-mb);"></div>

    {{-- 头部 --}}
    <template x-for="(mod, modIdx) in modules.filter(m => m.type === 'personal')" :key="mod._key">
        <div style="margin-bottom: var(--section-mb); display: flex; align-items: center; gap: 20px; flex-wrap: wrap;" :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="`margin-bottom: var(--section-mb); display: flex; align-items: center; gap: 20px; flex-wrap: wrap; ${moduleStyleOverride(mod)}`">
            <div x-show="mod.data.avatar">
                <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-c);">
            </div>
            <div>
                <h1 style="margin: 0 0 12px 0; font-size: 2.37em; font-weight: 400; color: var(--accent-c); letter-spacing: 3px; text-transform: uppercase;">
                    <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'name', $event.target.innerText)"
                        x-text="mod.data.name || ''"></span>
                </h1>
                <div style="font-size: 0.96em; color: var(--body-c); opacity: 0.6; letter-spacing: 0.5px; display: flex; gap: 24px; flex-wrap: wrap;">
                    <span class="inline-edit" contenteditable="true" data-placeholder="电话"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'phone', $event.target.innerText)"
                        x-text="mod.data.phone || ''"></span>
                    <span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'email', $event.target.innerText)"
                        x-text="mod.data.email || ''"></span>
                    <span class="inline-edit" contenteditable="true" data-placeholder="城市"
                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                        x-text="mod.data.location || ''"></span>
                </div>
            </div>
        </div>
    </template>

    {{-- 双栏布局 --}}
    <div style="display: flex; gap: 36px; flex-wrap: nowrap;">
        {{-- 左侧 62% --}}
        <div :style="splitColumnStyle('left')">
            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'left', 'elegant') && m.type !== 'personal')" :key="mod._key">
                <div :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="moduleStyleOverride(mod)">
                    <template x-if="['experience','project'].includes(mod.type)">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 10px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                                    <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                        x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                                </h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 12px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
                            <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.6; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                                <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;" x-show="moduleDisplayConfig(mod).showSubtitle"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                    x-text="mod.data.subtitle || ''"></span></span>
                                <span x-show="moduleDisplayConfig(mod).showDate && mod.data.date"><i class="ti ti-calendar me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                    x-text="mod.data.date || ''"></span></span>
                                <span x-show="moduleDisplayConfig(mod).showLocation && mod.data.location"><i class="ti ti-map-pin me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                    x-text="mod.data.location || ''"></span></span>
                            </div>
                            <template x-if="mod.data.content">
                                <div style="font-size: 0.96em; color: var(--body-c); margin-bottom: 8px; white-space: pre-wrap;"
                                    class="inline-edit block" contenteditable="true" data-placeholder="简要描述..."
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                    x-text="mod.data.content"></div>
                            </template>
                            <div class="inline-edit-list">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <div class="inline-item-wrap"
                                        :style="moduleDisplayConfig(mod).itemMarker === 'none'
                                            ? 'margin-bottom: 10px; padding-left: 0; position: relative; font-size: 1em; color: var(--body-c);'
                                            : 'margin-bottom: 10px; padding-left: 16px; position: relative; font-size: 1em; color: var(--body-c);'"
                                        x-show="item !== undefined">
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dot'" style="position: absolute; left: 0; top: 8px; width: 5px; height: 5px; background: var(--accent-c); border-radius: 50%;"></span>
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dash'" style="position: absolute; left: 0; top: 4px; color: var(--accent-c); font-weight: 700;">—</span>
                                        <span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容"
                                            @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                            @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                            x-text="item"></span>
                                        <span class="inline-item-del" style="right: -20px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                    </div>
                                </template>
                            </div>
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加条目</div>
                        </div>
                    </template>

                    <template x-if="mod.type === 'objective'">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 10px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">求职意向</h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 12px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
                            <div style="font-size: 1em; margin-bottom: 6px; color: var(--body-c);"><strong><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'target_job', $event.target.innerText)"
                                x-text="mod.data.target_job || ''"></span></strong></div>
                            <div style="font-size: 0.96em; color: var(--body-c); opacity: 0.8; white-space: pre-wrap;"
                                class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                                @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                x-text="mod.data.content || ''"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- 右侧 38% --}}
        <div :style="`${splitColumnStyle('right')} background: var(--light-bg, #f8fafc); padding: 24px; border-radius: 4px;`">
            <template x-for="(mod, modIdx) in modules.filter(m => moduleBelongsToColumn(m, 'right', 'elegant') && m.type !== 'personal')" :key="mod._key">
                <div :data-mod-index="modules.findIndex(m => m._key === mod._key)" :style="moduleStyleOverride(mod)">
                    <template x-if="mod.type === 'education'">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 8px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                                    <span class="inline-edit" contenteditable="true" data-placeholder="教育经历"
                                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                        x-text="mod.data.title || '教育经历'"></span>
                                </h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 30px; height: 2px; background: var(--accent-c); margin-bottom: 10px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
                            <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.6; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                                <span style="font-weight: 600; color: var(--body-c); opacity: 0.85;" x-show="moduleDisplayConfig(mod).showSubtitle"><span class="inline-edit" contenteditable="true" data-placeholder="学校"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'subtitle', $event.target.innerText)"
                                    x-text="mod.data.subtitle || ''"></span></span>
                                <span x-show="moduleDisplayConfig(mod).showDate && mod.data.date"><i class="ti ti-calendar me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="时间"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'date', $event.target.innerText)"
                                    x-text="mod.data.date || ''"></span></span>
                                <span x-show="moduleDisplayConfig(mod).showLocation && mod.data.location"><i class="ti ti-map-pin me-1 text-muted" style="font-size:0.85em;"></i><span class="inline-edit" contenteditable="true" data-placeholder="地点"
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'location', $event.target.innerText)"
                                    x-text="mod.data.location || ''"></span></span>
                            </div>
                            <div class="inline-edit-list">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <div class="inline-item-wrap" style="font-size: 0.96em; color: var(--body-c); margin-bottom: 6px;" x-show="item !== undefined">
                                        <span class="inline-edit inline-item" contenteditable="true" data-placeholder="条目内容"
                                            @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                            @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                            x-text="item"></span>
                                        <span class="inline-item-del" style="right: -18px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                    </div>
                                </template>
                            </div>
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加条目</div>
                        </div>
                    </template>

                    <template x-if="mod.type === 'skill'">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 8px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                                    <span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                        x-text="mod.data.title || '技能证书'"></span>
                                </h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 30px; height: 2px; background: var(--accent-c); margin-bottom: 10px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
                            <div class="inline-edit-list">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <div class="inline-item-wrap" style="font-size: 0.96em; color: var(--body-c); margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dotted #ddd;" x-show="item !== undefined">
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dot'" class="me-1">&bull;</span>
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dash'" class="me-1">—</span>
                                        <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                            @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                            @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                            x-text="item"></span>
                                        <span class="inline-item-del" style="right: -18px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                    </div>
                                </template>
                            </div>
                            <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                                <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.85; white-space: pre-wrap; margin-top: 6px;"
                                    class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                                    @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'content', $event.target.innerText)"
                                    x-text="mod.data.content || ''"></div>
                            </template>
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加技能</div>
                        </div>
                    </template>

                    <template x-if="mod.type === 'certificate'">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 8px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                                    <span class="inline-edit" contenteditable="true" data-placeholder="获奖情况"
                                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                        x-text="mod.data.title || '获奖情况'"></span>
                                </h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 30px; height: 2px; background: var(--accent-c); margin-bottom: 10px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
                            <div class="inline-edit-list">
                                <template x-for="(item, i) in mod.data.items" :key="i">
                                    <div class="inline-item-wrap" style="font-size: 0.89em; color: var(--body-c); opacity: 0.8; margin-bottom: 4px;" x-show="item !== undefined">
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dot'">&bull;</span>
                                        <span x-show="moduleDisplayConfig(mod).itemMarker === 'dash'">—</span>
                                        <span class="inline-edit inline-item" contenteditable="true" data-placeholder="证书"
                                            @input="inlineUpdateItem(modules.findIndex(m => m._key === mod._key), i, $event.target.innerText)"
                                            @keydown="onItemEnter($event, modules.findIndex(m => m._key === mod._key), i)"
                                            x-text="item"></span>
                                        <span class="inline-item-del" style="right: -18px;" @click="removeInlineItem(modules.findIndex(m => m._key === mod._key), i)"><i class="ti ti-x"></i></span>
                                    </div>
                                </template>
                            </div>
                            <div class="inline-add-btn" @click="addInlineItem(modules.findIndex(m => m._key === mod._key))"><i class="ti ti-plus"></i>添加证书</div>
                        </div>
                    </template>

                    <template x-if="mod.type === 'summary'">
                        <div style="margin-bottom: var(--section-mb);">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom: 8px;">
                                <h3 style="margin: 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                                    <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                                        @input="inlineUpdate(modules.findIndex(m => m._key === mod._key), 'title', $event.target.innerText)"
                                        x-text="mod.data.title || '自我评价'"></span>
                                </h3>
                                <span class="badge bg-warning-lt text-warning"
                                    x-show="moduleDisplayConfig(mod).showBadge"
                                    x-text="moduleDisplayConfig(mod).badgeText || moduleDefaultBadgeText(mod)"></span>
                            </div>
                            <div style="width: 30px; height: 2px; background: var(--accent-c); margin-bottom: 10px;" x-show="moduleDisplayConfig(mod).showDivider"></div>
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

    {{-- 底部装饰线 --}}
    <div style="width: 60px; height: 4px; background: var(--accent-c); margin-top: 32px;"></div>

    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .theme-blue { --accent-color: #2c3e50; --light-bg: #f8fafc; }
    .theme-coral { --accent-color: #c0392b; --light-bg: #fdf2f2; }
    .theme-green { --accent-color: #1e8449; --light-bg: #f0fdf4; }
    .theme-purple { --accent-color: #5b21b6; --light-bg: #faf5ff; }
    .theme-orange { --accent-color: #b45309; --light-bg: #fffbeb; }
    </style>
</div>
