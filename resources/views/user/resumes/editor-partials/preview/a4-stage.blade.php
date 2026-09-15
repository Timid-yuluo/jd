        {{-- A4 预览 --}}
        <div class="editor-preview-scroll" :class="{ 'is-fullscreen': isPreviewFullscreen }">
            <div class="editor-preview-stage">
                <div class="editor-preview-sheet-frame" :style="previewStageStyle()">
                <div id="resume-preview" class="bg-white shadow-sm editor-preview-sheet"
                     @click="handlePreviewModuleClick($event)"
                     @mousemove="handlePreviewModuleHover($event)"
                     @mouseleave="handlePreviewModuleLeave()"
                     :class="['theme-' + theme, { 'is-a4-autofit': isA4AutoFitApplied() }]"
                     :style="`
                        --rs-font-size: ${fontSize}px;
                        --rs-line-height: ${lineHeight};
                        --rs-font-family: ${fontFamily};
                        --rs-heading-font-size: ${headingFontSize}em;
                        --rs-heading-color: ${headingColor || 'var(--primary-color, #1f2937)'};
                        --rs-accent-color: ${accentColor || 'var(--accent-color, #2563eb)'};
                        ${bodyFontColor ? '--rs-body-color: ' + bodyFontColor + ';' : ''}
                        --rs-section-spacing: ${sectionSpacing}px;
                        ${titleStyleCssVars()}
                        ${previewA4FitStyle()}
                        font-family: ${fontFamily};
                     `">
                    <template x-if="template === 'classic'">
                        <div>@include('user.resumes.templates.visual-classic')</div>
                    </template>
                    <template x-if="template === 'modern'">
                        <div>@include('user.resumes.templates.visual-modern')</div>
                    </template>
                    <template x-if="template === 'minimal'">
                        <div>@include('user.resumes.templates.visual-minimal')</div>
                    </template>
                    <template x-if="template === 'timeline'">
                        <div>@include('user.resumes.templates.visual-timeline')</div>
                    </template>
                    <template x-if="template === 'creative'">
                        <div>@include('user.resumes.templates.visual-creative')</div>
                    </template>
                    <template x-if="template === 'elegant'">
                        <div>@include('user.resumes.templates.visual-elegant')</div>
                    </template>
                    <template x-if="template === 'professional'">
                        <div>@include('user.resumes.templates.visual-professional')</div>
                    </template>
                    <template x-if="template === 'academic'">
                        <div>@include('user.resumes.templates.visual-academic')</div>
                    </template>
                    <template x-if="template === 'internet'">
                        <div>@include('user.resumes.templates.visual-internet')</div>
                    </template>
                    <template x-if="template === 'executive'">
                        <div>@include('user.resumes.templates.visual-executive')</div>
                    </template>
                    <template x-if="template === 'startup'">
                        <div>@include('user.resumes.templates.visual-startup')</div>
                    </template>
                    <template x-if="template === 'student'">
                        <div>@include('user.resumes.templates.visual-student')</div>
                    </template>
                    <template x-if="template === 'medical'">
                        <div>@include('user.resumes.templates.visual-medical')</div>
                    </template>
                    <template x-if="template === 'teacher'">
                        <div>@include('user.resumes.templates.visual-teacher')</div>
                    </template>
                    <template x-if="template === 'lawyer'">
                        <div>@include('user.resumes.templates.visual-lawyer')</div>
                    </template>
                    <template x-if="template === 'finance'">
                        <div>@include('user.resumes.templates.visual-finance')</div>
                    </template>
                    <template x-if="template === 'consulting'">
                        <div>@include('user.resumes.templates.visual-consulting')</div>
                    </template>
                    <template x-if="template === 'designer'">
                        <div>@include('user.resumes.templates.visual-designer')</div>
                    </template>
                    <template x-if="template === 'government'">
                        <div>@include('user.resumes.templates.visual-government')</div>
                    </template>
                    <template x-if="template === 'freelancer'">
                        <div>@include('user.resumes.templates.visual-freelancer')</div>
                    </template>
                    <div
                        class="editor-preview-module-action"
                        x-show="previewActionVisible && !detailOpen"
                        x-cloak
                        :class="{ 'is-muted': previewActionMuted }"
                        :style="previewActionStyle"
                        @click.stop
                    >
                        <template x-if="modules[activeIndex]">
                            <div class="editor-preview-module-toolbar">
                                <div class="editor-preview-module-toolbar-meta">
                                    <span class="editor-preview-module-toolbar-state">
                                        <span class="badge editor-module-state-badge"
                                            :class="moduleCompletionBadgeClass(modules[activeIndex])"
                                            x-text="moduleCompletionLabel(modules[activeIndex])"></span>
                                        <span class="editor-preview-module-toolbar-score" x-text="moduleCompletionPercent(modules[activeIndex]) + '%'"></span>
                                    </span>
                                </div>
                                <div class="editor-preview-module-toolbar-actions">
                                    <button type="button" class="editor-preview-module-toolbar-btn is-primary" title="详细设置" @click.stop="openModuleDetail(activeIndex)">
                                        <i class="ti ti-adjustments-horizontal"></i>
                                    </button>
                                    <button type="button" class="editor-preview-module-toolbar-btn" title="AI 优化此模块" @click.stop="optimizeSingleModule(activeIndex)" :disabled="isAnyAiActionRunning()">
                                        <i class="ti ti-sparkles"></i>
                                    </button>
                                    <button type="button" class="editor-preview-module-toolbar-btn" @click.stop="moveUp(activeIndex)" :disabled="activeIndex === 0" title="上移">
                                        <i class="ti ti-arrow-up"></i>
                                    </button>
                                    <button type="button" class="editor-preview-module-toolbar-btn" @click.stop="moveDown(activeIndex)" :disabled="activeIndex === modules.length - 1" title="下移">
                                        <i class="ti ti-arrow-down"></i>
                                    </button>
                                    <button type="button" class="editor-preview-module-toolbar-btn" @click.stop="duplicateModule(activeIndex)" title="复制模块">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                    <button type="button" class="editor-preview-module-toolbar-btn is-danger" @click.stop="removeModule(activeIndex)" title="删除模块">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                </div>
            </div>
        </div>
