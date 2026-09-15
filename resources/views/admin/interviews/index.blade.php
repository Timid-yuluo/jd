@extends('layouts.admin')

@section('title', '面试记录')
@section('page-pretitle', '业务中心')
@section('page-title', '模拟面试列表')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-message-chatbot"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">面试总数</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-plus"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['today']) }}</div><div class="text-secondary">今日新增</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-check"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['completed']) }}</div><div class="text-secondary">已完成</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-orange text-white avatar"><i class="ti ti-star"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['pending_score']) }}</div><div class="text-secondary">待评分</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.interviews.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">关键词</label>
                        <input type="text" class="form-control" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="岗位/公司/用户">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">状态</label>
                        <select class="form-select" name="status">
                            <option value="">全部</option>
                            <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>进行中</option>
                            <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>已完成</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">类型</label>
                        <select class="form-select" name="type">
                            <option value="">全部</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">评分状态</label>
                        <label class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="scoring_pending" value="1" @checked(($filters['scoring_pending'] ?? false))>
                            <span class="form-check-label">仅看待评分</span>
                        </label>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">JD模式</label>
                        <select class="form-select" name="jd_mode">
                            <option value="">全部</option>
                            <option value="with_jd" @selected(($filters['jd_mode'] ?? '') === 'with_jd')>仅含JD</option>
                            <option value="without_jd" @selected(($filters['jd_mode'] ?? '') === 'without_jd')>未填JD</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">JD匹配度</label>
                        <select class="form-select" name="jd_match_band">
                            <option value="">全部区间</option>
                            <option value="high" @selected(($filters['jd_match_band'] ?? '') === 'high')>高 (8-10)</option>
                            <option value="medium" @selected(($filters['jd_match_band'] ?? '') === 'medium')>中 (5-7)</option>
                            <option value="low" @selected(($filters['jd_match_band'] ?? '') === 'low')>低 (0-4)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">AI提前结束</label>
                        <select class="form-select" name="ai_termination">
                            <option value="">全部</option>
                            <option value="terminated" @selected(($filters['ai_termination'] ?? '') === 'terminated')>仅AI终止</option>
                            <option value="normal" @selected(($filters['ai_termination'] ?? '') === 'normal')>非AI终止</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-primary">筛选</button>
                        <a href="{{ route('admin.interviews.index') }}" class="btn btn-outline-secondary">重置</a>
                        <a href="{{ route('admin.interviews.export', request()->only(['status','keyword'])) }}" class="btn btn-outline-success"><i class="ti ti-download me-1"></i>导出</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">系统面试记录</h3>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>用户</th>
                            <th>岗位/公司</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>进度</th>
                            <th>得分</th>
                            <th>JD匹配</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($interviews as $interview)
                        <tr>
                            <td><span class="text-secondary">{{ $interview->id }}</span></td>
                            <td>{{ $interview->user?->name ?? '未知用户' }}</td>
                            <td>
                                <div>{{ $interview->position }}</div>
                                <div class="text-secondary small">{{ $interview->company ?? '-' }}</div>
                                @if(!empty($interview->job_description))
                                    <div class="text-primary small">JD对齐</div>
                                @endif
                            </td>
                            <td>{{ $interview->type }}</td>
                            <td>
                                @if($interview->status === 'completed')
                                    <span class="badge bg-green text-green-fg">已完成</span>
                                @else
                                    <span class="badge bg-yellow text-yellow-fg">进行中</span>
                                @endif
                                @if(data_get($interview->report, 'early_termination.enabled_by_ai'))
                                    <div class="mt-1">
                                        <span class="badge bg-red-lt text-red">AI提前结束</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $interview->answered_count }} / {{ $interview->question_count }}
                                @if(($interview->pending_scoring_count ?? 0) > 0)
                                    <div class="text-yellow small mt-1">
                                        待评分 {{ $interview->pending_scoring_count }} 题
                                    </div>
                                @endif
                                @php $terminationReason = (string) data_get($interview->report, 'early_termination.reason', ''); @endphp
                                @if($terminationReason !== '')
                                    <div class="text-danger small mt-1">终止原因：{{ $terminationReason }}</div>
                                @endif
                            </td>
                            <td>
                                @if($interview->overall_score)
                                    <span class="text-green fw-bold">{{ $interview->overall_score }}</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $matchScore = (int) data_get($interview->report, 'jd_alignment.match_score', 0);
                                    $hitRate = (int) data_get($interview->report, 'jd_alignment.keyword_hit_rate', 0);
                                    $jdEnabled = (bool) data_get($interview->report, 'jd_alignment.enabled', false);
                                @endphp
                                @if($jdEnabled)
                                    <div class="fw-bold text-{{ $matchScore >= 8 ? 'green' : ($matchScore >= 5 ? 'orange' : 'red') }}">{{ $matchScore }}/10</div>
                                    <div class="text-secondary small">命中 {{ $hitRate }}%</div>
                                @elseif(!empty($interview->job_description))
                                    <span class="text-secondary small">待生成</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $interview->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.interviews.show', $interview) }}" class="btn btn-sm btn-outline-primary">查看</a>
                                <form action="{{ route('admin.interviews.destroy', $interview) }}" method="POST" class="d-inline" data-app-confirm='确定要删除此面试记录吗？'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary">暂无数据</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($interviews->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $interviews->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
