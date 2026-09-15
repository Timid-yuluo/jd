@extends('layouts.user')

@section('title', $config['title'] ?? '测评答题')
@section('page-pretitle', 'AI 测评')
@section('page-title', $config['title'] ?? '测评')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">{{ $config['title'] ?? '' }}</h3>
                    <div class="text-secondary small">
                        <i class="ti ti-clock me-1"></i>约 {{ $config['estimated_minutes'] ?? 10 }} 分钟
                    </div>
                </div>
                <p class="text-secondary mt-2 mb-0">{{ $config['description'] ?? '' }}</p>
            </div>
            <div class="card-body">
                <!-- 进度条 -->
                <div class="progress progress-sm mb-4">
                    <div id="progressBar" class="progress-bar bg-primary" style="width: 0%"></div>
                </div>
                <div class="d-flex justify-content-between text-secondary small mb-4">
                    <span>第 <span id="currentPage">1</span> / <span id="totalPages">1</span> 页</span>
                    <span>已答 <span id="answeredCount">0</span> 题</span>
                </div>

                <!-- 题目容器 -->
                <div id="questionsContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-secondary mt-2">题目加载中...</p>
                    </div>
                </div>

                <!-- 导航按钮 -->
                <div class="d-flex justify-content-between mt-4">
                    <button id="prevBtn" class="btn btn-outline-secondary" disabled>
                        <i class="ti ti-arrow-left me-1"></i>上一页
                    </button>
                    <button id="nextBtn" class="btn btn-primary">
                        下一页<i class="ti ti-arrow-right ms-1"></i>
                    </button>
                    <button id="submitBtn" class="btn btn-success" style="display:none">
                        <i class="ti ti-check me-1"></i>提交测评
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
window.assessmentConfig = {
    type: '{{ $type }}',
    fetchUrl: '{{ route('user.assessments.questions', $type) }}',
    submitUrl: '{{ route('user.assessments.submit', $type) }}',
    csrfToken: '{{ csrf_token() }}',
    totalQuestions: {{ count($config['questions'] ?? []) }},
};
</script>
<script src="{{ asset('js/pages/assessment-test.js') }}?v={{ @filemtime(public_path('js/pages/assessment-test.js')) ?: time() }}"></script>
@endpush
@endsection
