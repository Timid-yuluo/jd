@extends('layouts.admin')

@section('title', '面试详情')
@section('page-pretitle', '核心业务')
@section('page-title', '面试详情')

@section('page-actions')
<a href="{{ route('admin.interviews.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>返回列表
</a>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        {{-- JD 匹配度分析 --}}
        @php
            $jdAlignment = is_array($interview->report ?? null) ? ($interview->report['jd_alignment'] ?? []) : [];
            $jdEnabled = (bool) ($jdAlignment['enabled'] ?? false);
            $matchScore = (int) ($jdAlignment['match_score'] ?? 0);
            $hitRate = (int) ($jdAlignment['keyword_hit_rate'] ?? 0);
            $coverageScore = (int) ($jdAlignment['requirements_coverage_score'] ?? 0);
            $roleFocusScore = (int) ($jdAlignment['role_focus_score'] ?? 0);
            $evidenceScore = (int) ($jdAlignment['evidence_score'] ?? 0);
            $matchedKeywords = is_array($jdAlignment['matched_keywords'] ?? null) ? $jdAlignment['matched_keywords'] : [];
            $missingKeywords = is_array($jdAlignment['missing_keywords'] ?? null) ? $jdAlignment['missing_keywords'] : [];
        @endphp
        @if($jdEnabled)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-target me-1"></i>JD 匹配度分析</h3>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-secondary small">匹配总分</div>
                                <div class="font-weight-medium text-{{ $matchScore >= 8 ? 'success' : ($matchScore >= 5 ? 'warning' : 'danger') }}">{{ $matchScore }}/10</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-secondary small">关键词命中率</div>
                                <div class="font-weight-medium">{{ $hitRate }}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-secondary small">要求覆盖</div>
                                <div class="font-weight-medium">{{ $coverageScore }}/10</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-secondary small">岗位聚焦</div>
                                <div class="font-weight-medium">{{ $roleFocusScore }}/10</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-secondary small">量化证据</div>
                                <div class="font-weight-medium">{{ $evidenceScore }}/10</div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(!empty($matchedKeywords))
                    <div class="mb-2">
                        <span class="text-success small me-2">已覆盖关键词</span>
                        @foreach($matchedKeywords as $keyword)
                            <span class="badge bg-success-lt text-success mb-1">{{ $keyword }}</span>
                        @endforeach
                    </div>
                @endif
                @if(!empty($missingKeywords))
                    <div>
                        <span class="text-warning small me-2">待补强关键词</span>
                        @foreach($missingKeywords as $keyword)
                            <span class="badge bg-warning-lt text-warning mb-1">{{ $keyword }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- 逐题问答 --}}
        @if($interview->questions->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-message-circle me-1"></i>逐题问答 ({{ $interview->questions->count() }} 题)</h3>
                <div class="card-actions">
                    <form action="{{ route('admin.interviews.retry-pending-evaluation', $interview) }}" method="POST" data-app-confirm='确认重试所有待评分题目？'>
                        @csrf
                        <button class="btn btn-outline-primary btn-sm" type="submit"><i class="ti ti-refresh me-1"></i>重试全部待评分</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @foreach($interview->questions as $q)
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-blue-lt me-2">第 {{ $q->round_no }} 题</span>
                            @if($q->score)
                                <span class="badge {{ $q->score >= 70 ? 'bg-green-lt' : ($q->score >= 40 ? 'bg-yellow-lt' : 'bg-red-lt') }}">
                                    {{ $q->score }} 分
                                </span>
                            @elseif($q->answer)
                                <span class="badge bg-yellow-lt">待评分</span>
                            @else
                                <span class="badge bg-secondary-lt">未回答</span>
                            @endif
                        </div>
                        @if($q->answer)
                            <div>
                                <form action="{{ route('admin.interviews.retry-question-evaluation', [$interview, $q]) }}" method="POST" data-app-confirm='确认重试该题评分？'>
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm">重试评分</button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-secondary small mb-1">问题</div>
                            <div>{{ $q->question }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-secondary small mb-1">回答</div>
                            <div class="bg-light p-2 rounded" style="white-space: pre-wrap;">{{ $q->answer ?? '未回答' }}</div>
                        </div>
                        @if($q->feedback)
                        <div>
                            <div class="text-secondary small mb-1">AI 评价</div>
                            @php
                                $feedback = is_array($q->feedback) ? $q->feedback : (is_string($q->feedback) ? json_decode($q->feedback, true) : []);
                            @endphp
                            @if(is_array($feedback))
                                @foreach($feedback as $key => $value)
                                    @if(is_string($value))
                                    <div class="mb-1">
                                        <strong>{{ $key }}:</strong> {{ $value }}
                                    </div>
                                    @endif
                                @endforeach
                            @else
                                <div class="bg-azure-lt p-2 rounded">{{ is_string($q->feedback) ? $q->feedback : json_encode($q->feedback, JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- 基础信息 --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">基础信息</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">岗位</div>
                        <div class="datagrid-content">{{ $interview->position }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">公司</div>
                        <div class="datagrid-content">{{ $interview->company ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">面试类型</div>
                        <div class="datagrid-content">{{ $interview->type }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">当前状态</div>
                        <div class="datagrid-content">
                            @if($interview->status === 'completed')
                                <span class="badge bg-green text-green-fg">已完成</span>
                            @else
                                <span class="badge bg-yellow text-yellow-fg">进行中</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">进度</div>
                        <div class="datagrid-content">{{ $interview->answered_count }} / {{ $interview->question_count }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">综合得分</div>
                        <div class="datagrid-content">
                            @if($interview->overall_score)
                                <span class="text-green fw-bold">{{ $interview->overall_score }}</span>
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">JD 模式</div>
                        <div class="datagrid-content">
                            @if(!empty($interview->job_description))
                                <span class="badge bg-primary-lt text-primary">已启用</span>
                            @else
                                <span class="badge bg-secondary-lt text-secondary">未填写</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">创建时间</div>
                        <div class="datagrid-content">{{ $interview->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 所属用户 --}}
        @if($interview->user)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">所属用户</h3>
            </div>
            <div class="card-body text-center">
                <img src="{{ \Illuminate\Support\Str::avatarSvg($interview->user->email, 64) }}" class="avatar avatar-lg mb-2" alt="">
                <div class="font-weight-medium">{{ $interview->user->name }}</div>
                <div class="text-secondary small">{{ $interview->user->email }}</div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.users.show', $interview->user) }}" class="btn btn-outline-primary w-100 btn-sm">
                    <i class="ti ti-user me-1"></i>查看用户详情
                </a>
            </div>
        </div>
        @endif

        {{-- JD 内容 --}}
        @if(!empty($interview->job_description))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">岗位 JD</h3>
            </div>
            <div class="card-body">
                <div class="bg-light p-2 rounded" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;">{{ $interview->job_description }}</div>
            </div>
        </div>
        @endif

        {{-- 关联简历 --}}
        @if($interview->resume)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">关联简历</h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.resumes.show', $interview->resume) }}" class="list-group-item list-group-item-action">
                    <i class="ti ti-file-text me-2"></i>{{ $interview->resume->title }}
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
