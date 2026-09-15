@extends('layouts.user')

@section('title', '开始面试')

@section('page-pretitle', '面试管理')
@section('page-title', '开始面试')

@push('styles')
<style>
.wizard-steps{display:flex;gap:0;margin-bottom:1.5rem}
.wizard-step{flex:1;text-align:center;position:relative;padding:0.75rem 0.5rem;cursor:pointer;border-bottom:3px solid var(--tblr-border-color,#e6e7e9);transition:all .2s}
.wizard-step.active{border-bottom-color:var(--tblr-primary,#0054a6);color:var(--tblr-primary,#0054a6)}
.wizard-step.completed{border-bottom-color:var(--tblr-success,#2fb344);color:var(--tblr-success,#2fb344)}
.wizard-step .step-number{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--tblr-border-color,#e6e7e9);color:#fff;font-size:0.8rem;font-weight:600;margin-bottom:0.25rem;transition:all .2s}
.wizard-step.active .step-number{background:var(--tblr-primary,#0054a6)}
.wizard-step.completed .step-number{background:var(--tblr-success,#2fb344)}
.wizard-step .step-label{display:block;font-size:0.8rem;font-weight:500}
.wizard-panel{display:none}
.wizard-panel.active{display:block}
.wizard-nav{display:flex;justify-content:space-between;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--tblr-border-color,#e6e7e9)}
@media(max-width:576px){.wizard-step .step-label{font-size:0.7rem}.wizard-step .step-number{width:24px;height:24px;font-size:0.7rem}}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">创建面试</h3>
            </div>
            <div class="card-body">
                @php
                    $vm = $viewModel;
                    $maxQ = $vm->maxQuestionSlider();
                    $minQ = $vm->minQuestionSlider();
                    $defaultQ = (int) old('question_count', $vm->defaultQuestionCount());
                @endphp
                <div class="alert alert-info py-2 mb-3">
                    <span class="me-3"><i class="ti ti-chart-donut-2 me-1"></i>场次 {{ $vm->formatQuotaSummary($vm->sessionQuota()) }}</span>
                    <span><i class="ti ti-file-text me-1"></i>JD定制 <span class="{{ $vm->customQuestionsEnabled() ? 'fw-medium' : 'text-warning' }}">{{ $vm->customQuestionsEnabled() ? '✓ 已开通' : '未开通' }}</span></span>
                    @if(!$vm->customQuestionsEnabled())
                        <a href="{{ route('user.membership.pricing') }}" class="btn btn-xs btn-primary ms-2 py-0">升级</a>
                    @endif
                    <div class="text-secondary small mt-1">创建面试消耗1次场次；提交回答不再消耗额外额度。</div>
                </div>

                <div class="wizard-steps" id="wizardSteps">
                    <div class="wizard-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label">简历与职位</span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label">面试配置</span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label">JD与关键词</span>
                    </div>
                </div>

                <form action="{{ route('user.interviews.store') }}" method="POST" id="interviewCreateForm">
                    @csrf

                    {{-- Step 1: 简历与职位 --}}
                    <div class="wizard-panel active" id="wizardPanel1">
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="small text-secondary"><i class="ti ti-flash me-1"></i>快捷预设：</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="前端开发工程师" data-type="technical" data-difficulty="medium" data-profile="junior" data-lang="zh">前端</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="Java后端工程师" data-type="technical" data-difficulty="hard" data-profile="experienced" data-lang="zh">Java</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="产品经理" data-type="behavioral" data-difficulty="medium" data-profile="junior" data-lang="zh">产品</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="销售经理" data-type="sales" data-difficulty="medium" data-profile="junior" data-lang="zh">销售</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="运营总监" data-type="management" data-difficulty="hard" data-profile="experienced" data-lang="zh">管理岗</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="UI/UX设计师" data-type="creative" data-difficulty="medium" data-profile="junior" data-lang="zh">设计师</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="财务分析师" data-type="finance" data-difficulty="medium" data-profile="junior" data-lang="zh">财务</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="电商运营经理" data-type="retail" data-difficulty="medium" data-profile="junior" data-lang="zh">电商</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="生产主管" data-type="manufacturing" data-difficulty="medium" data-profile="junior" data-lang="zh">制造</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="酒店经理" data-type="service" data-difficulty="medium" data-profile="junior" data-lang="zh">服务业</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="新媒体运营" data-type="media" data-difficulty="medium" data-profile="junior" data-lang="zh">传媒</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="教师" data-type="education" data-difficulty="medium" data-profile="fresh_graduate" data-lang="zh">教师</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="总监/VP" data-type="deep" data-difficulty="hard" data-profile="experienced" data-lang="zh">深度面谈</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn"
                                    data-position="应届生通用" data-type="mixed" data-difficulty="easy" data-profile="fresh_graduate" data-lang="zh">应届生</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">选择简历</label>
                            <select id="resume_id" name="resume_id" class="form-select @error('resume_id') is-invalid @enderror" required>
                                <option value="">请选择简历</option>
                                @foreach($vm->resumes as $resume)
                                    <option
                                        value="{{ $resume->id }}"
                                        data-target-job="{{ $resume->target_job }}"
                                        data-modules="{{ $resume->modules_count }}"
                                        data-ats-score="{{ $resume->ats_score ?? '' }}"
                                        data-updated-at="{{ $resume->updated_at?->diffForHumans() }}"
                                        {{ old('resume_id') == $resume->id ? 'selected' : '' }}
                                    >
                                        {{ $resume->title }}
                                        @if($resume->modules_count) — {{ $resume->modules_count }} 个模块 @endif
                                        @if($resume->updated_at) · {{ $resume->updated_at?->format('m-d') }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('resume_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($vm->resumesIsEmpty())
                                <div class="form-hint text-danger">
                                    <a href="{{ route('user.resumes.create') }}">请先创建一份简历</a>
                                </div>
                            @endif
                        </div>
                        <div class="card bg-light-subtle d-none mb-3" id="resumePreviewCard" style="border-left:4px solid var(--tblr-primary);">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-medium" id="resumePreviewTitle">—</div>
                                    <div class="small text-secondary mt-1">
                                        <span class="me-3"><i class="ti ti-target me-1"></i>目标岗位：<span id="resumePreviewJob">未填写</span></span>
                                        <span class="me-3"><i class="ti ti-layers me-1"></i>模块：<span id="resumePreviewModules">—</span> 个</span>
                                        <span class="me-3"><i class="ti ti-clock me-1"></i>更新于：<span id="resumePreviewTime">—</span></span>
                                        <span id="resumePreviewAtsWrap" class="d-none"><i class="ti ti-star me-1"></i>ATS：<span id="resumePreviewAts">—</span></span>
                                    </div>
                                    <div class="d-none mt-1" id="resumePreviewAtsWarn">
                                        <span class="badge bg-warning-lt text-warning"><i class="ti ti-alert-triangle me-1"></i>ATS 分数偏低，建议先优化简历再面试</span>
                                    </div>
                                    </div>
                                    <div class="text-end small text-secondary" style="white-space:nowrap;">
                                        <span class="badge bg-green-lt text-green d-none" id="resumePreviewReady">准备就绪</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-primary py-2" id="resume-personalization-panel">
                            <div class="small">
                                <span class="me-2"><i class="ti ti-user-search"></i></span>
                                <span class="fw-medium">一对一出题依据：</span>
                                <span id="resume-target-job-tip">请先选择简历</span>
                                <span class="text-secondary"> — </span>
                                <span class="text-secondary" id="resume-anchors-tip">系统会基于简历亮点生成定制问题。</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">应聘职位</label>
                                    <input type="text" id="position" name="position" class="form-control @error('position') is-invalid @enderror"
                                        value="{{ old('position') }}" placeholder="例如：前端开发工程师" required>
                                    @error('position')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">公司名称</label>
                                    <input type="text" name="company" class="form-control @error('company') is-invalid @enderror"
                                        value="{{ old('company') }}" placeholder="例如：腾讯科技">
                                    @error('company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: 面试配置 --}}
                    <div class="wizard-panel" id="wizardPanel2">
                        <div class="mb-3">
                            <label class="form-label required">我的求职身份</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item mb-2">
                                    <input type="radio" name="candidate_profile" value="fresh_graduate" class="form-selectgroup-input"
                                        {{ old('candidate_profile', 'fresh_graduate') == 'fresh_graduate' ? 'checked' : '' }} required>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3"><span class="form-selectgroup-check"></span></span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-school me-2 text-primary"></i>应届生
                                            </span>
                                            <span class="d-block text-secondary small mt-1">题目更注重基础能力、学习潜力和项目表达，降低"必须有多年实战"的门槛。</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item mb-2">
                                    <input type="radio" name="candidate_profile" value="no_experience" class="form-selectgroup-input"
                                        {{ old('candidate_profile') == 'no_experience' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3"><span class="form-selectgroup-check"></span></span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-sparkles me-2 text-success"></i>无经验转岗
                                            </span>
                                            <span class="d-block text-secondary small mt-1">题目更强调迁移能力、学习路径和小项目成果，帮助你讲清"为什么能胜任"。</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item mb-2">
                                    <input type="radio" name="candidate_profile" value="junior" class="form-selectgroup-input"
                                        {{ old('candidate_profile') == 'junior' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3"><span class="form-selectgroup-check"></span></span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-briefcase me-2 text-warning"></i>1-3年经验
                                            </span>
                                            <span class="d-block text-secondary small mt-1">题目兼顾基础与进阶，关注项目落地、问题拆解与协作交付能力。</span>
                                        </span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="candidate_profile" value="experienced" class="form-selectgroup-input"
                                        {{ old('candidate_profile') == 'experienced' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3"><span class="form-selectgroup-check"></span></span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-crown me-2 text-danger"></i>3年+经验
                                            </span>
                                            <span class="d-block text-secondary small mt-1">题目更强调复杂场景、权衡决策、业务结果和复盘深度。</span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            @error('candidate_profile')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-hint" id="candidate-profile-hint">建议先用"应届生"模式，先建立表达结构，再逐步提升难度。</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">面试难度</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="difficulty" value="easy" class="form-selectgroup-input" {{ old('difficulty') == 'easy' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-2">
                                        <span class="me-2"><span class="form-selectgroup-check"></span></span>
                                        <span class="text-start"><strong>简单</strong><br><small class="text-secondary">基础概念、常规问题</small></span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="difficulty" value="medium" class="form-selectgroup-input" {{ old('difficulty', 'medium') == 'medium' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-2">
                                        <span class="me-2"><span class="form-selectgroup-check"></span></span>
                                        <span class="text-start"><strong>中等</strong><br><small class="text-secondary">项目深入、场景分析</small></span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="difficulty" value="hard" class="form-selectgroup-input" {{ old('difficulty') == 'hard' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-2">
                                        <span class="me-2"><span class="form-selectgroup-check"></span></span>
                                        <span class="text-start"><strong>困难</strong><br><small class="text-secondary">系统设计、深挖追问</small></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">面试类型</label>
                            @php
                                $typeGroups = $vm->interviewTypeGroups();
                                $profileRecommendations = $vm->profileTypeRecommendations();
                            @endphp
                            @foreach($typeGroups as $groupKey => $group)
                                <div class="mb-2">
                                    <div class="small text-secondary fw-medium mb-1">{{ $group['label'] }}</div>
                                    <div class="form-selectgroup">
                                        @foreach($group['types'] as $typeKey => $typeInfo)
                                            <label class="form-selectgroup-item">
                                                <input type="radio" name="type" value="{{ $typeKey }}" class="form-selectgroup-input"
                                                    {{ old('type', 'mixed') == $typeKey ? 'checked' : '' }} required>
                                                <span class="form-selectgroup-label">
                                                    <i class="ti {{ $typeInfo['icon'] ?? 'ti-circle' }} me-2"></i>{{ $typeInfo['label'] }}
                                                    <span class="badge bg-primary-lt text-primary ms-1 d-none type-recommend-badge" data-type="{{ $typeKey }}">推荐</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">面试语言</label>
                                    <div class="form-selectgroup">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="language" value="zh" class="form-selectgroup-input" {{ old('language', 'zh') == 'zh' ? 'checked' : '' }}>
                                            <span class="form-selectgroup-label"><i class="ti ti-world me-1"></i>中文</span>
                                        </label>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="language" value="en" class="form-selectgroup-input" {{ old('language') == 'en' ? 'checked' : '' }}>
                                            <span class="form-selectgroup-label"><i class="ti ti-language me-1"></i>English</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">面试题目数量</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-fill" name="question_count" min="{{ $minQ }}" max="{{ $maxQ }}" value="{{ $defaultQ }}" id="questionCountRange" style="max-width:200px;">
                                        <span class="badge bg-primary-lt text-primary px-2 py-1" id="questionCountBadge">{{ $defaultQ }} 题</span>
                                    </div>
                                    <div class="form-hint">套餐上限 {{ $maxQ }} 题，<span id="questionTimeHint">预计耗时约 {{ max(1, round($defaultQ * 2.5)) }} 分钟</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">面试模式</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item mb-2">
                                    <input type="radio" name="mode" value="text" class="form-selectgroup-input"
                                        {{ old('mode', 'text') == 'text' ? 'checked' : '' }} required>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-keyboard me-2 text-primary"></i>文字面试
                                            </span>
                                            <span class="d-block text-secondary small mt-1">
                                                适合在电脑前进行，支持键盘输入回答
                                            </span>
                                        </span>
                                    </span>
                                </label>
                                <label class="form-selectgroup-item mb-2">
                                    <input type="radio" name="mode" value="voice" class="form-selectgroup-input"
                                        {{ old('mode') == 'voice' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </span>
                                        <span class="form-selectgroup-label-content d-block text-start">
                                            <span class="form-selectgroup-title strong d-flex align-items-center">
                                                <i class="ti ti-microphone me-2 text-success"></i>语音面试
                                            </span>
                                            <span class="d-block text-secondary small mt-1">
                                                适合手机端，AI语音提问，语音回答，扫码即可开始
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            @error('mode')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 练习模式开关 --}}
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="is_practice" value="1" id="isPracticeSwitch" {{ old('is_practice') ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold">
                                    <i class="ti ti-rocket me-1 text-info"></i>练习模式
                                </span>
                            </label>
                            <div class="form-hint" id="practiceHint" style="display:none;">
                                <span class="badge bg-info-lt text-info me-1">不消耗额度</span>
                                <span class="badge bg-secondary-lt text-dark me-1">不生成评分报告</span>
                                适合熟悉面试流程和练习表达，完成后仅展示问答记录。
                            </div>
                        </div>
                    </div>

                    {{-- Step 3: JD与关键词 --}}
                    <div class="wizard-panel" id="wizardPanel3">
                        <div class="mb-3">
                            <label class="form-label d-flex align-items-center gap-2">
                                <span>岗位 JD（可选，推荐）</span>
                                @if($vm->customQuestionsEnabled())
                                    <span class="badge bg-success-lt text-success">套餐已支持</span>
                                @else
                                    <span class="badge bg-warning-lt text-warning">当前套餐未支持</span>
                                @endif
                            </label>
                            @if($vm->customQuestionsEnabled())
                                <div class="d-flex gap-2 mb-2 flex-wrap">
                                    <div class="dropdown" id="jdImportDropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" id="jdImportToggle">
                                            <i class="ti ti-file-import me-1"></i>快速导入 JD
                                        </button>
                                        <div class="dropdown-menu p-0" style="max-width:420px;" id="jdImportMenu">
                                            <span class="dropdown-item small text-secondary py-2"><span class="spinner-border spinner-border-sm me-1"></span>加载中...</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(!$vm->customQuestionsEnabled())
                                <div class="alert alert-warning py-2 mb-2">
                                    <i class="ti ti-lock me-1"></i>
                                    当前套餐暂不支持 JD 定制题目，请升级基础版或专业版后使用。
                                    <a href="{{ route('user.membership.pricing') }}" class="alert-link ms-1">去升级</a>
                                </div>
                            @endif
                            <textarea
                                name="job_description"
                                class="form-control @error('job_description') is-invalid @enderror"
                                rows="5"
                                maxlength="8000"
                                placeholder="{{ $vm->customQuestionsEnabled() ? '粘贴岗位职责、任职要求、技术栈关键词等。系统会结合简历与JD双向生成问题。' : '升级基础版或专业版后可启用 JD 定制题目。' }}"
                                {{ $vm->customQuestionsEnabled() ? '' : 'disabled' }}
                            >{{ $vm->customQuestionsEnabled() ? old('job_description') : '' }}</textarea>
                            @error('job_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">建议包含：核心职责、必备技能、加分项。填写后问题会更贴合真实招聘要求。</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">行业 / 技能关键词<span class="text-secondary ms-1 small">（已选 <span id="kwCount">0</span>/10）</span></label>
                            <input type="hidden" name="tech_keywords" id="techKeywordsInput" value="{{ old('tech_keywords', '') }}">
                            <div class="d-flex flex-wrap align-items-center gap-1 border rounded p-2" id="techTagsSelected" style="min-height:32px;">
                                <span class="text-secondary small" id="kwPlaceholder">点击右侧标签添加</span>
                            </div>
                            <details class="mt-2" id="kwDetails">
                                <summary class="small text-secondary cursor-pointer">展开关键词列表</summary>
                                <div class="mt-2 p-2 border rounded bg-light-subtle" id="kwPanel" style="max-height:280px;overflow-y:auto;">
                                    <input type="text" class="form-control form-control-sm mb-2" id="kwSearchInput" placeholder="搜索关键词..." autocomplete="off">
                                    <div class="d-flex flex-wrap gap-1 mb-2" id="kwTypeTabs">
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-primary kw-type-tab" data-type="technical" style="font-size:0.72rem;">技术</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="behavioral" style="font-size:0.72rem;">行为</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="mixed" style="font-size:0.72rem;">综合</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="sales" style="font-size:0.72rem;">销售</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="management" style="font-size:0.72rem;">管理</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="creative" style="font-size:0.72rem;">创意</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="finance" style="font-size:0.72rem;">金融</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="retail" style="font-size:0.72rem;">零售</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="manufacturing" style="font-size:0.72rem;">制造</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="service" style="font-size:0.72rem;">服务</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="media" style="font-size:0.72rem;">媒体</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="education" style="font-size:0.72rem;">教育</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="deep" style="font-size:0.72rem;">深度</button>
                                        <button type="button" class="btn btn-xs py-0 px-2 btn-outline-secondary kw-type-tab" data-type="common" style="font-size:0.72rem;">通用</button>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1" id="techTagsBody">
                                        <span class="text-secondary small">点击上方分类加载关键词</span>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    {{-- 向导导航按钮 --}}
<div class="wizard-nav">
                    <div>
                        <a href="{{ route('user.interviews.index') }}" class="btn btn-outline-secondary" id="btnCancel">取消</a>
                        <button type="button" class="btn btn-outline-secondary d-none" id="btnPrev"><i class="ti ti-arrow-left me-1"></i>上一步</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="btnNext">下一步<i class="ti ti-arrow-right ms-1"></i></button>
                        <button type="submit" class="btn btn-primary submit-btn d-none" id="btnSubmit" {{ $vm->resumesIsEmpty() ? 'disabled' : '' }}>
                            <i class="ti ti-player-play me-2"></i>开始面试
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<div id="interviewLoadingOverlay" style="position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,.92);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;flex-direction:column;gap:16px;">
    <div style="width:48px;height:48px;border:4px solid #e5e7eb;border-top-color:#5570e6;border-radius:50%;animation:spin .8s linear infinite;"></div>
    <div style="font-size:16px;font-weight:600;color:#1e293b;">正在创建面试...</div>
    <div style="font-size:13px;color:#94a3b8;">AI 正在准备出题，请稍候</div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
</div>
</div>

    <div class="col-lg-4">
        <div class="card d-lg-block">
            <div class="card-header">
                <h3 class="card-title">面试说明</h3>
            </div>
            <div class="card-body">
                    <div class="mb-3">
                        <h4><i class="ti ti-info-circle me-2 text-primary"></i>如何工作</h4>
                        <p class="text-secondary">AI面试官会根据你选择的简历、求职身份、职位和 JD，进行一对一出题与追问。</p>
                    </div>
                    <div class="mb-3">
                        <h4><i class="ti ti-info-circle me-2 text-primary"></i>面试说明</h4>
                        <div class="text-secondary small">
                            <p class="mb-2"><strong>1. 选简历和职位</strong><br>系统会根据你的简历内容+目标职位智能出题。建议先用 AI 优化简历再面试，效果更好。</p>
                            <p class="mb-2"><strong>2. 填写岗位 JD（推荐）</strong><br>粘贴目标岗位的招聘描述，AI 会围绕 JD 中的技能要求和职责范围设计针对性问题，模拟真实面试场景。需要套餐支持。</p>
                            <p class="mb-2"><strong>3. 选面试类型</strong><br>根据目标岗位选择最合适的面试类型。技术岗选「技术面试」，管理岗选「管理岗」，不确定选「综合面试」。</p>
                            <p class="mb-2"><strong>4. 选难度和语言</strong><br>简单适合应届生/转岗，中等适合有经验者，困难适合资深岗位。语言可选择中文或 English。</p>
                            <p class="mb-2"><strong>5. 技术关键词（可选）</strong><br>勾选你目标岗位需要的技能标签，AI 会围绕这些技能出题。建议选 3-5 个最相关的。</p>
                            <p class="mb-2"><strong>6. 面试模式</strong><br>文字模式在电脑/手机上打字作答；语音模式通过语音交互，更适合模拟真实面试氛围。</p>
                            <p class="mb-2"><strong>7. 题目数量</strong><br>建议 3-5 题，每题回答后 AI 会打分和给出改进建议，所有题目完成后生成综合报告。</p>
                            <p class="mb-0"><strong>💡 提示</strong><br>回答时尽量使用 STAR 框架（情境→任务→行动→结果），包含具体数据和成果，得分更高。</p>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="ti ti-device-mobile me-2"></i>
                        <strong>语音面试提示：</strong>选择语音模式后，您可以在手机上扫码进行面试，AI会通过语音与您交流。
                    </div>
                    <div class="alert alert-primary py-2">
                        <i class="ti ti-list-numbers me-2"></i>
                        当前套餐单场最多 {{ $vm->interviewMaxQuestions() }} 题。
                    </div>
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        面试结束后可查看详细报告。
                    </div>
                </div>
            </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
window.__interviewProfileRecommendations = @json(config('interview.profile_type_recommendations', []));
document.addEventListener('DOMContentLoaded', function() {
    const sw = document.getElementById('isPracticeSwitch');
    const hint = document.getElementById('practiceHint');
    if (sw && hint) {
        function toggle() { hint.style.display = sw.checked ? 'block' : 'none'; }
        sw.addEventListener('change', toggle);
        toggle();
    }
});
</script>
<script src="{{ asset('js/pages/user-interviews-create.js') }}?v={{ filemtime(public_path('js/pages/user-interviews-create.js')) }}"></script>
@endpush
