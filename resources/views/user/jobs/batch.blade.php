@extends('layouts.user')

@section('title', '批量岗位分析')

@section('page-pretitle', '智能分析')
@section('page-title', '批量岗位分析')

@section('page-actions')
<a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i>单个分析
</a>
@endsection

@section('content')
@if(isset($quotaCheck))
<div class="mb-3">
    @if($quotaCheck['allowed'])
        <div class="alert alert-success py-2 d-flex align-items-center gap-2 flex-wrap">
            <i class="ti ti-check me-1"></i><span>{{ $creditsHint }}</span>
            <div class="ms-auto d-flex gap-1">
                <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-sm btn-outline-success">购买次卡</a>
                <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-outline-primary">升级套餐</a>
            </div>
        </div>
    @else
        <div class="alert alert-warning py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    @if(!empty($availableCredits))
                        <div><i class="ti ti-ticket me-1"></i>批量分析会消耗多次配额。可用次卡：<strong>{{ count($availableCredits) }}</strong> 张。</div>
                    @else
                        <div><i class="ti ti-alert-triangle me-1"></i>配额不足且无可用次卡。</div>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-sm btn-outline-primary">购买次卡</a>
                    <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-primary">升级套餐</a>
                </div>
            </div>
        </div>
    @endif
</div>
@endif
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">批量粘贴岗位描述</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('user.jobs.batch.submit') }}" method="POST" id="batchForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">选择简历</label>
                        <select name="resume_id" class="form-select">
                            <option value="">自动使用最近更新的简历</option>
                            @foreach($resumes as $resume)
                                <option value="{{ $resume->id }}">{{ $resume->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">岗位描述 (JD)</label>
                        <textarea name="job_descriptions" class="form-control" rows="16" placeholder="粘贴多个岗位描述，每段之间用 --- 分隔。&#10;&#10;示例：&#10;岗位1：Java后端工程师...&#10;---&#10;岗位2：前端开发工程师...&#10;---&#10;岗位3：全栈工程师..." required></textarea>
                        <div class="form-hint mt-1">每段 JD 至少 50 字，用 <code>---</code> 分隔不同岗位。</div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="batchSubmitBtn">
                        <i class="ti ti-sparkles me-2"></i>开始批量分析
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">使用说明</h3>
            </div>
            <div class="card-body text-secondary small">
                <p><i class="ti ti-info-circle me-1"></i>批量分析适合海投场景：</p>
                <ul class="mb-0">
                    <li class="mb-1">粘贴多个 JD，用 <code>---</code> 分隔</li>
                    <li class="mb-1">系统逐个分析并保存结果</li>
                    <li class="mb-1">分析完成后可查看各岗位的匹配分数</li>
                    <li class="mb-1">每段 JD 至少 50 字</li>
                </ul>
            </div>
        </div>
        @if($recentBatches->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">最近批量任务</h3>
            </div>
            <div class="list-group list-group-flush">
                @foreach($recentBatches as $batch)
                    <div class="list-group-item" data-batch-id="{{ $batch->id }}" data-batch-status="{{ $batch->status }}">
                        <div class="d-flex justify-content-between">
                            <span class="small">{{ $batch->resume?->title ?? '自动选择' }}</span>
                            <span class="badge {{ $batch->status === 'completed' ? 'bg-success-lt' : ($batch->status === 'failed' ? 'bg-danger-lt' : 'bg-warning-lt') }}">
                                <span class="batch-progress-text">{{ $batch->completed }}/{{ $batch->total }}</span>
                            </span>
                        </div>
                        @if($batch->status === 'processing')
                            <div class="progress mt-1" style="height:3px;">
                                <div class="progress-bar bg-primary" style="width:{{ $batch->total > 0 ? round($batch->completed/$batch->total*100) : 0 }}%"></div>
                            </div>
                        @endif
                        <div class="text-secondary small">{{ $batch->created_at?->format('Y-m-d H:i') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    var processingBatches = document.querySelectorAll('[data-batch-status="processing"]');
    processingBatches.forEach(function(el) {
        var batchId = el.dataset.batchId;
        var progressUrl = '/user/jobs/batch/' + batchId + '/progress';
        var interval = setInterval(function() {
            fetch(progressUrl, {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json();})
                .then(function(d){
                    if (d.success) {
                        var pct = d.data.progress_percent || 0;
                        var bar = el.querySelector('.progress-bar');
                        var text = el.querySelector('.batch-progress-text');
                        if (bar) bar.style.width = pct + '%';
                        if (text) text.textContent = d.data.completed + '/' + d.data.total;
                        if (d.data.status === 'completed' || d.data.status === 'failed') {
                            clearInterval(interval);
                            setTimeout(function(){ location.reload(); }, 1000);
                        }
                    }
                })
                .catch(function(){});
        }, 3000);
    });
});
</script>
@endpush
