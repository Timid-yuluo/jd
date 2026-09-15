@extends('layouts.user')

@section('title', '次卡使用记录')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.membership.credits') }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回次卡</a>
                <h2 class="page-title">次卡使用记录</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>次卡</th>
                            <th>场景</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($log->credit)
                                        <span class="fw-medium">{{ $log->credit->packName() ?: ($log->credit->isUniversal() ? '通用次卡' : '专用次卡') }}</span>
                                    @else
                                        <span class="text-secondary">已删除</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $scenarioLabels = [
                                            'resume.optimize' => 'AI 全文优化',
                                            'resume.optimize-section' => '分段优化',
                                            'resume.keywords' => '关键词提取',
                                            'resume.import-draft' => 'AI 导入简历',
                                            'resume.ats_score' => 'ATS 评分',
                                            'interview.generate-question' => '面试题目',
                                            'interview.evaluation' => '面试评估',
                                            'job.match' => '岗位匹配',
                                            'job.match-analysis' => '匹配分析',
                                        ];
                                    @endphp
                                    <span class="badge bg-secondary-lt">{{ $scenarioLabels[$log->scenario] ?? $log->scenario }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-4">暂无使用记录</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
