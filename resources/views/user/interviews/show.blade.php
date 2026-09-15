@extends('layouts.user')

@section('title', '面试详情')

@section('page-pretitle', '面试管理')
@section('page-title', '面试详情')

@section('content')
@php
    $planSummary = is_array($interviewPlanSummary ?? null) ? $interviewPlanSummary : [];
    $usageSummary = is_array($interviewUsageSummary ?? null) ? $interviewUsageSummary : [];
    $customQuestionsEnabled = (bool) ($planSummary['customQuestionsEnabled'] ?? false);
    $interviewMaxQuestions = (int) ($planSummary['interviewMaxQuestions'] ?? config('interview.max_questions', 5));
    $interviewSessionQuota = is_array($planSummary['interviewSessionQuotaCheck'] ?? null) ? $planSummary['interviewSessionQuotaCheck'] : [];
    $interviewSessionAutoCreditCheck = is_array($planSummary['interviewSessionAutoCreditCheck'] ?? null) ? $planSummary['interviewSessionAutoCreditCheck'] : [];
    $sessionQuotaNotice = is_array($planSummary['interviewSessionNotice'] ?? null) ? $planSummary['interviewSessionNotice'] : null;
    $sessionAutoCreditReady = (($sessionQuotaNotice['credit_available'] ?? false) === true)
        && (($interviewSessionAutoCreditCheck['allowed'] ?? false) === true);
    $sessionAutoCreditName = (string) ($interviewSessionAutoCreditCheck['credit_name'] ?? '次卡');
    $hasCreditContinuation = (($sessionQuotaNotice['credit_available'] ?? false) === true);
    $formatQuotaSummary = static function (array $quota): string {
        $limit = (int) ($quota['monthly_limit'] ?? 0);
        $used = (int) ($quota['monthly_used'] ?? 0);
        if ($limit === -1) {
            return '不限';
        }

        $remaining = max(0, $limit - $used);

        return "本月已用 {$used}/{$limit}，剩余 {$remaining}";
    };
    $dimensionScores = collect($interview->report['dimension_scores'] ?? [])->values();
    $strengths = collect($interview->report['strengths'] ?? []);
    $improvements = collect($interview->report['improvements'] ?? []);
@endphp
<div class="row">
    <div class="col-12 mb-3">
        <div class="alert alert-info py-2 mb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small">
                    <span class="me-3">
                        <i class="ti ti-chart-donut-2 me-1"></i>
                        AI 面试场次：{{ $formatQuotaSummary($interviewSessionQuota) }}
                    </span>
                    <span class="me-3">
                        <i class="ti ti-file-text me-1"></i>
                        JD 定制题目：
                        <span class="badge {{ $customQuestionsEnabled ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">
                            {{ $customQuestionsEnabled ? '已开通' : '未开通' }}
                        </span>
                    </span>
                    <span>
                        <i class="ti ti-list-numbers me-1"></i>
                        单场最多 {{ $interviewMaxQuestions }} 题
                    </span>
                </div>
                <div class="text-secondary small">
                    详情页与创建页、报告页保持同一套额度口径。
                </div>
            </div>
        </div>
    </div>
    @if($sessionQuotaNotice)
        <div class="col-12 mb-3">
            <div class="alert alert-warning py-2 mb-0">
                @if($sessionQuotaNotice)
                    <div><i class="ti ti-alert-triangle me-1"></i>{{ $sessionQuotaNotice['message'] }}</div>
                    @if($sessionAutoCreditReady)
                        <div class="small text-secondary mt-1">再次开始新面试时，系统会先检查免费次数；若已用完，将弹出次卡确认框，默认优先推荐 AI 面试专用次卡，其次推荐通用次卡。当前推荐：{{ $sessionAutoCreditName }}。</div>
                    @elseif(($sessionQuotaNotice['credit_available'] ?? false) === true)
                        <div class="small text-secondary mt-1">再次开始新面试时，系统会先检查免费次数；若已用完，将弹出次卡确认框，并继续检查 AI 面试专用次卡或通用次卡，确认后再创建面试。</div>
                    @endif
                @endif
                @if($hasCreditContinuation)
                    <div class="mt-2">
                        <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-sm btn-warning">
                            <i class="ti ti-ticket me-1"></i>购买次卡/通用卡
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">面试信息</h3>
                    <span class="badge bg-{{ $interview->status === 'completed' ? 'success' : ($interview->status === 'in_progress' ? 'warning' : 'secondary') }}">
                        {{ $interview->status === 'pending' ? '待开始' : ($interview->status === 'in_progress' ? '进行中' : '已完成') }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="text-secondary small">关联简历</div>
                        @if($interview->resume)
                            <a href="{{ route('user.resumes.show', $interview->resume) }}">
                                {{ $interview->resume->title }}
                            </a>
                        @else
                            <span class="text-secondary">已删除</span>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">公司</div>
                        <div>{{ $interview->company ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">职位</div>
                        <div>{{ $interview->position }}</div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="text-secondary small">面试类型</div>
                        @php
                            $typeLabels = ['technical'=>'技术面试','behavioral'=>'行为面试','mixed'=>'综合面试','sales'=>'销售/BD','management'=>'管理岗','creative'=>'创意/设计','finance'=>'金融/财务','retail'=>'零售/电商','manufacturing'=>'制造/工业','service'=>'服务行业','media'=>'传媒/广告','education'=>'教育/培训','deep'=>'深度面谈'];
                            $typeColors = ['technical'=>'primary','behavioral'=>'info','mixed'=>'warning','sales'=>'success','management'=>'danger','creative'=>'primary','finance'=>'warning','retail'=>'success','manufacturing'=>'secondary','service'=>'info','media'=>'primary','education'=>'info','deep'=>'secondary'];
                            $typeIcons = ['technical'=>'ti ti-code','behavioral'=>'ti ti-users','mixed'=>'ti ti-adjustments','sales'=>'ti ti-chart-bar','management'=>'ti ti-building','creative'=>'ti ti-palette','finance'=>'ti ti-report-money','retail'=>'ti ti-shopping-cart','manufacturing'=>'ti ti-tool','service'=>'ti ti-headset','media'=>'ti ti-speakerphone','education'=>'ti ti-book','deep'=>'ti ti-brain'];
                            $tKey = $interview->type ?? 'mixed';
                        @endphp
                        <span class="badge bg-{{ $typeColors[$tKey] ?? 'secondary' }}-lt text-{{ $typeColors[$tKey] ?? 'secondary' }} rounded-pill">
                            <i class="{{ $typeIcons[$tKey] ?? 'ti ti-message-circle' }} me-1" style="font-size:.7rem;"></i>{{ $typeLabels[$tKey] ?? $tKey }}
                        </span>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">完成进度</div>
                        <div>{{ $interview->answered_count ?? 0 }} / {{ $interview->question_count ?? 0 }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-secondary small">创建时间</div>
                        <div>{{ $interview->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="text-secondary small mb-1">岗位 JD</div>
                    @if(!empty($interview->job_description))
                        <div class="bg-light p-3 rounded" style="white-space: pre-wrap;">{{ $interview->job_description }}</div>
                    @else
                        <span class="badge bg-secondary-lt text-secondary">未填写 JD（仅简历对齐）</span>
                    @endif
                </div>

                @if($interview->overall_score)
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-star me-2"></i>
                            <span>综合评分：<strong>{{ $interview->overall_score }}分</strong></span>
                        </div>
                    </div>
                @endif

                <div class="bg-light p-3 rounded">
                    <div class="text-secondary small mb-1">本场额度消耗摘要</div>
                    <div class="small">
                        <div>创建本场面试消耗 {{ (int) ($usageSummary['sessionQuotaConsumed'] ?? 0) }} 次 AI 面试场次。</div>
                        <div class="mt-1">本场共提交 {{ (int) ($usageSummary['answeredCount'] ?? 0) }} 次回答，累计消耗 {{ (int) ($usageSummary['evaluationQuotaConsumed'] ?? 0) }} 次面试评估额度。</div>
                        <div class="mt-1">本场题目数 {{ (int) ($usageSummary['questionCount'] ?? 0) }}，{{ ($usageSummary['jdEnabled'] ?? false) ? '已启用 JD 对齐' : '未启用 JD 对齐' }}。</div>
                    </div>
                </div>
            </div>
        </div>

        @if($interview->questions->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">问答记录</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($interview->questions as $question)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="fw-medium">
                                        <span class="badge bg-secondary me-2">Q{{ $question->round_no }}</span>
                                        {{ $question->question }}
                                    </div>
                                    @if($question->score)
                                        <span class="badge bg-{{ $question->score >= 80 ? 'success' : ($question->score >= 60 ? 'warning' : 'danger') }}">
                                            {{ $question->score }}分
                                        </span>
                                    @endif
                                </div>
                                @if($question->answer)
                                    <div class="text-secondary ps-4 border-start border-2">
                                        <div class="mb-1"><strong>你的回答：</strong></div>
                                        <div>{{ $question->answer }}</div>
                                    </div>
                                @endif
                                @if($question->feedback)
                                    <div class="mt-2 ps-4">
                                        <div class="text-primary mb-1"><i class="ti ti-message me-1"></i><strong>反馈：</strong></div>
                                        <div class="text-secondary small">{{ $question->feedback['suggestion'] ?? '' }}</div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">操作</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($interview->status === 'pending')
                        <form action="#" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" disabled>
                                <i class="ti ti-player-play me-2"></i>开始面试
                            </button>
                        </form>
                    @elseif($interview->status === 'in_progress')
                        <a href="{{ route('user.interviews.session', $interview) }}" class="btn btn-success w-100">
                            <i class="ti ti-player-play me-2"></i>继续面试
                        </a>
                    @elseif($interview->status === 'completed')
                        <a href="{{ route('user.interviews.report', $interview) }}" class="btn btn-success w-100">
                            <i class="ti ti-file-text me-2"></i>查看面试报告
                        </a>
                    @endif
                    @if($interview->resume)
                        <a href="{{ route('user.resumes.show', $interview->resume) }}" class="btn btn-outline-primary">
                            <i class="ti ti-file-text me-2"></i>查看简历
                        </a>
                    @endif
                    <form action="{{ route('user.interviews.destroy', $interview) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100" data-app-confirm="确定要删除这条面试记录吗？">
                            <i class="ti ti-trash me-2"></i>删除记录
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if($interview->report)
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">报告摘要</h3>
                </div>
                <div class="card-body">
                    @if($dimensionScores->isNotEmpty())
                        <div class="mb-3">
                            <div class="text-secondary small mb-2">能力维度</div>
                            @foreach($dimensionScores as $row)
                                @php
                                    $dimensionScore = (int) ($row['score'] ?? 0);
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ $row['dimension'] ?? '岗位综合能力' }}</span>
                                    <span class="badge bg-{{ $dimensionScore >= 7 ? 'success' : ($dimensionScore >= 5 ? 'warning' : 'danger') }}">{{ $dimensionScore }}/10</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($strengths->isNotEmpty())
                        <div class="mb-3">
                            <div class="text-success small mb-2"><i class="ti ti-trending-up me-1"></i>优势</div>
                            @foreach($strengths as $item)
                                <div class="badge bg-success-light text-success mb-1">{{ $item }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if($improvements->isNotEmpty())
                        <div class="mb-3">
                            <div class="text-warning small mb-2"><i class="ti ti-trending-down me-1"></i>需改进</div>
                            @foreach($improvements as $item)
                                <div class="badge bg-warning-light text-warning mb-1">{{ $item }}</div>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ route('user.interviews.report', $interview) }}" class="btn btn-outline-primary w-100">
                        <i class="ti ti-file-analytics me-2"></i>查看完整报告
                    </a>
                </div>
            </div>
        @endif

        @if($interviewTrend->count() > 1)
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-chart-line me-1"></i>面试趋势</h3>
                </div>
                <div class="card-body">
                    <div class="interview-trend-chart">
                        <svg viewBox="0 0 300 120" class="w-100" style="height:120px">
                            @php
                                $maxScore = $interviewTrend->max('overall_score') ?: 100;
                                $minScore = max(0, $interviewTrend->min('overall_score') - 10);
                                $range = max(1, $maxScore - $minScore);
                                $step = 280 / max(1, $interviewTrend->count() - 1);
                                $points = [];
                                foreach ($interviewTrend as $i => $item) {
                                    $x = 10 + $i * $step;
                                    $y = 110 - (($item->overall_score - $minScore) / $range) * 90;
                                    $points[] = "{$x},{$y}";
                                }
                                $polyline = implode(' ', $points);
                            @endphp
                            {{-- 网格线 --}}
                            <line x1="10" y1="20" x2="290" y2="20" stroke="#e9eef5" stroke-width="0.5"/>
                            <line x1="10" y1="55" x2="290" y2="55" stroke="#e9eef5" stroke-width="0.5"/>
                            <line x1="10" y1="90" x2="290" y2="90" stroke="#e9eef5" stroke-width="0.5"/>
                            {{-- 趋势线 --}}
                            <polyline points="{{ $polyline }}" fill="none" stroke="#4c6fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            {{-- 数据点 --}}
                            @foreach ($interviewTrend as $i => $item)
                                @php
                                    $x = 10 + $i * $step;
                                    $y = 110 - (($item->overall_score - $minScore) / $range) * 90;
                                    $isCurrent = $item->id === $interview->id;
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $isCurrent ? 5 : 3 }}" fill="{{ $isCurrent ? '#4c6fff' : '#a0b4ff' }}" stroke="#fff" stroke-width="1.5"/>
                                @if($isCurrent)
                                    <text x="{{ $x }}" y="{{ $y - 8 }}" text-anchor="middle" fill="#4c6fff" font-size="10" font-weight="bold">{{ $item->overall_score }}</text>
                                @endif
                            @endforeach
                        </svg>
                    </div>
                    <div class="d-flex justify-content-between mt-2 small text-secondary">
                        <span>{{ $interviewTrend->first()->created_at->format('m/d') }}</span>
                        <span>{{ $interviewTrend->last()->created_at->format('m/d') }}</span>
                    </div>
                    <div class="text-center mt-1">
                        <span class="badge bg-primary-lt text-primary">共 {{ $interviewTrend->count() }} 场</span>
                        @if($interviewTrend->count() >= 2)
                            @php
                                $latest = $interviewTrend->last()->overall_score;
                                $earliest = $interviewTrend->first()->overall_score;
                                $diff = $latest - $earliest;
                            @endphp
                            <span class="badge bg-{{ $diff >= 0 ? 'success' : 'danger' }}-lt text-{{ $diff >= 0 ? 'success' : 'danger' }}">
                                {{ $diff >= 0 ? '+' : '' }}{{ $diff }}分
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
