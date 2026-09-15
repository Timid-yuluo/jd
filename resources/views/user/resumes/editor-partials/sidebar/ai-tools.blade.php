<div id="onboarding-ai-tools-panel" class="editor-ai-workbench">
    <div class="editor-ai-flat-head">
        <div class="editor-ai-hero-head">
            <div class="editor-ai-hero-title">
                <i class="ti ti-sparkles text-primary"></i>
                <div>
                    <div class="editor-ai-hero-name">先设定岗位，再执行优化</div>
                    <div class="editor-ai-hero-note">ATS、关键词分析和优化结果已改为弹窗查看，当前区域仅保留主流程与真实优先策略配置。</div>
                </div>
            </div>
            <div class="editor-ai-hero-status">
                <span class="badge" x-show="hasOptimizationResult()"
                    :class="isOptimizeStale() ? 'bg-warning-lt text-warning' : 'bg-success-lt'"
                    x-text="isOptimizeStale() ? '优化结果待刷新' : '已优化'"></span>
                <span class="badge" x-show="resumeAtsScore !== null"
                    :class="isAtsStale() ? 'bg-warning-lt text-warning' : (resumeAtsScore >= 80 ? 'bg-success-lt' : (resumeAtsScore >= 60 ? 'bg-warning-lt' : 'bg-danger-lt'))">
                    <span x-text="isAtsStale() ? 'ATS 待刷新' : ('ATS ' + resumeAtsScore)"></span>
                </span>
                <span class="badge bg-primary-lt text-primary" x-show="selectedJdKeywords.length" x-text="`关键词 ${selectedJdKeywords.length}`"></span>
                <span class="badge bg-success-lt text-success" x-show="Array.isArray(optimizeGoals) && optimizeGoals.length" x-text="`方向 ${optimizeGoals.length}`"></span>
                <span class="badge bg-info-lt text-info" x-show="isOptimizeGoalSelected('authenticity')">真实优先</span>
                <span class="badge bg-secondary-lt text-secondary" x-show="currentAiDriver" x-text="`模型 ${normalizeAiDriverLabel(currentAiDriver)}`"></span>
                <span class="badge bg-warning-lt text-warning" x-show="fallbackFromDriver && currentAiDriver" x-text="`已降级 ${normalizeAiDriverLabel(fallbackFromDriver)} → ${normalizeAiDriverLabel(currentAiDriver)}`"></span>
            </div>
        </div>

        <div class="editor-ai-quickbar">
            <input id="onboarding-target-job-input" type="text" class="form-control form-control-sm"
                :class="hasTargetJob() ? 'border-primary-subtle' : 'border-warning-subtle'"
                x-model="streamTargetJob" placeholder="目标岗位（例如：Laravel 后端开发）" style="min-width: 0;">
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" @click="streamOptimize()" :disabled="isAnyAiActionRunning() || !hasTargetJob()">
                <span x-show="streamStatus !== 'running'"><i class="ti ti-sparkles me-1"></i><span x-text="optimizeButtonText()"></span></span>
                <span x-show="streamStatus === 'running'" class="spinner-border spinner-border-sm"></span>
            </button>
            <button type="button" class="btn btn-outline-info btn-sm flex-shrink-0" @click="doAtsScore()" :disabled="isAnyAiActionRunning()" title="ATS评分">
                <span x-show="!atsScoring"><i class="ti ti-star me-1"></i>ATS</span>
                <span x-show="atsScoring" class="spinner-border spinner-border-sm"></span>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" @click="openOptimizeHistoryModal()" title="优化历史版本">
                <i class="ti ti-history me-1"></i>历史版本
            </button>
        </div>

        <div class="editor-ai-quickhint">
            <span x-text="targetJobHintText()"></span>
            <span x-show="isAnyAiActionRunning()"><i class="ti ti-loader-2 ti-spin me-1"></i>AI 正在处理中</span>
        </div>

        <div x-show="streamStatus === 'error' && lastAiErrorMessage" class="editor-ai-refresh-banner">
            <div class="small">
                <i class="ti ti-alert-triangle me-1 text-danger"></i>
                <span x-text="lastAiErrorMessage"></span>
            </div>
            <div class="small text-muted mt-1" x-show="Array.isArray(lastAiAttemptedDrivers) && lastAiAttemptedDrivers.length > 0"
                x-text="`已尝试：${lastAiAttemptedDrivers.map((driver) => normalizeAiDriverLabel(driver)).join(' -> ')}`"></div>
        </div>

        <div class="editor-ai-inline-tip">
            <i class="ti ti-bulb text-warning"></i>
            <span
                x-text="!hasTargetJob()
                    ? '先填写目标岗位，再启动优化或 ATS 评分。'
                    : resumeAtsScore !== null && resumeAtsScore < 70
                        ? 'ATS 分数偏低（' + resumeAtsScore + '分），建议先优化简历再投递，可提升 40% 面试机会。'
                        : !(streamTargetJobDescription || '').trim()
                            ? '建议补充岗位描述 / JD，再提取关键词，优化结果会更贴近目标岗位。'
                            : !selectedJdKeywords.length
                                ? '下一步建议提取并勾选 JD 关键词，便于 AI 聚焦命中项。'
                                : optimizedContentForDiff === ''
                                    ? '策略与方向已具备，可以开始生成优化结果。'
                                    : '已有优化结果，建议先查看差异，再决定是否回写到当前简历。'"></span>
        </div>

        <div class="editor-ai-roadmap">
            <div class="editor-ai-roadmap-item" :class="hasTargetJob() ? 'is-ready' : 'is-current'">
                <span>设定岗位</span>
            </div>
            <div class="editor-ai-roadmap-item"
                :class="(streamTargetJobDescription || '').trim() ? 'is-ready' : (hasTargetJob() ? 'is-current' : '')">
                <span>补充 JD</span>
            </div>
            <div class="editor-ai-roadmap-item"
                :class="selectedJdKeywords.length ? 'is-ready' : (((streamTargetJobDescription || '').trim()) ? 'is-current' : '')">
                <span>提取关键词</span>
            </div>
            <div class="editor-ai-roadmap-item"
                :class="(optimizedContentForDiff !== '' || resumeAtsScore !== null) ? 'is-ready' : ((selectedJdKeywords.length || hasTargetJob()) ? 'is-current' : '')">
                <span>生成并校验</span>
            </div>
        </div>
    </div>

    <div class="editor-ai-jd-section mb-2">
        <div class="small fw-medium mb-1 d-flex justify-content-between align-items-center">
            <span><i class="ti ti-file-text me-1 text-primary"></i>岗位描述 / JD</span>
            <span class="text-muted" style="font-size:10px;">粘贴 JD 后自动解析关键词</span>
        </div>
        <textarea class="form-control form-control-sm" rows="3"
            x-model="streamTargetJobDescription"
            @input.debounce.2s="parseJdStructure()"
            @paste.debounce.1s="parseJdStructure()"
            placeholder="粘贴岗位描述 / JD，AI 将自动提取关键词并推荐优化方向..."></textarea>
        <div class="d-flex gap-2 mt-1">
            <input type="text" class="form-control form-control-sm" x-model="streamTargetCompany" placeholder="目标公司（可选）" style="flex:1;">
            <input type="text" class="form-control form-control-sm" x-model="streamTargetJobTitle" placeholder="岗位名称（可选）" style="flex:1;">
        </div>
        <template x-if="jdParseResult">
            <div class="border rounded p-2 mt-2" style="font-size:11px;background:var(--tblr-bg-surface);">
                <div class="fw-medium mb-1 d-flex justify-content-between align-items-center" style="font-size:12px;">
                    <span><i class="ti ti-sparkles me-1 text-primary"></i>JD 解析结果</span>
                    <button type="button" class="btn btn-outline-success btn-sm py-0" style="font-size:10px;" @click="autoRecommendOptimizeGoalsFromJd()" :disabled="isAnyAiActionRunning()">
                        <i class="ti ti-bulb me-1"></i>自动推荐优化方向
                    </button>
                </div>
                <template x-if="jdParseResult.hard_skills?.length">
                    <div class="mb-1">
                        <span class="text-muted">硬技能：</span>
                        <template x-for="s in jdParseResult.hard_skills" :key="s">
                            <span class="badge bg-primary-lt text-primary me-1 mb-1" x-text="s" style="font-size:10px;"></span>
                        </template>
                    </div>
                </template>
                <template x-if="jdParseResult.soft_skills?.length">
                    <div class="mb-1">
                        <span class="text-muted">软技能：</span>
                        <template x-for="s in jdParseResult.soft_skills" :key="s">
                            <span class="badge bg-info-lt text-info me-1 mb-1" x-text="s" style="font-size:10px;"></span>
                        </template>
                    </div>
                </template>
                <div class="d-flex gap-3 text-muted">
                    <span x-show="jdParseResult.experience_years">经验：<strong x-text="jdParseResult.experience_years + '年+'"></strong></span>
                    <span x-show="jdParseResult.education">学历：<strong x-text="jdParseResult.education"></strong></span>
                    <span x-show="jdParseResult.salary">薪资：<strong x-text="jdParseResult.salary"></strong></span>
                </div>
            </div>
        </template>
    </div>

    <template x-if="streamTargetJob && streamTargetJob.trim().length >= 2 && modules.length > 0">
        <div class="border rounded p-2 mb-2" style="font-size:11px;background:var(--tblr-bg-surface);">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-medium" style="font-size:12px;"><i class="ti ti-target-arrow me-1 text-primary"></i>匹配度预览</span>
                <span class="badge" :class="matchHeatmapData().overall >= 70 ? 'bg-success-lt text-success' : (matchHeatmapData().overall >= 40 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger')" x-text="matchHeatmapData().overall + '%'"></span>
            </div>
            <div class="mb-2">
                <template x-for="dim in matchHeatmapData().dimensions" :key="dim.key">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i :class="dim.icon" class="text-muted" style="font-size:12px;width:14px;"></i>
                        <span class="text-muted text-truncate" style="width:70px;font-size:10px;" x-text="dim.label"></span>
                        <div class="flex-fill progress" style="height:4px;">
                            <div class="progress-bar" :class="dim.score >= 70 ? 'bg-success' : (dim.score >= 40 ? 'bg-warning' : 'bg-danger')" :style="`width:${dim.score}%`"></div>
                        </div>
                        <span style="font-size:10px;width:28px;text-align:right;" :class="dim.score >= 70 ? 'text-success' : (dim.score >= 40 ? 'text-warning' : 'text-danger')" x-text="dim.score + '%'"></span>
                    </div>
                </template>
            </div>
            <template x-if="jdParseResult">
                <div class="mb-2">
                    <template x-if="jdParseResult.hard_skills?.length">
                        <div class="mb-1">
                            <span class="text-muted" style="font-size:10px;">硬技能：</span>
                            <template x-for="kw in jdParseResult.hard_skills" :key="'hs-'+kw">
                                <span class="badge" style="font-size:9px;" :class="isKeywordCovered(kw) ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'" x-text="kw"></span>
                            </template>
                        </div>
                    </template>
                    <template x-if="jdParseResult.soft_skills?.length">
                        <div class="mb-1">
                            <span class="text-muted" style="font-size:10px;">软技能：</span>
                            <template x-for="kw in jdParseResult.soft_skills" :key="'ss-'+kw">
                                <span class="badge" style="font-size:9px;" :class="isKeywordCovered(kw) ? 'bg-info-lt text-info' : 'bg-warning-lt text-warning'" x-text="kw"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
            <template x-for="m in matchHeatmapData().modules" :key="m.index">
                <div class="d-flex align-items-center gap-2 mb-1" @click="activeIndex = m.index" style="cursor:pointer;">
                    <span class="text-muted text-truncate" style="width:60px;font-size:10px;" x-text="m.label"></span>
                    <div class="flex-fill progress" style="height:6px;">
                        <div class="progress-bar" :class="m.level === 'high' ? 'bg-success' : (m.level === 'medium' ? 'bg-warning' : 'bg-danger')" :style="`width:${m.score}%`"></div>
                    </div>
                    <span style="font-size:10px;width:28px;text-align:right;" :class="m.level === 'high' ? 'text-success' : (m.level === 'medium' ? 'text-warning' : 'text-danger')" x-text="m.score + '%'"></span>
                </div>
            </template>
            <template x-if="matchHeatmapData().modules.some(m => m.missKeywords?.length > 0)">
                <div class="mt-1 pt-1 border-top">
                    <div class="text-muted" style="font-size:10px;">未覆盖关键词：</div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <template x-for="m in matchHeatmapData().modules" :key="'miss-'+m.index">
                            <template x-for="kw in (m.missKeywords || [])" :key="kw">
                                <span class="badge bg-danger-lt text-danger" style="font-size:9px;cursor:pointer;" x-text="kw" @click="activeIndex = m.index" title="点击跳转到对应模块"></span>
                            </template>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <div x-show="showResultRefreshHint()" class="editor-ai-refresh-banner">
        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
            <div class="small">
                <i class="ti ti-alert-circle me-1"></i>
                <span x-text="resultRefreshHintText()"></span>
            </div>
            <div class="d-flex gap-2" x-show="showResultRefreshActions()">
                <button type="button" class="btn btn-outline-info btn-sm"
                    x-show="isAtsStale()"
                    @click="doAtsScore()"
                    :disabled="isAnyAiActionRunning()">
                    <i class="ti ti-star me-1"></i>重新 ATS
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm"
                    x-show="isOptimizeStale()"
                    @click="streamOptimize()"
                    :disabled="isAnyAiActionRunning() || !hasTargetJob()">
                    <i class="ti ti-sparkles me-1"></i>重新优化
                </button>
            </div>
        </div>
    </div>

    <div class="editor-ai-jumpbar">
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="openAiPanelModal('analysis')">
            <i class="ti ti-search me-1"></i>关键词分析
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="openAiPanelModal('suggestion')">
            <i class="ti ti-list-check me-1"></i>建议评分
        </button>
        <a class="btn btn-outline-secondary btn-sm" href="#ai-strategy-zone">
            <i class="ti ti-adjustments me-1"></i>策略模式
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="openAiPanelModal('result')">
            <i class="ti ti-git-compare me-1"></i>优化结果
        </button>
    </div>

    <section id="ai-strategy-zone" class="editor-ai-section">
        <div class="editor-ai-section-head">
            <div>
                <div class="editor-ai-section-kicker">配置</div>
                <div class="editor-ai-section-title">策略包与优化模式</div>
            </div>
            <div class="editor-ai-section-side small text-muted" x-text="`${(focusKeywordsForOptimize() || []).length} 个关键词`"></div>
        </div>

        <div class="editor-ai-card mb-2">
            @if($resume->careerTrack)
                <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background: {{ $resume->careerTrack->color ?? '#206bc4' }}10; border: 1px solid {{ $resume->careerTrack->color ?? '#206bc4' }}30;">
                    @if($resume->careerTrack->icon)<i class="ti {{ $resume->careerTrack->icon }}" style="color: {{ $resume->careerTrack->color ?? '#206bc4' }};"></i>@endif
                    <div class="flex-grow-1">
                        <span class="small fw-medium" style="color: {{ $resume->careerTrack->color ?? '#206bc4' }};">{{ $resume->careerTrack->name }}</span>
                        <span class="small text-muted ms-1">赛道专属策略</span>
                    </div>
                    <a href="{{ route('user.resumes.show', $resume) }}#trackModal" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size:10px;" data-bs-toggle="modal" data-bs-target="#trackModal">更换</a>
                </div>
            @else
                <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded bg-light-subtle">
                    <i class="ti ti-compass text-secondary"></i>
                    <span class="small text-muted flex-grow-1">未选择赛道，建议选择以获得专属优化策略</span>
                    <a href="{{ route('user.resumes.show', $resume) }}#trackModal" class="btn btn-sm btn-outline-primary py-0 px-1" style="font-size:10px;" data-bs-toggle="modal" data-bs-target="#trackModal">选择</a>
                </div>
            @endif
            <div class="small fw-medium mb-2 d-flex justify-content-between align-items-center">
                <span>提示词策略包</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="autoDetectPromptStrategyTemplate()" :disabled="isAnyAiActionRunning()">自动识别</button>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
                <template x-for="cat in strategyCategoryOptions()" :key="cat.key">
                    <button type="button" class="btn btn-sm"
                        :class="_strategyCategoryFilter === cat.key ? 'btn-dark' : 'btn-outline-dark'"
                        style="font-size:10px;"
                        @click="_strategyCategoryFilter = cat.key"
                        x-text="cat.label"></button>
                </template>
            </div>
            <div class="d-flex flex-wrap gap-1" style="max-height: 132px; overflow-y: auto;">
                <template x-for="tpl in promptStrategyTemplateOptions()" :key="tpl.key">
                    <button type="button" class="btn btn-sm"
                        :class="[
                            promptStrategyTemplate === tpl.key ? 'btn-primary' : 'btn-outline-primary',
                            tpl.locked ? 'disabled opacity-50' : ''
                        ]"
                        @click="tpl.locked ? notifyAdvancedFeatureLocked(`策略包「${tpl.title}」`) : applyPromptStrategyTemplate(tpl.key)"
                        :title="tpl.locked ? lockedFeatureHint(`策略包「${tpl.title}」`) : tpl.title"
                        :aria-disabled="tpl.locked ? 'true' : 'false'"
                        x-text="tpl.title"></button>
                </template>
            </div>
            <div class="small text-muted mt-2 d-flex justify-content-between">
                <span>当前关键词</span>
                <span x-text="`${(focusKeywordsForOptimize() || []).length} 个`"></span>
            </div>
            <div class="small text-muted" x-text="(focusKeywordsForOptimize() || []).slice(0, 5).join(' / ') || '暂无关键词，建议先提取 JD 关键词'"></div>
            <div class="border rounded p-2 mt-2 bg-light-subtle" x-show="currentPromptStrategyMeta()">
                <div class="small fw-medium mb-1">策略包适用说明</div>
                <div class="small text-muted" x-text="currentPromptStrategyMeta()?.applicable || '适用于多数常规投递场景'"></div>
                <div class="small text-muted" x-show="currentPromptStrategyMeta()?.notApplicable" x-text="`不适用：${currentPromptStrategyMeta()?.notApplicable}`"></div>
                <div class="small text-warning" x-show="currentPromptStrategyMeta()?.riskTip" x-text="`风险提示：${currentPromptStrategyMeta()?.riskTip}`"></div>
            </div>
            <div class="border rounded p-2 mt-2" style="border-color: #e6ecff !important; background: #ffffff;">
                <div class="small fw-medium mb-1">策略包近 3 次增益</div>
                <template x-if="(promptStrategyRecentGainRows() || []).length === 0">
                    <div class="small text-muted">当前策略包历史不足 2 次，完成几轮优化后可查看趋势。</div>
                </template>
                <div class="d-flex flex-column gap-1" x-show="(promptStrategyRecentGainRows() || []).length > 0">
                    <template x-for="row in promptStrategyRecentGainRows()" :key="row.id">
                        <div class="small d-flex justify-content-between">
                            <span class="text-muted" x-text="`${row.fromScore} → ${row.toScore}`"></span>
                            <span :class="row.delta >= 0 ? 'text-success' : 'text-danger'" x-text="`${row.delta >= 0 ? '+' : ''}${row.delta}`"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="editor-ai-card mb-2">
            <div class="small fw-medium mb-2">优化模式</div>
            <div class="btn-group w-100 mb-2" role="group">
                <template x-for="mode in optimizeModeOptions()" :key="mode.key">
                    <button type="button" class="btn btn-sm"
                        :class="[
                            optimizeMode === mode.key ? 'btn-primary' : 'btn-outline-primary',
                            isOptimizeModeLocked(mode.key) ? 'disabled opacity-50' : ''
                        ]"
                        @click="setOptimizeMode(mode.key)"
                        :disabled="isAnyAiActionRunning()"
                        :title="isOptimizeModeLocked(mode.key) ? lockedFeatureHint('深度优化模式') : mode.desc"
                        :aria-disabled="isOptimizeModeLocked(mode.key) ? 'true' : 'false'"
                        x-text="mode.title"></button>
                </template>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <template x-for="preset in optimizePresetOptions()" :key="preset.key">
                    <button type="button" class="btn btn-sm"
                        :class="isOptimizePresetActive(preset.key) ? 'btn-secondary' : 'btn-outline-secondary'"
                        @click="applyOptimizePreset(preset.key)"
                        :disabled="isAnyAiActionRunning()"
                        x-text="preset.title"></button>
                </template>
                <button type="button"
                    class="btn btn-sm position-relative"
                    :class="shouldSuggestFreshGraduatePreset() ? 'btn-primary' : 'btn-outline-primary'"
                    @click="onFreshGraduatePresetClick()"
                    :disabled="isAnyAiActionRunning()">
                    应届生一键预设
                    <span x-show="shouldSuggestFreshGraduatePreset()" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">荐</span>
                </button>
            </div>
            <div class="small text-muted" x-text="optimizeModeScopeHint()"></div>
        </div>
        <div class="editor-ai-card mb-2" x-show="freshGraduateSuggestionRows().length > 0">
            <div class="small fw-medium mb-2 d-flex align-items-center">
                <i class="ti ti-school me-1 text-primary"></i>应届生优化建议
            </div>
            <ul class="small mb-0 ps-3">
                <template x-for="(tip, idx) in freshGraduateSuggestionRows()" :key="`fresh-tip-${idx}`">
                    <li x-text="tip"></li>
                </template>
            </ul>
        </div>

        <div class="editor-ai-card">
            <div class="small fw-medium mb-2 d-flex align-items-center justify-content-between">
                <span>优化方向（可多选）</span>
                <span class="text-muted" x-text="`已选 ${Array.isArray(optimizeGoals) ? optimizeGoals.length : 0} 项`"></span>
            </div>
            <div class="d-flex gap-1 mb-1 flex-wrap">
                <template x-for="preset in optimizePresetOptions()" :key="'preset-'+preset.key">
                    <button type="button" class="btn btn-sm" :class="isOptimizePresetActive(preset.key) ? 'btn-primary' : 'btn-outline-primary'" @click="applyOptimizePreset(preset.key)" :disabled="isAnyAiActionRunning()" x-text="preset.title"></button>
                </template>
            </div>
            <div class="d-flex gap-1 mb-2 flex-wrap">
                <button type="button" class="btn btn-outline-success btn-sm" @click="autoRecommendOptimizeGoalsFromJd()" :disabled="isAnyAiActionRunning()" x-show="jdParseResult">
                    <i class="ti ti-sparkles me-1"></i>JD 智能推荐
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" @click="autoRecommendOptimizeGoals()" :disabled="isAnyAiActionRunning()">
                    <i class="ti ti-bulb me-1"></i>内容智能推荐
                </button>
                <button type="button" class="btn btn-outline-info btn-sm" @click="autoRecommendOptimizePreset()" :disabled="isAnyAiActionRunning()">
                    <i class="ti ti-wand me-1"></i>岗位推荐预设
                </button>
            </div>
            <template x-if="Array.isArray(optimizeGoals) && optimizeGoals.length > 0">
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <template x-for="gk in optimizeGoals" :key="'sel-'+gk">
                        <span class="badge bg-success-lt text-success" style="cursor:pointer;font-size:10px;" @click="toggleOptimizeGoal(gk)" x-text="optimizeGoalTitle(gk) + ' ×'"></span>
                    </template>
                </div>
            </template>
            <template x-for="group in optimizeGoalGroups()" :key="group.key">
                <details class="mb-2" :open="group.key === 'job_match' || group.key === 'career_strategy'">
                    <summary class="small fw-medium d-flex justify-content-between" style="cursor:pointer;">
                        <span x-text="group.title"></span>
                        <span class="text-muted" x-text="group.desc"></span>
                    </summary>
                    <div class="row g-2 mt-1">
                        <template x-for="goal in optimizeGoalsByGroup(group.key)" :key="goal.key">
                            <div class="col-6">
                                <button type="button"
                                    class="btn btn-sm text-start w-100 h-100 d-flex align-items-start gap-2"
                                    :class="isOptimizeGoalSelected(goal.key) ? 'btn-success' : 'btn-outline-success'"
                                    @click="toggleOptimizeGoal(goal.key)"
                                    :disabled="isAnyAiActionRunning()">
                                    <i :class="goal.icon + ' mt-1'"></i>
                                    <span>
                                        <span class="fw-medium d-block" x-text="goal.title"></span>
                                        <span class="small opacity-75 d-block" x-text="goal.desc"></span>
                                    </span>
                                </button>
                            </div>
                        </template>
                    </div>
                </details>
            </template>
            <div class="small text-warning" x-show="isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-shield-check me-1"></i>真实性护栏默认开启：仅优化表达与结构，不新增未经证实事实。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('leadership') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>领导力+真实性：仅提炼已有管理贡献，不会编造管理职责。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('career_pivot') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>转型+真实性：仅重新框架化已有经验，不会添加未从事的职能。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('i18n_expression') && isOptimizeGoalSelected('concise')">
                <i class="ti ti-info-circle me-1"></i>国际化+精简：保留必要双语术语，不会因精简丢失英文术语。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('project_impact') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>项目影响力+真实性：仅基于已有事实放大影响描述，不会虚构项目规模。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('tech_depth') && isOptimizeGoalSelected('concise')">
                <i class="ti ti-info-circle me-1"></i>技术深度+精简：保留关键技术决策理由，仅压缩实现细节。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('data_driven') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>数据驱动+真实性：仅使用已有数据，不会编造具体数值。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('innovation') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>创新+真实性：仅基于已有创新事实提炼，不会编造创新成果。
            </div>
            <div class="small text-info" x-show="isOptimizeGoalSelected('certification') && isOptimizeGoalSelected('authenticity')">
                <i class="ti ti-info-circle me-1"></i>资质认证+真实性：仅突出已有认证，不会添加未持有的证书。
            </div>
            <div class="border rounded p-2 mt-2 bg-light-subtle" x-show="quantifiedGapChecklist().length > 0">
                <div class="small fw-medium mb-1">待补充指标建议</div>
                <ul class="small mb-0 ps-3">
                    <template x-for="(tip, idx) in quantifiedGapChecklist()" :key="`quant-tip-${idx}`">
                        <li x-text="tip"></li>
                    </template>
                </ul>
            </div>
        </div>
    </section>

    <div x-show="streamStatus === 'running' || streamStatus === 'done'" class="editor-ai-stream">
        <div class="editor-ai-stream-head d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span>AI 实时生成（真实优先）</span>
                <span class="badge bg-secondary-lt text-secondary" x-show="currentAiDriver" x-text="normalizeAiDriverLabel(currentAiDriver)"></span>
                <span class="badge bg-warning-lt text-warning" x-show="fallbackFromDriver && currentAiDriver" x-text="`已从 ${normalizeAiDriverLabel(fallbackFromDriver)} 切换`"></span>
            </div>
            <template x-if="optimizePollProgress && streamStatus === 'running'">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted" x-text="`${optimizePollProgress.current}/${optimizePollProgress.total}`"></span>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 11px;" @click="cancelOptimizePoll()">取消</button>
                </div>
            </template>
        </div>
        <div class="editor-ai-stream-body" x-text="streamText || '等待生成...'"></div>
        <template x-if="streamStatus === 'running'">
            <div class="mt-2">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span x-text="optimizeStageLabel()"></span>
                </div>
                <div class="d-flex gap-1">
                    <template x-for="(stage, i) in ['信号对齐', '内容重构', '关键词嵌入', '质量校验']" :key="i">
                        <div class="flex-fill text-center" style="font-size:10px;">
                            <div :class="`rounded mb-1 ${optimizeStageIndex() >= i ? 'bg-primary' : 'bg-secondary-lt'}`" style="height:3px;"></div>
                            <span :class="optimizeStageIndex() >= i ? 'text-primary' : 'text-muted'" x-text="stage"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        <div class="d-flex gap-1 flex-wrap mt-2" x-show="streamStatus === 'done' && optimizeResultTrustTags().length > 0">
            <template x-for="(tag, idx) in optimizeResultTrustTags()" :key="`trust-${idx}`">
                <span class="badge bg-info-lt text-info" x-text="tag"></span>
            </template>
        </div>
        <template x-if="streamStatus === 'done' && optimizeMetricsCompare?.delta !== undefined">
            <div class="mt-2 p-2 rounded" style="background:var(--tblr-bg-surface);">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-muted">预估分数变化</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small" x-text="optimizeMetricsCompare.before?.score || 0"></span>
                        <i class="ti ti-arrow-right text-muted"></i>
                        <span class="small fw-bold" x-text="optimizeMetricsCompare.after?.score || 0"></span>
                        <span class="badge" :class="optimizeMetricsCompare.delta > 0 ? 'bg-success-lt text-success' : (optimizeMetricsCompare.delta < 0 ? 'bg-danger-lt text-danger' : 'bg-secondary-lt')" x-text="(optimizeMetricsCompare.delta > 0 ? '+' : '') + optimizeMetricsCompare.delta"></span>
                    </div>
                </div>
            </div>
        </template>
        <template x-if="streamStatus === 'done' && optimizeChangesSummary">
            <div class="mt-2 p-2 rounded" style="background:var(--tblr-bg-surface);font-size:11px;">
                <div class="small fw-medium mb-1"><i class="ti ti-file-description me-1 text-primary"></i>AI 修改说明</div>
                <div class="text-secondary" style="white-space:pre-wrap;line-height:1.5;" x-text="optimizeChangesSummary"></div>
            </div>
        </template>
        <template x-if="streamStatus === 'done' && Array.isArray(optimizeGapActions) && optimizeGapActions.length > 0">
            <div class="mt-2">
                <div class="small text-muted mb-1"><i class="ti ti-bulb me-1"></i>补齐建议</div>
                <template x-for="(gap, gi) in optimizeGapActions" :key="gi">
                    <div class="d-flex align-items-start gap-1 mb-1" style="font-size:11px;">
                        <span class="badge bg-warning-lt text-warning" style="font-size:9px;" x-text="gap.action === 'add' ? '补充' : (gap.action === 'enhance' ? '增强' : '重写')"></span>
                        <span class="text-secondary" x-text="gap.content"></span>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="streamStatus === 'done' && Array.isArray(optimizeDiffModules) && optimizeDiffModules.length > 0">
            <div class="mt-2">
                <div class="small fw-medium mb-1 d-flex justify-content-between align-items-center">
                    <span><i class="ti ti-git-compare me-1 text-primary"></i>逐模块变更</span>
                    <span class="text-muted" x-text="optimizeDiffModules.filter(m => m.changed).length + ' 个模块变更'"></span>
                </div>
                <div style="max-height:200px;overflow-y:auto;">
                    <template x-for="dm in optimizeDiffModules" :key="'dm-' + dm.index">
                        <details class="mb-1 border rounded" :open="dm.changed">
                            <summary class="d-flex justify-content-between align-items-center px-2 py-1" style="cursor:pointer;font-size:11px;">
                                <span>
                                    <i :class="dm.changed ? 'ti ti-git-branch text-warning' : 'ti ti-check text-success'" class="me-1"></i>
                                    <span x-text="dm.label"></span>
                                </span>
                                <span class="badge" :class="dm.changed ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success'" style="font-size:9px;">
                                    <span x-show="dm.changed" x-text="(dm.deltaLen > 0 ? '+' : '') + dm.deltaLen + '字'"></span>
                                    <span x-show="!dm.changed">未变</span>
                                </span>
                            </summary>
                            <div class="p-2" style="font-size:10px;background:var(--tblr-bg-surface);">
                                <div x-show="dm.changed" class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted mb-1">优化前</div>
                                        <pre class="mb-0 p-1 border rounded bg-white" style="white-space:pre-wrap;word-break:break-all;max-height:120px;overflow-y:auto;font-size:10px;" x-text="dm.beforeText || '（空）'"></pre>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted mb-1">优化后</div>
                                        <pre class="mb-0 p-1 border rounded bg-light-success" style="white-space:pre-wrap;word-break:break-all;max-height:120px;overflow-y:auto;font-size:10px;" x-text="dm.afterText || '（已删除）'"></pre>
                                    </div>
                                </div>
                                <div x-show="!dm.changed" class="text-muted text-center" style="font-size:10px;">内容未发生变更</div>
                            </div>
                        </details>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="optimizePollProgress && streamStatus === 'running'">
            <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                     :style="`width: ${(optimizePollProgress.current / optimizePollProgress.total * 100).toFixed(0)}%`"></div>
            </div>
        </template>
    </div>
</div>
