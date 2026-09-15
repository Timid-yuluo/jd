@extends('layouts.user')

@section('title', 'AI 岗位定向优化 - ' . $resume->title)

@section('content')
<div data-route-user-resumes-optimize-stream-0="{{ route('user.resumes.optimize-stream', $resume) }}">
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-link text-secondary p-0">
                        <i class="ti ti-arrow-left me-1"></i>返回
                    </a>
                </div>
                <h2 class="page-title">AI 岗位定向优化</h2>
                <div class="mt-1 text-secondary">{{ $resume->title }}</div>
            </div>
            <div class="col-auto ms-auto">
                @if($resume->optimized_text)
                    <form action="{{ route('user.resumes.apply-optimized', $resume) }}" method="POST" class="d-inline" data-app-confirm="应用后会用优化版本覆盖当前简历正文，并清空旧的 ATS 分数，是否继续？">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>应用到简历
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-primary submit-btn" data-action="submit-optimize-form">
                        <i class="ti ti-refresh me-1"></i>重新优化
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-check me-2"></i> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-circle me-2"></i> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(!($advancedModelEnabled ?? false))
            <div class="alert alert-warning">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="ti ti-lock me-1"></i>
                        当前套餐使用标准模型与标准队列；高级模型、深度策略、优先队列仅专业版可用。
                    </div>
                    <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-crown me-1"></i>升级专业版
                    </a>
                </div>
            </div>
        @elseif($priorityQueueEnabled ?? false)
            <div class="alert alert-success py-2">
                <i class="ti ti-bolt me-1"></i>当前套餐已启用高级模型与优先队列。
            </div>
        @endif

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">优化参数</h3>
                    </div>
                    <div class="card-body">
                        <form id="optimize-form" action="{{ route('user.resumes.optimize', $resume) }}" method="POST" data-action="stream-optimize" data-prevent-double-submit="true" data-unsaved-warning="true">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">目标公司</label>
                                <input type="text" id="target-company-input" name="target_company" class="form-control"
                                    value="{{ old('target_company', $resume->target_company) }}"
                                    placeholder="例如：字节跳动">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">岗位名称</label>
                                <input type="text" id="target-job-title-input" name="target_job_title" class="form-control"
                                    value="{{ old('target_job_title', $resume->target_job_title) }}"
                                    placeholder="例如：高级后端开发工程师">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">目标岗位</label>
                                <input
                                    type="text"
                                    id="target-job-input"
                                    name="target_job"
                                    class="form-control @error('target_job') is-invalid @enderror"
                                    value="{{ old('target_job', $resume->target_job) }}"
                                    placeholder="例如：Java开发工程师"
                                >
                                @error('target_job')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">岗位描述 / JD</label>
                                <textarea id="target-job-description-input" name="target_job_description" class="form-control" rows="5"
                                    placeholder="粘贴目标岗位的职位描述（JD），AI 会根据 JD 中的关键词和要求进行针对性优化">{{ old('target_job_description', $resume->target_job_description) }}</textarea>
                                <div class="form-hint">填写越具体，AI 优化结果越贴近岗位 JD 和 ATS 关键词。</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">优化目标 <span class="text-danger">*</span></label>
                                @php
                                    $defaultGoals = ['ats_keywords','structure','quantified','skill_match','language','highlights'];
                                    $savedGoals = old('optimize_goals', $resume->optimize_goals ?? $defaultGoals);
                                    if (!is_array($savedGoals)) $savedGoals = $defaultGoals;
                                @endphp
                                <div class="row g-2">
                                    @foreach([
                                        'ats_keywords' => ['ATS 关键词优化', '提升简历在招聘系统中的匹配度', 'ti-search'],
                                        'structure' => ['结构优化', '调整简历布局，突出重点内容', 'ti-layout-cards'],
                                        'quantified' => ['量化成果', '用数据说话，展示具体成就', 'ti-chart-bar'],
                                        'skill_match' => ['技能匹配', '强化与目标岗位的技能关联', 'ti-target'],
                                        'language' => ['语言表达', '优化措辞，使用专业术语', 'ti-language'],
                                        'highlights' => ['亮点提炼', '突出核心优势和独特价值', 'ti-star'],
                                    ] as $key => $item)
                                    <div class="col-6">
                                        <label class="form-selectgroup-item flex-fill w-100">
                                            <input type="checkbox" name="optimize_goals[]" value="{{ $key }}" class="form-selectgroup-input" {{ in_array($key, $savedGoals, true) ? 'checked' : '' }}>
                                            <div class="form-selectgroup-label d-flex align-items-center p-2 w-100" style="min-height: 64px;">
                                                <div class="me-2 text-primary" style="font-size: 18px;"><i class="ti {{ $item[2] }}"></i></div>
                                                <div>
                                                    <div class="fw-medium small">{{ $item[0] }}</div>
                                                    <div class="text-secondary" style="font-size: 11px;">{{ $item[1] }}</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 submit-btn" id="optimize-btn">
                                <i class="ti ti-sparkles me-2"></i>{{ $resume->optimized_text ? '按目标岗位重新定向优化' : '开始 AI 定向优化' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">定向优化说明</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled text-secondary mb-0">
                            <li class="mb-2">原文不会自动覆盖，只有点击“应用优化结果”才会写回。</li>
                            <li class="mb-2">重新定向优化时会保留最新的目标岗位设定。</li>
                            <li>如果岗位方向变化较大，建议优化后重新执行 ATS 评分。</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">优化结果</h3>
                    </div>
                    <div class="card-body">
                        @if($resume->optimized_text)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="me-auto">优化后简历</h4>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-action="copy-optimized">
                                            <i class="ti ti-copy me-1"></i>复制
                                        </button>
                                        <form action="{{ route('user.resumes.apply-optimized', $resume) }}" method="POST" data-app-confirm="应用后会覆盖当前简历正文，并清空旧的 ATS 分数，是否继续？">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="ti ti-arrow-forward-up me-1"></i>应用优化结果
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="alert alert-primary" role="alert">
                                    <div class="d-flex">
                                        <div><i class="ti ti-info-circle me-2"></i></div>
                                        <div>应用后会将优化版本写回原始简历正文，同时清空旧的 ATS 分数，便于重新评估最新版本。</div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="border rounded h-100">
                                            <div class="p-3 border-bottom bg-light">
                                                <div class="fw-medium">优化前原文</div>
                                            </div>
                                            <div class="p-3 font-monospace text-wrap" style="white-space: pre-wrap; min-height: 420px; max-height: 620px; overflow-y: auto;">{{ $resume->content_raw }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border rounded h-100">
                                            <div class="p-3 border-bottom bg-primary-lt">
                                                <div class="fw-medium">优化后版本</div>
                                            </div>
                                            <div class="p-3 font-monospace text-wrap optimized-preview" style="white-space: pre-wrap; min-height: 420px; max-height: 620px; overflow-y: auto;">{{ $resume->optimized_text }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($resume->highlights)
                                <div class="mb-4">
                                    <h4 class="mb-3">亮点提炼</h4>
                                    <div class="row g-3">
                                        @foreach($resume->highlights as $highlight)
                                            <div class="col-md-6">
                                                <div class="card bg-success-lt">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ti ti-sparkles text-success me-2"></i>
                                                            <span class="text-break">{{ $highlight }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <div id="optimize-empty-state" class="text-center py-5">
                                <i class="ti ti-loader-2 text-secondary mb-3" style="font-size: 48px;"></i>
                                <h4 class="text-secondary">尚未生成优化结果</h4>
                                <p class="text-secondary mb-4">点击下方按钮，使用 AI 按目标岗位定向优化简历内容</p>
                            </div>
                            <div id="stream-output-area" class="d-none">
                                <div class="d-flex align-items-center mb-3">
                                    <h4 class="me-auto">AI 实时生成中</h4>
                                    <span class="badge bg-primary" id="stream-status">生成中...</span>
                                </div>
                                <div class="alert alert-info mb-3">
                                    <div class="d-flex">
                                        <div><i class="ti ti-info-circle me-2"></i></div>
                                        <div>内容正在逐字生成，生成完毕后将自动保存。请勿关闭页面。</div>
                                    </div>
                                </div>
                                <div class="border rounded">
                                    <div class="p-3 border-bottom bg-primary-lt">
                                        <div class="fw-medium">优化后版本（实时预览）</div>
                                    </div>
                                    <div id="stream-text" class="p-3 font-monospace text-wrap" style="white-space: pre-wrap; min-height: 200px; max-height: 500px; overflow-y: auto;"></div>
                                </div>
                                <div id="stream-highlights" class="mt-4 d-none">
                                    <h4 class="mb-3">亮点提炼</h4>
                                    <div class="row g-3" id="stream-highlights-list"></div>
                                </div>
                                <div class="mt-4 d-flex gap-2 justify-content-center">
                                    <form action="{{ route('user.resumes.apply-optimized', $resume) }}" method="POST" data-app-confirm="应用后会覆盖当前简历正文，并清空旧的 ATS 分数，是否继续？">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-check me-1"></i>应用优化结果
                                        </button>
                                    </form>
                                    <a href="{{ route('user.resumes.optimize-view', $resume) }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-refresh me-1"></i>刷新页面查看完整结果
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-resumes-optimize.js') }}"></script>
@endpush
