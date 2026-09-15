@extends('layouts.user')

@section('title', '测评历史')
@section('page-pretitle', 'AI 测评')
@section('page-title', '测评历史记录')

@section('page-actions')
    <a href="{{ route('user.assessments.index') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-plus me-1"></i>新测评
    </a>
@endsection

@section('content')
@if($assessments->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-clipboard-data fs-1 text-secondary"></i>
        <p class="text-secondary mt-2">暂无测评记录</p>
        <a href="{{ route('user.assessments.index') }}" class="btn btn-outline-primary mt-2">
            <i class="ti ti-plus me-1"></i>开始第一次测评
        </a>
    </div>
</div>
@else
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>类型</th>
                    <th>结果代码</th>
                    <th>结果标签</th>
                    <th>AI 解读</th>
                    <th>时间</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $assessment)
                @php
                    $typeLabel = match($assessment->test_type) {
                        'mbti' => 'MBTI',
                        'holland' => '霍兰德',
                        'disc' => 'DISC',
                        default => $assessment->test_type,
                    };
                    $hasAi = !empty($assessment->ai_analysis);
                @endphp
                <tr>
                    <td><span class="badge bg-primary-lt">{{ $typeLabel }}</span></td>
                    <td><strong>{{ $assessment->result_code }}</strong></td>
                    <td>{{ $assessment->result_label }}</td>
                    <td>
                        @if($hasAi)
                        <span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>已生成</span>
                        @else
                        <span class="badge bg-secondary-lt">未生成</span>
                        @endif
                    </td>
                    <td class="text-secondary small">{{ $assessment->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('user.assessments.result', $assessment) }}" class="btn btn-sm btn-outline-primary">
                            查看报告
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $assessments->links() }}
</div>
@endif
@endsection
