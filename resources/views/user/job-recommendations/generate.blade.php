@extends('layouts.user')

@section('title', '生成推荐中')
@section('page-pretitle', '岗位推荐')
@section('page-title', '正在生成推荐')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body text-center py-5">
                <div id="progress-status" data-status="{{ $progress['status'] }}" class="mb-4">
                    @if($progress['status'] === 'queued')
                        <i class="ti ti-loader ti-spin fs-1 text-primary"></i>
                        <h3 class="mt-3">任务排队中</h3>
                        <p class="text-secondary">AI 正在为你匹配最佳岗位，请稍候...</p>
                    @elseif($progress['status'] === 'processing')
                        <i class="ti ti-loader ti-spin fs-1 text-primary"></i>
                        <h3 class="mt-3">正在分析匹配度</h3>
                        <p class="text-secondary">已处理 {{ $progress['processed'] }} / {{ $progress['total'] }} 个岗位</p>
                        <div class="progress progress-sm mt-3" style="max-width: 300px; margin: 0 auto;">
                            <div class="progress-bar bg-primary" id="progress-bar"
                                 style="width: {{ $progress['total'] > 0 ? ($progress['processed'] / $progress['total'] * 100) : 0 }}%"></div>
                        </div>
                    @elseif($progress['status'] === 'completed')
                        <i class="ti ti-check fs-1 text-success"></i>
                        <h3 class="mt-3 text-success">推荐生成完成</h3>
                        <p class="text-secondary">共生成 {{ $progress['total'] }} 条推荐</p>
                        <a href="{{ route('user.recommendations.index') }}" class="btn btn-primary mt-2">
                            <i class="ti ti-list me-1"></i>查看推荐
                        </a>
                    @elseif($progress['status'] === 'failed')
                        <i class="ti ti-alert-circle fs-1 text-danger"></i>
                        <h3 class="mt-3 text-danger">生成失败</h3>
                        {{-- #24 失败反馈：显示具体错误原因 --}}
                        @if(!empty($progress['error']))
                            <div class="alert alert-danger mt-3 text-start">
                                <i class="ti ti-bug me-1"></i>
                                <strong>错误详情：</strong>
                                <pre class="mb-0 mt-2 small">{{ $progress['error'] }}</pre>
                            </div>
                        @else
                            <p class="text-secondary">请稍后重试或联系客服</p>
                        @endif
                        <div class="mt-3">
                            <a href="{{ route('user.recommendations.index') }}" class="btn btn-outline-primary me-2">
                                <i class="ti ti-arrow-left me-1"></i>返回列表
                            </a>
                            <form action="{{ route('user.recommendations.generate') }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-refresh me-1"></i>重新生成
                                </button>
                            </form>
                        </div>
                    @else
                        <i class="ti ti-sparkles fs-1 text-secondary"></i>
                        <h3 class="mt-3">准备中</h3>
                        <p class="text-secondary">请稍候...</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<span id="progress-config" data-url="{{ route('user.recommendations.progress') }}"></span>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/recommendation-progress.js') }}?v={{ @filemtime(public_path('js/pages/recommendation-progress.js')) ?: time() }}"></script>
@endpush
