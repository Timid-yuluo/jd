@extends('layouts.user')

@section('title', '谈判历史')
@section('page-pretitle', '薪资助手')
@section('page-title', '谈判历史记录')

@section('page-actions')
    <a href="{{ route('user.salary.negotiate') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-plus me-1"></i>新谈判
    </a>
@endsection

@section('content')
@if($sessions->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-history fs-1 text-secondary"></i>
        <p class="text-secondary mt-2">暂无谈判记录</p>
        <a href="{{ route('user.salary.negotiate') }}" class="btn btn-outline-primary mt-2">
            <i class="ti ti-sparkles me-1"></i>开始第一次谈判
        </a>
    </div>
</div>
@else
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>岗位</th>
                    <th>公司</th>
                    <th>当前</th>
                    <th>期望</th>
                    <th>涨幅</th>
                    <th>时间</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                @php
                    $increase = $session->current_salary > 0
                        ? round(($session->target_salary - $session->current_salary) / $session->current_salary * 100, 1)
                        : 0;
                @endphp
                <tr>
                    <td>{{ $session->job_title }}</td>
                    <td>{{ $session->company ?? '-' }}</td>
                    <td>{{ number_format($session->current_salary) }}</td>
                    <td>{{ number_format($session->target_salary) }}</td>
                    <td>
                        <span class="badge {{ $increase > 30 ? 'bg-danger-lt' : 'bg-primary-lt' }}">+{{ $increase }}%</span>
                    </td>
                    <td class="text-secondary small">{{ $session->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('user.salary.negotiate.result', $session) }}" class="btn btn-sm btn-outline-primary">
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
    {{ $sessions->links() }}
</div>
@endif
@endsection
