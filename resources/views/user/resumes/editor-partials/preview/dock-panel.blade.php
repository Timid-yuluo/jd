            <div class="editor-preview-dock-panel" id="preview-dock-panel" x-show="previewDockOpen" x-transition x-cloak>
            {{-- 工具栏 --}}
            <div class="editor-preview-toolbar editor-preview-toolbar-card primary mb-3">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="editor-section-label mb-2">
                            <i class="ti ti-eye"></i>实时预览
                        </div>
                        <h4 class="mb-1">{{ $resume->title }}</h4>
                        <div class="small text-muted">切换模板、主题与排版参数后，右侧预览立即更新，可直接导出 PDF。</div>
                    </div>
                    <div class="editor-preview-toolbar-meta">
                        <span class="badge bg-primary-lt text-primary" x-text="modules.length + ' 个模块'"></span>
                        <span class="badge bg-secondary-lt text-secondary" x-text="wordCount().chars + ' 字'" title="字数统计"></span>
                        <span class="badge" :class="dirty ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success'">
                            <i :class="dirty ? 'ti ti-pencil me-1' : 'ti ti-check me-1'"></i>
                            <span x-text="dirty ? '预览有未保存修改' : '预览与已保存内容一致'"></span>
                        </span>
                        <span class="badge" :class="(isA4Overflow() && !isA4AutoFitApplied()) ? 'bg-danger-lt text-danger' : 'bg-success-lt text-success'" title="A4 单页占用状态">
                            <i :class="(isA4Overflow() && !isA4AutoFitApplied()) ? 'ti ti-alert-triangle me-1' : 'ti ti-check me-1'"></i>
                            <span x-text="a4StatusText()"></span>
                        </span>
                    </div>
                </div>

                <input type="file" id="editor-import-file" accept=".doc,.docx,.pdf,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none"
                    @change="importDocument($event)">

                <div class="editor-preview-flow">
                    <div class="editor-preview-flow-head">
                        <div>
                            <div class="editor-preview-flow-title">编辑闭环</div>
                            <div class="editor-preview-flow-note">先补结构，再做 AI 优化，最后打开结果弹窗核对并应用。</div>
                        </div>
                        <div class="editor-preview-flow-meta">
                            <span class="badge bg-primary-lt text-primary" x-text="`基础项 ${['personal', 'objective', 'experience', 'education'].filter(type => modules.some(m => m.type === type)).length}/4`"></span>
                            <span class="badge bg-success-lt text-success" x-show="optimizedContentForDiff !== ''">已有优化结果</span>
                        </div>
                    </div>
                    <div class="editor-preview-flow-steps">
                        <div class="editor-preview-flow-step"
                            :class="['personal', 'objective', 'experience', 'education'].every(type => modules.some(m => m.type === type)) ? 'is-ready' : 'is-current'">
                            <span class="editor-preview-flow-step-index">1</span>
                            <span>补齐核心模块</span>
                        </div>
                        <div class="editor-preview-flow-step"
                            :class="hasTargetJob() ? (optimizedContentForDiff !== '' ? 'is-ready' : 'is-current') : ''">
                            <span class="editor-preview-flow-step-index">2</span>
                            <span>前往 AI 优化</span>
                        </div>
                        <div class="editor-preview-flow-step"
                            :class="optimizedContentForDiff !== '' ? 'is-ready' : ''">
                            <span class="editor-preview-flow-step-index">3</span>
                            <span>对比并应用结果</span>
                        </div>
                    </div>
                    <div class="editor-preview-flow-actions">
                        <a class="btn btn-outline-primary btn-sm" href="#onboarding-ai-tools-panel">
                            <i class="ti ti-sparkles me-1"></i>前往 AI 面板
                        </a>
                        <button type="button" class="btn btn-outline-success btn-sm" @click="openAiPanelModal('result')" x-show="optimizedContentForDiff !== ''">
                            <i class="ti ti-git-compare me-1"></i>查看优化结果
                        </button>
                        <span class="small text-muted"
                            x-text="['personal', 'objective', 'experience', 'education'].every(type => modules.some(m => m.type === type))
                                ? '基础结构已具备，可以直接转入 AI 优化。'
                                : '建议优先补齐个人信息、求职意向、实习经历、教育经历 4 个核心模块。'"></span>
                    </div>
                </div>

                <div>
                    <div class="editor-preview-tabs">
                        <button type="button" class="editor-preview-tab" :class="{ 'is-active': previewToolTab === 'module' }" @click="previewToolTab = 'module'">添加模块</button>
                        <button type="button" class="editor-preview-tab" :class="{ 'is-active': previewToolTab === 'style' }" @click="previewToolTab = 'style'">样式</button>
                        <button type="button" class="editor-preview-tab" :class="{ 'is-active': previewToolTab === 'io' }" @click="previewToolTab = 'io'">导入导出</button>
                        <button type="button" class="editor-preview-tab" :class="{ 'is-active': previewToolTab === 'preview' }" @click="previewToolTab = 'preview'">预览控制</button>
                    </div>

                    <div class="editor-preview-tab-panel" x-show="previewToolTab === 'module'" x-transition>
                        <div class="editor-preview-toolbar-group">
                            <div class="editor-preview-toolbar-group-label">添加模块</div>
                            <div class="editor-preview-module-group">
                                <div class="editor-preview-module-group-title">基础模块</div>
                                <div class="editor-preview-module-grid">
                                    <button type="button" class="editor-preview-module-add-btn" x-show="!modules.some(m => m.type === 'personal')" @click="addModuleOfType('personal')">
                                        <i class="ti ti-user"></i>
                                        <span>个人信息</span>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" x-show="!modules.some(m => m.type === 'objective')" @click="addModuleOfType('objective')">
                                        <i class="ti ti-briefcase"></i>
                                        <span>求职意向</span>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('experience')">
                                        <i class="ti ti-building"></i>
                                        <span>实习经历</span>
                                        <small x-show="modules.filter(m => m.type === 'experience').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'experience').length + ' 组'"></small>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('education')">
                                        <i class="ti ti-school"></i>
                                        <span>教育经历</span>
                                        <small x-show="modules.filter(m => m.type === 'education').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'education').length + ' 组'"></small>
                                    </button>
                                </div>
                                <div class="editor-preview-module-subgroup" x-show="modules.some(m => m.type === 'personal') || modules.some(m => m.type === 'objective')">
                                    <div class="editor-preview-module-subgroup-title">已添加基础项</div>
                                    <div class="editor-preview-module-grid">
                                        <button type="button" class="editor-preview-module-add-btn is-added" x-show="modules.some(m => m.type === 'personal')" @click="scrollToModule(modules.findIndex(m => m.type === 'personal'), 'preview')">
                                            <i class="ti ti-user"></i>
                                            <span>个人信息</span>
                                            <small>去查看</small>
                                        </button>
                                        <button type="button" class="editor-preview-module-add-btn is-added" x-show="modules.some(m => m.type === 'objective')" @click="scrollToModule(modules.findIndex(m => m.type === 'objective'), 'preview')">
                                            <i class="ti ti-briefcase"></i>
                                            <span>求职意向</span>
                                            <small>去查看</small>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="editor-preview-module-group">
                                <div class="editor-preview-module-group-title">进阶模块</div>
                                <div class="editor-preview-module-grid">
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('project')">
                                        <i class="ti ti-code"></i>
                                        <span>项目经验</span>
                                        <small x-show="modules.filter(m => m.type === 'project').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'project').length + ' 组'"></small>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('skill')">
                                        <i class="ti ti-tools"></i>
                                        <span>技能证书</span>
                                        <small x-show="modules.filter(m => m.type === 'skill').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'skill').length + ' 组'"></small>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('certificate')">
                                        <i class="ti ti-certificate"></i>
                                        <span>获奖情况</span>
                                        <small x-show="modules.filter(m => m.type === 'certificate').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'certificate').length + ' 组'"></small>
                                    </button>
                                    <button type="button" class="editor-preview-module-add-btn" @click="addModuleOfType('summary')">
                                        <i class="ti ti-user-check"></i>
                                        <span>自我评价</span>
                                        <small x-show="modules.filter(m => m.type === 'summary').length > 0" x-text="'已添加 ' + modules.filter(m => m.type === 'summary').length + ' 组'"></small>
                                    </button>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">常用基础模块建议优先补齐，新增后会自动同步到左侧编辑区和右侧简历预览。</div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                                <div class="editor-preview-toolbar-group-label mb-0">模块分组</div>
                                <span class="small text-muted">按当前模块栏位配置展示，可批量调整</span>
                            </div>
                            <div class="editor-preview-layout-groups">
                                <template x-for="group in moduleLayoutGroups()" :key="group.key">
                                    <div class="editor-preview-layout-group-card" x-show="group.items.length > 0">
                                        <div class="editor-preview-layout-group-head"
                                            :class="{ 'is-drop-target': layoutDragIndex !== null }"
                                            @dragover.prevent
                                            @drop.prevent="moveDraggedModuleToLayout(group.key)">
                                            <div>
                                                <strong x-text="group.label"></strong>
                                                <span x-text="group.items.length + ' 个模块'"></span>
                                            </div>
                                            <div class="editor-preview-layout-batch-actions">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    @click="batchSetModuleLayouts(group.items.map(item => item.index), 'left')">左栏</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    @click="batchSetModuleLayouts(group.items.map(item => item.index), 'right')">右栏</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    @click="batchSetModuleLayouts(group.items.map(item => item.index), 'full')">通栏</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    @click="batchSetModuleLayouts(group.items.map(item => item.index), 'auto')">自动</button>
                                            </div>
                                        </div>
                                        <div class="editor-preview-layout-chip-wrap">
                                            <template x-for="item in group.items" :key="item.key">
                                                <button type="button" class="editor-preview-layout-chip"
                                                    :class="{ 'is-dragging': layoutDragIndex === item.index }"
                                                    draggable="true"
                                                    @dragstart="startLayoutDrag(item.index)"
                                                    @dragend="endLayoutDrag()"
                                                    @click="activeIndex = item.index; scrollToModule(item.index, 'preview')">
                                                    <span class="editor-preview-layout-chip-type" x-text="item.label"></span>
                                                    <span class="editor-preview-layout-chip-title" x-text="item.title"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="editor-preview-tab-panel" x-show="previewToolTab === 'style'" x-transition>
                        <div class="editor-preview-toolbar-group">
                            <div class="editor-preview-toolbar-group-label">模板风格</div>
                            <div class="editor-toolbar-button-wrap">
                                <button type="button" class="btn btn-sm" :class="template === 'classic' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'classic'">经典</button>
                                <button type="button" class="btn btn-sm" :class="template === 'modern' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'modern'">现代</button>
                                <button type="button" class="btn btn-sm" :class="template === 'minimal' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'minimal'">极简</button>
                                <button type="button" class="btn btn-sm" :class="template === 'timeline' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'timeline'">时间线</button>
                                <button type="button" class="btn btn-sm" :class="template === 'creative' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'creative'">创意</button>
                                <button type="button" class="btn btn-sm" :class="template === 'elegant' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'elegant'">优雅</button>
                                <button type="button" class="btn btn-sm" :class="template === 'professional' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'professional'">商务</button>
                                <button type="button" class="btn btn-sm" :class="template === 'academic' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'academic'">学术</button>
                                <button type="button" class="btn btn-sm" :class="template === 'internet' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'internet'">互联网</button>
                                <button type="button" class="btn btn-sm" :class="template === 'executive' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'executive'">高管</button>
                                <button type="button" class="btn btn-sm" :class="template === 'startup' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'startup'">创业</button>
                                <button type="button" class="btn btn-sm" :class="template === 'student' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'student'">校园</button>
                                <button type="button" class="btn btn-sm" :class="template === 'medical' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'medical'">医疗</button>
                                <button type="button" class="btn btn-sm" :class="template === 'teacher' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'teacher'">教师</button>
                                <button type="button" class="btn btn-sm" :class="template === 'lawyer' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'lawyer'">法律</button>
                                <button type="button" class="btn btn-sm" :class="template === 'finance' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'finance'">金融</button>
                                <button type="button" class="btn btn-sm" :class="template === 'consulting' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'consulting'">咨询</button>
                                <button type="button" class="btn btn-sm" :class="template === 'designer' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'designer'">设计</button>
                                <button type="button" class="btn btn-sm" :class="template === 'government' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'government'">公职</button>
                                <button type="button" class="btn btn-sm" :class="template === 'freelancer' ? 'btn-primary' : 'btn-outline-secondary'" @click="template = 'freelancer'">自由职业</button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">主题色</div>
                            <div class="editor-toolbar-button-wrap">
                                <button type="button" class="btn btn-sm" :class="theme === 'blue' ? 'btn-primary' : 'btn-outline-secondary'" @click="theme = 'blue'">商务蓝</button>
                                <button type="button" class="btn btn-sm" :class="theme === 'coral' ? 'btn-danger' : 'btn-outline-secondary'" @click="theme = 'coral'">珊瑚红</button>
                                <button type="button" class="btn btn-sm" :class="theme === 'green' ? 'btn-success' : 'btn-outline-secondary'" @click="theme = 'green'">清新绿</button>
                                <button type="button" class="btn btn-sm" :class="theme === 'purple' ? 'btn-purple' : 'btn-outline-secondary'" @click="theme = 'purple'" :style="theme === 'purple' ? 'background-color: #7c3aed; color: #fff; border-color: #7c3aed;' : ''">紫罗兰</button>
                                <button type="button" class="btn btn-sm" :class="theme === 'orange' ? 'btn-orange' : 'btn-outline-secondary'" @click="theme = 'orange'" :style="theme === 'orange' ? 'background-color: #f59e0b; color: #fff; border-color: #f59e0b;' : ''">活力橙</button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">排版预设</div>
                            <div class="editor-preview-preset-grid">
                                <button type="button" class="editor-preview-preset-card" @click="applyTypographyPreset('compact')">
                                    <strong>紧凑</strong>
                                    <span>更适合一页内容较多的简历</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyTypographyPreset('balanced')">
                                    <strong>均衡</strong>
                                    <span>通用默认，兼顾密度和可读性</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyTypographyPreset('spacious')">
                                    <strong>舒展</strong>
                                    <span>更突出层次，适合内容较精简</span>
                                </button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">字体方案</div>
                            <div class="editor-preview-preset-grid">
                                <button type="button" class="editor-preview-preset-card" @click="applyFontPreset('system')">
                                    <strong>系统黑体</strong>
                                    <span>清晰稳重，默认推荐</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyFontPreset('serif')">
                                    <strong>衬线风格</strong>
                                    <span>更正式，适合研究和设计方向</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyFontPreset('yahei')">
                                    <strong>微软雅黑</strong>
                                    <span>中文显示更统一</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyFontPreset('elegant')">
                                    <strong>英文优雅</strong>
                                    <span>适合英文标题较多的版式</span>
                                </button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">颜色预设</div>
                            <div class="editor-preview-color-preset-grid">
                                <button type="button" class="editor-preview-color-preset" @click="applyColorPreset('default')">
                                    <span class="editor-preview-color-preset-swatch" style="--preset-a:#2563eb; --preset-b:#1f2937; --preset-c:#475569;"></span>
                                    <span>跟随主题</span>
                                </button>
                                <button type="button" class="editor-preview-color-preset" @click="applyColorPreset('business')">
                                    <span class="editor-preview-color-preset-swatch" style="--preset-a:#2563eb; --preset-b:#0f172a; --preset-c:#334155;"></span>
                                    <span>商务蓝灰</span>
                                </button>
                                <button type="button" class="editor-preview-color-preset" @click="applyColorPreset('calm')">
                                    <span class="editor-preview-color-preset-swatch" style="--preset-a:#0f766e; --preset-b:#1f2937; --preset-c:#475569;"></span>
                                    <span>沉稳青绿</span>
                                </button>
                                <button type="button" class="editor-preview-color-preset" @click="applyColorPreset('warm')">
                                    <span class="editor-preview-color-preset-swatch" style="--preset-a:#ea580c; --preset-b:#7c2d12; --preset-c:#4b5563;"></span>
                                    <span>暖色表达</span>
                                </button>
                                <button type="button" class="editor-preview-color-preset" @click="applyColorPreset('elegant')">
                                    <span class="editor-preview-color-preset-swatch" style="--preset-a:#7c3aed; --preset-b:#312e81; --preset-c:#374151;"></span>
                                    <span>优雅紫调</span>
                                </button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">标题规范</div>
                            <div class="editor-preview-preset-grid">
                                <button type="button" class="editor-preview-preset-card" @click="applyTitleStyleVariant('professional')">
                                    <strong>专业标准</strong>
                                    <span>统一标题粗细、分隔线和阅读层级</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyTitleStyleVariant('minimal')">
                                    <strong>极简大写</strong>
                                    <span>更强调留白、字距和标题秩序</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyTitleStyleVariant('executive')">
                                    <strong>高级感</strong>
                                    <span>适合社招与偏成熟的职业风格</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyTitleStyleVariant('compact')">
                                    <strong>紧凑高效</strong>
                                    <span>标题占位更小，适合一页纸简历</span>
                                </button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="editor-preview-toolbar-group-label">一键排版方案</div>
                            <div class="editor-preview-preset-grid">
                                <button type="button" class="editor-preview-preset-card" @click="applyResumeProfile('campus')">
                                    <strong>适合校招</strong>
                                    <span>现代双栏，突出教育、项目与基础能力</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyResumeProfile('experienced')">
                                    <strong>适合社招</strong>
                                    <span>优雅双栏，突出经历深度和专业沉稳感</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyResumeProfile('onepage')">
                                    <strong>一页纸</strong>
                                    <span>压缩间距与标题占位，适合控制页数</span>
                                </button>
                            </div>
                        </div>
                        <div class="editor-preview-toolbar-group mt-2">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div class="editor-preview-toolbar-group-label mb-0">栏位占比</div>
                                <span class="editor-preview-scale-pill" x-text="clampedLeftColumnRatio() + ' / ' + rightColumnRatio()"></span>
                            </div>
                            <div class="editor-preview-control-card">
                                <label class="form-label mb-1">左栏宽度</label>
                                <div class="editor-preview-inline-range">
                                    <input type="range" class="form-range" min="35" max="65" step="1"
                                        x-model="leftColumnRatio" @input="layoutProfile = 'custom'; dirty = true">
                                    <span class="text-muted" x-text="clampedLeftColumnRatio() + '%'"></span>
                                </div>
                            </div>
                            <div class="editor-preview-preset-grid mt-2">
                                <button type="button" class="editor-preview-preset-card" @click="applyColumnRatioPreset(50)">
                                    <strong>50 / 50</strong>
                                    <span>左右均衡，适合通用双栏排版</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyColumnRatioPreset(60)">
                                    <strong>60 / 40</strong>
                                    <span>突出左栏主经历区域</span>
                                </button>
                                <button type="button" class="editor-preview-preset-card" @click="applyColumnRatioPreset(38)">
                                    <strong>38 / 62</strong>
                                    <span>突出右栏主内容，适合现代侧栏结构</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="editor-preview-tab-panel" x-show="previewToolTab === 'io'" x-transition>
                        <div class="editor-preview-toolbar-group">
                            <div class="editor-preview-toolbar-group-label">导入导出</div>
                            <div class="editor-toolbar-button-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="document.getElementById('editor-import-file').click()" :disabled="importing">
                                    <i class="ti ti-upload me-1" x-show="!importing"></i>
                                    <span class="spinner-border spinner-border-sm me-1" role="status" x-show="importing"></span>
                                    <span x-text="importing ? '导入中...' : '导入简历'"></span>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" @click="exportPdf()">
                                    <i class="ti ti-file-type-pdf me-1"></i>导出 PDF
                                </button>
                            </div>
                            <div class="small text-muted mt-2">支持导入 `DOC / DOCX / PDF`，适合从已有简历快速初始化内容。</div>
                        </div>
                    </div>

                    <div class="editor-preview-tab-panel" x-show="previewToolTab === 'preview'" x-transition>
                        <div class="editor-preview-toolbar-group">
                            <div class="editor-preview-toolbar-group-label">预览控制</div>
                            <div class="editor-toolbar-button-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="zoomOutPreview()" :disabled="previewScale <= 70">
                                    <i class="ti ti-zoom-out me-1"></i>缩小
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="zoomInPreview()" :disabled="previewScale >= 130">
                                    <i class="ti ti-zoom-in me-1"></i>放大
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="resetPreviewZoom()">
                                    <i class="ti ti-focus-centered me-1"></i>重置
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="togglePreviewFullscreen()">
                                    <i :class="isPreviewFullscreen ? 'ti ti-minimize me-1' : 'ti ti-maximize me-1'"></i>
                                    <span x-text="isPreviewFullscreen ? '退出全屏' : '全屏预览'"></span>
                                </button>
                                <button type="button" class="btn btn-sm" :class="keywordHighlightEnabled ? 'btn-warning' : 'btn-outline-secondary'" @click="keywordHighlightEnabled = !keywordHighlightEnabled" title="在预览中高亮显示目标岗位关键词">
                                    <i class="ti ti-highlight me-1"></i>关键词高亮
                                </button>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                                <span class="small text-muted">当前缩放比例</span>
                                <span class="editor-preview-scale-pill" x-text="previewScale + '%'"></span>
                            </div>
                            <div class="editor-preview-a4-status" :class="(isA4Overflow() && !isA4AutoFitApplied()) ? 'is-overflow' : 'is-fit'">
                                <i :class="(isA4Overflow() && !isA4AutoFitApplied()) ? 'ti ti-alert-triangle' : 'ti ti-file-check'"></i>
                                <span x-text="a4StatusText()"></span>
                                <label class="editor-preview-a4-toggle mb-0">
                                    <input type="checkbox" x-model="a4AutoFitEnabled">
                                    <span>自动适配一页A4（仅轻微超出时）</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-primary" x-show="isA4Overflow() && !isA4AutoFitApplied()" @click="applyOnePagePresetAndMeasure()">
                                    一键压缩到一页
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" x-show="a4AutoFitEnabled && a4AutoFitSkippedForLongContent" @click="applyLongContentReadableLayout()">
                                    切换长内容多页排版
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 排版工具栏 --}}
            <div class="editor-preview-toolbar editor-preview-toolbar-card secondary mb-3" style="font-size: 12px;" x-show="previewToolTab === 'style'" x-transition>
                <div class="editor-section-label mb-3">
                    <i class="ti ti-typography"></i>排版调节
                </div>
                <div class="editor-preview-typography-grid">
                    <div class="editor-preview-control-card">
                        <label class="form-label mb-1">字体</label>
                        <select class="form-select form-select-sm" x-model="fontFamily" @change="dirty = true">
                            <option value="'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif">默认（系统黑体）</option>
                            <option value="'Noto Serif SC','SimSun','STSong','宋体',serif">宋体 / 衬线</option>
                            <option value="'Microsoft YaHei','微软雅黑',sans-serif">微软雅黑</option>
                            <option value="'PingFang SC','苹方',sans-serif">苹方</option>
                            <option value="Arial,'Helvetica Neue',sans-serif">Arial</option>
                            <option value="Georgia,'Times New Roman',serif">Georgia</option>
                        </select>
                    </div>
                    <div class="editor-preview-control-card">
                        <label class="form-label mb-1">正文字号</label>
                        <div class="editor-preview-inline-range">
                            <input type="range" class="form-range" min="11" max="16" step="0.5"
                                x-model="fontSize" @input="dirty = true">
                            <span class="text-muted" x-text="fontSize + 'px'"></span>
                        </div>
                    </div>
                    <div class="editor-preview-control-card">
                        <label class="form-label mb-1">正文行距</label>
                        <div class="editor-preview-inline-range">
                            <input type="range" class="form-range" min="1.3" max="2.2" step="0.1"
                                x-model="lineHeight" @input="dirty = true">
                            <span class="text-muted" x-text="lineHeight"></span>
                        </div>
                    </div>
                    <div class="editor-preview-control-card">
                        <label class="form-label mb-1">标题字号</label>
                        <div class="editor-preview-inline-range">
                            <input type="range" class="form-range" min="0.8" max="1.5" step="0.05"
                                x-model="headingFontSize" @input="dirty = true">
                            <span class="text-muted" x-text="headingFontSize + 'em'"></span>
                        </div>
                    </div>
                    <div class="editor-preview-control-card">
                        <label class="form-label mb-1">模块间距</label>
                        <div class="editor-preview-inline-range">
                            <input type="range" class="form-range" min="8" max="32" step="2"
                                x-model="sectionSpacing" @input="dirty = true">
                            <span class="text-muted" x-text="sectionSpacing + 'px'"></span>
                        </div>
                    </div>
                </div>

                <div class="editor-preview-color-control-grid mt-3">
                    <div class="editor-preview-color-control">
                        <label class="form-label mb-1">正文颜色</label>
                        <div class="editor-preview-inline-color">
                            <input type="color" class="form-control form-control-color"
                                :value="bodyFontColor || '#1f2937'"
                                @input="bodyFontColor = $event.target.value; dirty = true">
                            <span class="text-muted" x-text="bodyFontColor || '默认'"></span>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0"
                                x-show="bodyFontColor" @click="bodyFontColor = ''; dirty = true" title="重置">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="editor-preview-color-control">
                        <label class="form-label mb-1">标题颜色</label>
                        <div class="editor-preview-inline-color">
                            <input type="color" class="form-control form-control-color"
                                :value="headingColor || '#1f2937'"
                                @input="headingColor = $event.target.value; dirty = true">
                            <span class="text-muted" x-text="headingColor || '默认'"></span>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0"
                                x-show="headingColor" @click="headingColor = ''; dirty = true" title="重置">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="editor-preview-color-control">
                        <label class="form-label mb-1">强调颜色</label>
                        <div class="editor-preview-inline-color">
                            <input type="color" class="form-control form-control-color"
                                :value="accentColor || '#2563eb'"
                                @input="accentColor = $event.target.value; dirty = true">
                            <span class="text-muted" x-text="accentColor || '默认'"></span>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0"
                                x-show="accentColor" @click="accentColor = ''; dirty = true" title="重置">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
                    <button type="button" class="btn btn-ghost-secondary btn-sm" @click="resetFont()" title="恢复默认排版">
                        <i class="ti ti-refresh me-1"></i>恢复默认
                    </button>
                    <span class="text-muted small" x-show="headingColor || accentColor || bodyFontColor" style="font-size: 10px;">
                        自定义颜色会覆盖主题色，点击 × 可重置
                    </span>
                </div>
            </div>
            </div>
