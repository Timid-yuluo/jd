@extends('layouts.user')

@section('title', '回收站 - 我的简历')
@section('page-pretitle', '简历管理')
@section('page-title', '回收站')

@section('page-actions')
<a href="{{ route('user.resumes.index') }}" class="btn btn-ghost-secondary">
    <i class="ti ti-arrow-left me-2"></i>返回简历列表
</a>
@endsection

@section('content')
<div class="row row-cards">
    @if($trashedResumes->isEmpty())
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-trash-off text-secondary mb-3" style="font-size:3rem"></i>
                <h3 class="text-secondary">回收站为空</h3>
                <p class="text-secondary mb-0">暂无已删除的简历</p>
            </div>
        </div>
    </div>
    @else
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ti ti-trash me-2 text-muted"></i>
                    已删除的简历（{{ $trashedResumes->total() }} 份）
                </div>
                <div class="text-secondary small">删除后 30 天内可恢复，超期将自动清理</div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>简历名称</th>
                            <th>目标岗位</th>
                            <th>删除时间</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trashedResumes as $resume)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $resume->title }}</div>
                            </td>
                            <td class="text-secondary">
                                {{ $resume->target_job ?: '--' }}
                            </td>
                            <td class="text-secondary small">
                                {{ $resume->deleted_at?->diffForHumans() }}
                                <span class="text-muted">（{{ $resume->deleted_at?->format('Y-m-d H:i') }}）</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form action="{{ route('user.resumes.restore', $resume->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost-primary btn-sm">
                                            <i class="ti ti-restore me-1"></i>恢复
                                        </button>
                                    </form>
                                    <form action="{{ route('user.resumes.force-delete', $resume->id) }}" method="POST" data-app-confirm="确定要永久删除该简历吗？此操作不可恢复。">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost-danger btn-sm">
                                            <i class="ti ti-trash-x me-1"></i>彻底删除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($trashedResumes->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $trashedResumes->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
