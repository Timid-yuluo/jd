@extends('layouts.admin')

@section('title', '系统日志')
@section('page-pretitle', '系统运维')
@section('page-title', '系统日志')

@section('content')
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-file-text me-1"></i>日志内容</h3>
                <div class="card-actions">
                    <span class="text-secondary small me-3">显示最近 500 行</span>
                    <a href="{{ route('admin.system-ops.logs') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-refresh me-1"></i>刷新
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-sm table-nowrap">
                        <thead>
                            <tr>
                                <th style="width: 80px;">级别</th>
                                <th>消息</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>
                                        @switch($log['level'])
                                            @case('error')
                                                <span class="badge bg-red-lt text-red">ERROR</span>
                                                @break
                                            @case('warning')
                                                <span class="badge bg-yellow-lt text-yellow">WARN</span>
                                                @break
                                            @case('debug')
                                                <span class="badge bg-purple-lt text-purple">DEBUG</span>
                                                @break
                                            @default
                                                <span class="badge bg-blue-lt text-blue">INFO</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <code class="text-wrap" style="font-size: 0.75rem; white-space: pre-wrap;">{{ $log['message'] }}</code>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-secondary py-4">暂无日志记录</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
