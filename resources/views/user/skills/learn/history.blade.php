@extends('layouts.user')

@section('title', '学习路径历史')
@section('page-pretitle', '技能管理')
@section('page-title', '学习路径历史')

@section('page-actions')
    <a href="{{ route('user.skills.learn.index') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-plus me-1"></i>新分析
    </a>
@endsection

@section('content')
@if($paths->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-route fs-1 text-secondary"></i>
        <p class="text-secondary mt-2">暂无学习路径记录</p>
        <a href="{{ route('user.skills.learn.index') }}" class="btn btn-outline-primary mt-2">
            <i class="ti ti-sparkles me-1"></i>开始第一次分析
        </a>
    </div>
</div>
@else
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>目标岗位</th>
                    <th>差距数</th>
                    <th>阶段数</th>
                    <th>预计时长</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($paths as $path)
                <tr>
                    <td>{{ $path->target_job }}</td>
                    <td><span class="badge bg-warning-lt">{{ $path->gap_count }}</span></td>
                    <td>{{ $path->phase_count }}</td>
                    <td>{{ $path->total_duration_weeks }} 周</td>
                    <td>
                        @if($path->status === 'active')
                        <span class="badge bg-success-lt">活跃</span>
                        @else
                        <span class="badge bg-secondary-lt">已归档</span>
                        @endif
                    </td>
                    <td class="text-secondary small">{{ $path->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('user.skills.learn.show', $path) }}" class="btn btn-sm btn-outline-primary">
                            查看
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $paths->links() }}
</div>
@endif
@endsection
