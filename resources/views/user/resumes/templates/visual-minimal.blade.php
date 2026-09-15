<div class="visual-resume" :class="`template-${template}`" :style="`font-size: var(--body-fs, var(--rs-font-size, 13.5px)); line-height: var(--body-lh, var(--rs-line-height, 1.7)); color: var(--body-c, #2d2d2d); padding: 36px 32px; ${dualColumnGridStyle('24px')} --heading-fs: var(--rs-heading-font-size, 0.89em); --heading-c: var(--rs-heading-color, #1a1a1a); --accent-c: var(--rs-accent-color, var(--accent-color, #2563eb)); --section-mb: var(--rs-section-spacing, 24px); --body-c: var(--rs-body-color, #2d2d2d); --body-fs: var(--rs-font-size, 13.5px); --body-lh: var(--rs-line-height, 1.7);`">
    <template x-for="(mod, modIdx) in modules" :key="mod._key">
        <div :style="`margin-bottom: var(--section-mb); ${moduleGridColumnStyle(mod, { template: 'minimal', defaultFullTypes: ['personal', 'objective', 'summary'] })} ${moduleStyleOverride(mod)}`" :data-mod-index="modIdx">
            <template x-if="mod.type === 'personal'">
                <div style="text-align: center; margin-bottom: 28px;">
                    <div x-show="mod.data.avatar" style="margin-bottom: 12px;">
                        <img :src="mod.data.avatar" alt="头像" class="resume-avatar" style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                    </div>
                    <h1 style="margin: 0 0 8px 0; font-size: 1.78em; font-weight: 400; color: var(--heading-c); letter-spacing: 2px; text-transform: uppercase;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="姓名"
                            @input="inlineUpdate(modIdx, 'name', $event.target.innerText)"
                            x-text="mod.data.name || ''"></span>
                    </h1>
                    <div style="font-size: 0.96em; color: var(--body-c); opacity: 0.65; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        <span class="inline-edit" contenteditable="true" data-placeholder="电话"
                            @input="inlineUpdate(modIdx, 'phone', $event.target.innerText)"
                            x-text="mod.data.phone || ''"></span>
                        <span class="inline-edit" contenteditable="true" data-placeholder="邮箱"
                            @input="inlineUpdate(modIdx, 'email', $event.target.innerText)"
                            x-text="mod.data.email || ''"></span>
                        <span class="inline-edit" contenteditable="true" data-placeholder="城市"
                            @input="inlineUpdate(modIdx, 'location', $event.target.innerText)"
                            x-text="mod.data.location || ''"></span>
                    </div>
                    <div style="width: 40px; height: 1px; background: #ccc; margin: 14px auto 0;"></div>
                </div>
            </template>

            <template x-if="mod.type === 'objective'">
                <div>
                    <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                        <span>求职意向：</span><span class="inline-edit" contenteditable="true" data-placeholder="目标岗位"
                            @input="inlineUpdate(modIdx, 'target_job', $event.target.innerText)"
                            x-text="mod.data.target_job || ''"></span>
                    </h3>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap;"
                        class="inline-edit block" contenteditable="true" data-placeholder="简要描述求职意向..."
                        @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                        x-text="mod.data.content || ''"></div>
                </div>
            </template>

            <template x-if="['education','experience','project','certificate'].includes(mod.type)">
                <div>
                    <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                        <span class="inline-edit" contenteditable="true" data-placeholder="模块标题"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || moduleTypeLabel(mod.type)"></span>
                    </h3>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    <div style="font-size: 0.93em; color: var(--body-c); opacity: 0.6; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <span style="font-weight: 600; color: var(--body-c); opacity: 0.8;"><span class="inline-edit" contenteditable="true" data-placeholder="机构/公司"
                            @input="inlineUpdate(modIdx, 'subtitle', $event.target.innerText)"
                            x-text="mod.data.subtitle || ''"></span></span>
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
                    <ul class="inline-edit-list" style="margin: 0; padding-left: 18px; color: var(--body-c);">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <li class="inline-item-wrap" style="margin-bottom: 5px; font-size: 1em;">
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

            <template x-if="mod.type === 'skill'">
                <div>
                    <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                        <span class="inline-edit" contenteditable="true" data-placeholder="技能证书"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || '技能证书'"></span>
                    </h3>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    <div class="inline-edit-list" style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <template x-for="(item, i) in mod.data.items" :key="i">
                            <span class="inline-item-wrap" x-show="item !== undefined" style="font-size: 0.89em; color: var(--body-c); padding: 2px 0;">
                                <span class="inline-edit inline-item" contenteditable="true" data-placeholder="技能"
                                    @input="inlineUpdateItem(modIdx, i, $event.target.innerText)"
                                    @keydown="onItemEnter($event, modIdx, i)"
                                    x-text="(item ? '· ' + item : '')"></span>
                                <span class="inline-item-del" style="position: static; transform: none; margin-left: 3px;" @click="removeInlineItem(modIdx, i)"><i class="ti ti-x"></i></span>
                            </span>
                        </template>
                    </div>
                    <template x-if="(!Array.isArray(mod.data.items) || mod.data.items.filter(i => String(i || '').trim() !== '').length === 0) && mod.data.content">
                        <div style="font-size: 0.96em; color: var(--body-c); white-space: pre-wrap; margin-top: 6px;"
                            class="inline-edit block" contenteditable="true" data-placeholder="请输入技能描述..."
                            @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                            x-text="mod.data.content || ''"></div>
                    </template>
                    <div class="inline-add-btn" @click="addInlineItem(modIdx)"><i class="ti ti-plus"></i>添加技能</div>
                </div>
            </template>

            <template x-if="mod.type === 'summary'">
                <div>
                    <h3 style="margin: 0 0 10px 0; font-size: var(--heading-fs); font-weight: var(--rs-title-weight, 600); color: var(--heading-c); letter-spacing: var(--rs-title-spacing, 0.12em); text-transform: var(--rs-title-transform, uppercase);">
                        <span class="inline-edit" contenteditable="true" data-placeholder="自我评价"
                            @input="inlineUpdate(modIdx, 'title', $event.target.innerText)"
                            x-text="mod.data.title || '自我评价'"></span>
                    </h3>
                    <div style="width: 100%; height: 1px; background: #e5e5e5; margin-bottom: 10px;"></div>
                    <div style="font-size: 1em; color: var(--body-c); white-space: pre-wrap;"
                        class="inline-edit block" contenteditable="true" data-placeholder="请输入内容..."
                        @input="inlineUpdate(modIdx, 'content', $event.target.innerText)"
                        x-text="mod.data.content || ''"></div>
                </div>
            </template>
        </div>
    </template>
</div>
