@extends('layouts.user')

@section('title', '匹配分析结果')

@section('page-pretitle', '智能分析')
@section('page-title', '匹配分析结果')

@section('page-actions')
<div class="d-flex gap-2">
    <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-primary">
        <i class="ti ti-arrow-left me-2"></i>重新分析
    </a>
    <button type="button" class="btn btn-outline-secondary" id="exportPdfBtn">
        <i class="ti ti-download me-1"></i>导出 PDF
    </button>
</div>
@endsection

@php
    $score = $result['match_score'] ?? 0;
    $level = $result['level'] ?? '待提升';
    $levelColors = [
        '卓越' => ['bg' => 'text-primary', 'bar' => 'bg-primary', 'badge' => 'bg-primary-lt'],
        '优秀' => ['bg' => 'text-success', 'bar' => 'bg-success', 'badge' => 'bg-success-lt'],
        '良好' => ['bg' => 'text-info', 'bar' => 'bg-info', 'badge' => 'bg-info-lt'],
        '合格' => ['bg' => 'text-warning', 'bar' => 'bg-warning', 'badge' => 'bg-warning-lt'],
        '待提升' => ['bg' => 'text-danger', 'bar' => 'bg-danger', 'badge' => 'bg-danger-lt'],
    ];
    $colors = $levelColors[$level] ?? $levelColors['待提升'];
@endphp

@section('content')
@if($score < 55)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="ti ti-alert-triangle fs-3"></i>
    <div>
        <strong>匹配度较低（{{ $score }} 分）</strong>：建议先优化简历再投递，可显著提升面试机会。
        @if(isset($resume) && $resume)
            <a href="{{ route('user.resumes.optimize-view', $resume) }}" class="alert-link ms-1">去优化简历 →</a>
        @endif
    </div>
</div>
@endif
<div class="row">
    <div class="col-lg-8">
        {{-- 总评分卡片 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">匹配度评分</h3>
                @if(!empty($result['level']))
                    <span class="badge {{ $colors['badge'] }} fs-6">{{ $level }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <div class="display-1 fw-bold me-4 {{ $colors['bg'] }}">
                        {{ $score }}
                        <small class="fs-5 text-secondary">/ 100</small>
                    </div>
                </div>

                <div class="progress mb-3" style="height: 24px;">
                    <div class="progress-bar {{ $colors['bar'] }}"
                        role="progressbar" style="width: {{ min($score, 100) }}%"
                        aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $score }}%
                    </div>
                </div>

                @if(!empty($result['summary']))
                    <div class="alert {{ $colors['badge'] }} d-flex align-items-start gap-2 py-2 px-3 mb-0">
                        <i class="ti ti-info-circle mt-0_5"></i>
                        <span>{{ $result['summary'] }}</span>
                    </div>
                @else
                    <div class="text-secondary small mb-0">
                        基于AI对简历内容和岗位描述的深度分析得出
                    </div>
                @endif
            </div>
        </div>

        {{-- JD 摘要 --}}
        @if(!empty($jobDescription))
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;" id="jdToggleHeader">
                <h3 class="card-title mb-0"><i class="ti ti-file-text text-secondary me-2"></i>岗位描述原文</h3>
                <i class="ti ti-chevron-down" id="jdToggleIcon"></i>
            </div>
            <div class="collapse" id="jdCollapse">
                <div class="card-body">
                    <div class="bg-light rounded p-3" style="white-space:pre-wrap;max-height:400px;overflow:auto;font-size:0.85rem;line-height:1.7;">{{ $jobDescription }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- 维度分项 --}}
        @if(!empty($result['breakdown']))
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">分项得分</h3>
            </div>
            <div class="card-body">
                @php
                    $dimLabels = [
                        'skill_match' => '技能匹配',
                        'experience_fit' => '经验契合',
                        'quantified_results' => '成果量化',
                        'expression_structure' => '表达与结构',
                        'differentiation' => '差异化亮点',
                    ];
                @endphp
                <div class="space-y-3">
                    @foreach($result['breakdown'] as $key => $dim)
                        @php
                            $pct = $dim['max_score'] > 0 ? round($dim['score'] / $dim['max_score'] * 100) : 0;
                            $barColor = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">{{ $dimLabels[$key] ?? $key }}</span>
                                <span class="text-secondary small">{{ $dim['score'] }} / {{ $dim['max_score'] }}</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>
                            @if(!empty($dim['detail']))
                                <div class="text-secondary small mt-1">{{ $dim['detail'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- 匹配优势 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-checks text-success me-2"></i>匹配优势</h3>
            </div>
            <div class="card-body">
                @if(!empty($result['strengths']))
                    <div class="row g-3">
                        @foreach($result['strengths'] as $strength)
                            <div class="col-md-6">
                                <div class="card bg-success-lt border-0">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-circle-check text-success me-2 fs-5"></i>
                                            <span>{{ $strength }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-secondary text-center py-4">暂无明显优势</div>
                @endif
            </div>
        </div>

        {{-- 不足之处 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-alert-triangle text-danger me-2"></i>不足之处</h3>
            </div>
            <div class="card-body">
                @if(!empty($result['weaknesses']))
                    <div class="row g-3">
                        @foreach($result['weaknesses'] as $weakness)
                            <div class="col-md-6">
                                <div class="card bg-danger-lt border-0">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ti ti-alert-circle text-danger me-2 fs-5"></i>
                                            <span>{{ $weakness }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-secondary text-center py-4">暂未发现明显不足</div>
                @endif
            </div>
        </div>

        {{-- 改进建议 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-bulb text-warning me-2"></i>改进建议</h3>
            </div>
            <div class="card-body">
                @if(!empty($result['suggestions']))
                    <div class="list-group list-group-flush">
                        @foreach($result['suggestions'] as $index => $suggestion)
                            @php
                                $priorityConfig = [
                                    'high' => ['label' => '高优先级', 'color' => 'danger'],
                                    'medium' => ['label' => '中优先级', 'color' => 'warning'],
                                    'low' => ['label' => '低优先级', 'color' => 'info'],
                                ];
                                $priority = $suggestion['priority'] ?? 'medium';
                                $pCfg = $priorityConfig[$priority] ?? $priorityConfig['medium'];
                            @endphp
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge rounded-pill bg-{{ $pCfg['color'] }}">{{ $index + 1 }}</span>
                                    <div class="flex-fill">
                                        @if(!empty($suggestion['area']))
                                            <div class="fw-medium small text-primary mb-1">{{ $suggestion['area'] }}
                                                <span class="badge bg-outline-secondary ms-1" style="font-size:0.65rem;">{{ $pCfg['label'] }}</span>
                                            </div>
                                        @endif
                                        <div>{{ $suggestion['action'] ?? $suggestion['text'] ?? '' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-secondary text-center py-4">暂无改进建议</div>
                @endif
            </div>
        </div>

        {{-- 技能缺口 --}}
        @if(!empty($result['skill_gaps']) && (
            !empty($result['skill_gaps']['missing_hard_skills'])
            || !empty($result['skill_gaps']['missing_soft_skills'])
            || !empty($result['skill_gaps']['nice_to_have'])
        ))
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-key text-secondary me-2"></i>技能缺口分析</h3>
            </div>
            <div class="card-body">

                @if(!empty($result['skill_gaps']['missing_hard_skills']))
                <div class="mb-3">
                    <div class="fw-medium text-danger mb-2"><i class="ti ti-code me-1"></i>缺失硬技能（核心）</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($result['skill_gaps']['missing_hard_skills'] as $kw)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">
                                <i class="ti ti-x me-1" style="font-size:0.7rem;"></i>{{ $kw }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!empty($result['skill_gaps']['missing_soft_skills']))
                <div class="mb-3">
                    <div class="fw-medium text-warning mb-2"><i class="ti ti-users me-1"></i>缺失软技能 / 素质</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($result['skill_gaps']['missing_soft_skills'] as $kw)
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2">
                                {{ $kw }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!empty($result['skill_gaps']['nice_to_have']))
                <div class="mb-0">
                    <div class="fw-medium text-info mb-2"><i class="ti ti-star me-1"></i>加分项（非必须）</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($result['skill_gaps']['nice_to_have'] as $kw)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2">
                                + {{ $kw }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
        @endif

        {{-- 技能提升路线图 --}}
        @if(!empty($result['skill_gaps']) && (!empty($result['skill_gaps']['missing_hard_skills']) || !empty($result['skill_gaps']['missing_soft_skills'])))
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-road text-primary me-2"></i>技能提升路线图</h3>
            </div>
            <div class="card-body">
                @php
                    $allGaps = array_merge(
                        $result['skill_gaps']['missing_hard_skills'] ?? [],
                        $result['skill_gaps']['missing_soft_skills'] ?? []
                    );
                    $total = count($allGaps);
                @endphp
                <p class="text-secondary small mb-3">建议按以下优先级逐步补强，每阶段聚焦 1-2 项核心技能：</p>
                @if($total > 0)
                <div class="timeline">
                    @foreach(array_slice($allGaps, 0, 6) as $i => $skill)
                        @php
                            $phase = $i < 2 ? '短期（1-2周）' : ($i < 4 ? '中期（1-2月）' : '长期（3月+）');
                            $phaseColor = $i < 2 ? 'success' : ($i < 4 ? 'warning' : 'info');
                        @endphp
                        <div class="timeline-item d-flex gap-3 mb-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="badge bg-{{ $phaseColor }} rounded-circle" style="min-width:28px;height:28px;display:flex;align-items:center;justify-content:center;">{{ $i + 1 }}</span>
                                @if(!$loop->last)<div class="border-start border-2 border-{{ $phaseColor }} mt-1" style="min-height:30px;"></div>@endif
                            </div>
                            <div>
                                <div class="fw-medium">{{ $skill }}</div>
                                <div class="small text-secondary">{{ $phase }} · {{ $i < 2 ? '核心必备，优先攻克' : ($i < 4 ? '重要提升，增强竞争力' : '长期积累，拓宽能力边界') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
                @if(!empty($result['skill_gaps']['nice_to_have']))
                <div class="mt-3 pt-3 border-top">
                    <div class="small text-secondary"><i class="ti ti-star me-1"></i>加分项（有余力时学习）：{{ implode('、', $result['skill_gaps']['nice_to_have']) }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- 面试重点 --}}
        @if(!empty($result['interview_focus_areas']))
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-target text-primary me-2"></i>面试准备重点</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary small mb-3">以下能力点在岗位要求中权重较高，建议面试前重点准备：</p>
                <div class="row g-2">
                    @foreach($result['interview_focus_areas'] as $focus)
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2 p-2 rounded bg-primary bg-opacity-10">
                                <i class="ti ti-point-filled text-primary"></i>
                                <span class="small fw-medium">{{ $focus }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- 右侧栏 --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">分析信息</h3>
            </div>
            <div class="card-body">
                @if(isset($analysisHistory))
                    <div class="mb-3">
                        <div class="text-secondary small">历史记录</div>
                        <div>#{{ $analysisHistory->id }} · {{ $analysisHistory->created_at?->format('Y-m-d H:i') }}</div>
                    </div>
                @endif
                <div class="mb-3">
                    <div class="text-secondary small">分析简历</div>
                    @if(isset($resume) && $resume)
                        <div><a href="{{ route('user.resumes.show', $resume) }}">{{ $resume->title }}</a></div>
                    @else
                        <div class="text-secondary">简历已删除</div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">简历目标职位</div>
                    <div>{{ (isset($resume) && $resume) ? ($resume->target_job ?: '未填写') : '无' }}</div>
                </div>
                @if(!empty($level))
                <div class="mb-0">
                    <div class="text-secondary small">综合评级</div>
                    <span class="badge {{ $colors['badge'] }} fs-6">{{ $level }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">下一步操作</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if(isset($resume) && $resume)
                        @php
                            $suggestions = array_map(function($s) { return $s['action'] ?? $s['text'] ?? ''; }, $result['suggestions'] ?? []);
                            $suggestionsJson = json_encode($suggestions);
                            $suggestionsHash = md5($suggestionsJson);
                        @endphp
                        <form method="POST" action="{{ route('user.resumes.optimize-view', $resume) }}" style="display:inline;" id="optimizeWithSuggestionsForm">
                            @csrf
                            <input type="hidden" name="analysis_suggestions" value="{{ e($suggestionsJson) }}">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="ti ti-sparkles me-2"></i>一键优化简历
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline-secondary" disabled>
                            <i class="ti ti-lock me-2"></i>优化简历（简历已删除）
                        </button>
                    @endif
                    <a href="{{ route('user.interviews.create', (isset($resume) && $resume) ? ['resume_id' => $resume->id] : []) }}" class="btn btn-success">
                        <i class="ti ti-message-chatbot me-2"></i>开始AI面试
                    </a>
                    <a href="{{ route('user.kanban.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-briefcase me-2"></i>查看招聘推荐
                    </a>
                    <a href="{{ route('user.jobs.analyze.history.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-history me-2"></i>查看历史对比
                    </a>
                    <button type="button" class="btn btn-outline-secondary" id="shareAnalysisBtn">
                        <i class="ti ti-share me-2"></i>分享结果
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var jdHeader = document.getElementById('jdToggleHeader');
    var jdCollapse = document.getElementById('jdCollapse');
    var jdIcon = document.getElementById('jdToggleIcon');
    if (jdHeader && jdCollapse) {
        jdHeader.addEventListener('click', function() {
            var open = jdCollapse.classList.toggle('show');
            if (jdIcon) jdIcon.className = open ? 'ti ti-chevron-up' : 'ti ti-chevron-down';
        });
    }

    var exportBtn = document.getElementById('exportPdfBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            @if(isset($analysisHistory) && $analysisHistory)
            window.open('{{ route('user.jobs.analyze.history.pdf', $analysisHistory->id) }}', '_blank');
            @else
            window.print();
            @endif
        });
    }

    var shareBtn = document.getElementById('shareAnalysisBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            var url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    if (typeof window.appNotify === 'function') window.appNotify('链接已复制到剪贴板', 'success');
                    else alert('链接已复制');
                });
            } else {
                var input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
                if (typeof window.appNotify === 'function') window.appNotify('链接已复制到剪贴板', 'success');
                else alert('链接已复制');
            }
        });
    }
})();
</script>
@endpush
