@extends('layouts.user')

@section('title', '反馈详情')
@section('page-title', '反馈详情')

@section('content')
<div
    data-route-feedback-reply-0="{{ route('feedback.reply', $feedback) }}"
    data-auth-name="{{ auth()->user()->name }}"
>
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-auto">
            <a href="{{ route('feedback.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> 返回列表
            </a>
        </div>
        <div class="col">
            <h2 class="page-title">反馈 #{{ $feedback->id }}</h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- 反馈详情 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ $feedback->title }}</h3>
                <div class="card-actions">
                    <span class="badge bg-{{ $feedback->getStatusColor() }}-lt me-1">{{ $feedback->getStatusLabel() }}</span>
                    <span class="badge bg-{{ $feedback->getPriorityColor() }}-lt me-1">{{ $feedback->getPriorityLabel() }}</span>
                    <span class="badge bg-{{ $feedback->getAdoptionColor() }}-lt">{{ $feedback->getAdoptionLabel() }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-{{ $feedback->category === 'bug' ? 'red' : ($feedback->category === 'suggestion' ? 'blue' : ($feedback->category === 'ux' ? 'yellow' : 'secondary')) }}-lt">{{ $feedback->getCategoryLabel() }}</span>
                </div>
                <div class="mb-3" style="white-space: pre-wrap;">{{ $feedback->content }}</div>

                @if($feedback->page_url)
                <div class="text-secondary small mt-3">
                    <i class="ti ti-link me-1"></i>反馈页面：
                    <a href="{{ $feedback->page_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-reset">{{ $feedback->page_name ?: $feedback->page_url }}</a>
                </div>
                @endif
            </div>
        </div>

        @if($feedback->isAdopted() || $feedback->reward)
        <div class="card mb-4 border-success">
            <div class="card-header">
                <h3 class="card-title">采纳结果</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-{{ $feedback->getAdoptionColor() }}-lt">{{ $feedback->getAdoptionLabel() }}</span>
                    @if($feedback->reward)
                        <span class="badge bg-success-lt">奖励已发放</span>
                    @endif
                </div>

                @if($feedback->adoption_note)
                    <div class="mb-3" style="white-space: pre-wrap;">{{ $feedback->adoption_note }}</div>
                @endif

                @if($feedback->reward)
                    <div class="alert alert-success mb-0">
                        <div class="fw-semibold mb-1">奖励内容</div>
                        <div class="small">次卡类型：{{ \App\Models\UserCredit::quotaLabel($feedback->reward->quota_key) }}</div>
                        <div class="small">奖励次数：{{ $feedback->reward->credits }}</div>
                        <div class="small">发放时间：{{ $feedback->reward->granted_at?->format('Y-m-d H:i') }}</div>
                        @if($feedback->reward->validity_days)
                            <div class="small">有效期：{{ $feedback->reward->validity_days }} 天</div>
                        @else
                            <div class="small">有效期：长期有效</div>
                        @endif
                        @if($feedback->reward->reason)
                            <div class="small mt-1">奖励说明：{{ $feedback->reward->reason }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- 沟通记录 --}}
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">沟通记录 ({{ $feedback->replies->count() }})</h3>
            </div>
            <div class="card-body" id="repliesContainer">
                @foreach($feedback->replies as $reply)
                <div class="d-flex mb-3 {{ $reply->is_admin ? '' : 'flex-row-reverse' }}">
                    <div class="avatar avatar-sm me-3 {{ $reply->is_admin ? '' : 'ms-3 me-0' }}"
                         style="background: {{ $reply->is_admin ? '#206bc4' : '#0EA5E9' }}; color: #fff; font-weight: 600; font-size: 0.75rem; flex-shrink: 0;">
                        {{ mb_strtoupper(mb_substr($reply->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="{{ $reply->is_admin ? 'me-auto' : 'ms-auto' }}" style="max-width: 75%;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-medium small">{{ $reply->user->name ?? '未知' }}</span>
                            @if($reply->is_admin)
                                <span class="badge bg-blue-lt">管理员</span>
                            @else
                                <span class="badge bg-info-lt">我</span>
                            @endif
                            <span class="text-secondary small">{{ $reply->created_at->format('m-d H:i') }}</span>
                        </div>
                        <div class="p-3 rounded {{ $reply->is_admin ? 'bg-primary-lt' : 'bg-info-lt' }}" style="white-space: pre-wrap;">{{ $reply->content }}</div>
                    </div>
                </div>
                @endforeach

                @if($feedback->replies->isEmpty())
                <div class="text-center text-secondary py-4">暂无回复</div>
                @endif
            </div>
        </div>

        {{-- 追加回复表单 --}}
        @if($feedback->status !== 'closed')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">追加回复</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <textarea id="replyContent" class="form-control" rows="3" maxlength="5000" placeholder="继续描述您的问题或补充信息..."></textarea>
                </div>
                <button type="button" class="btn btn-primary" id="replyBtn" data-action="submit-reply">
                    <i class="ti ti-send me-1"></i> 发送
                </button>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="ti ti-circle-check fs-1 text-secondary"></i>
                <p class="text-secondary mt-2 mb-0">该反馈已关闭</p>
            </div>
        </div>
        @endif

        {{-- 满意度评价（已回复或已关闭且未评价时显示） --}}
        @if(in_array($feedback->status, ['replied', 'closed']) && $feedback->satisfaction_score === null)
        <div class="card mt-3" id="satisfactionCard">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-star me-2 text-warning"></i>满意度评价</h3>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-3">请对本次反馈处理结果进行评价</p>
                <form id="satisfactionForm" action="{{ route('feedback.rate', $feedback) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <div class="satisfaction-stars" id="satisfactionStars">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="btn btn-lg p-1 star-btn" data-score="{{ $i }}" style="font-size:1.8rem;color:#d1d5db;">
                                <i class="ti ti-star"></i>
                            </button>
                            @endfor
                        </div>
                        <input type="hidden" name="score" id="satisfactionScore" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">补充说明（可选）</label>
                        <textarea name="comment" class="form-control" rows="2" maxlength="500" placeholder="对处理结果有什么建议..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitSatisfaction" disabled>
                        <i class="ti ti-check me-1"></i>提交评价
                    </button>
                </form>
            </div>
        </div>
        @elseif($feedback->satisfaction_score !== null)
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-star me-2 text-warning"></i>满意度评价</h3>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="ti ti-star-filled" style="font-size:1.5rem;color:{{ $i <= $feedback->satisfaction_score ? '#f59e0b' : '#d1d5db' }};"></i>
                    @endfor
                    <span class="ms-2 fw-medium">{{ $feedback->satisfaction_score }}/5</span>
                </div>
                @if($feedback->satisfaction_comment)
                    <p class="text-secondary mb-0">{{ $feedback->satisfaction_comment }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">反馈信息</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-secondary w-25">类型</td><td>{{ $feedback->getCategoryLabel() }}</td></tr>
                    <tr><td class="text-secondary">状态</td><td><span class="badge bg-{{ $feedback->getStatusColor() }}-lt">{{ $feedback->getStatusLabel() }}</span></td></tr>
                    <tr><td class="text-secondary">优先级</td><td><span class="badge bg-{{ $feedback->getPriorityColor() }}-lt">{{ $feedback->getPriorityLabel() }}</span></td></tr>
                    <tr><td class="text-secondary">采纳结果</td><td><span class="badge bg-{{ $feedback->getAdoptionColor() }}-lt">{{ $feedback->getAdoptionLabel() }}</span></td></tr>
                    @if($feedback->reward)
                        <tr><td class="text-secondary">奖励状态</td><td><span class="badge bg-success-lt">已奖励</span></td></tr>
                    @endif
                    <tr><td class="text-secondary">提交时间</td><td>{{ $feedback->created_at->format('Y-m-d H:i') }}</td></tr>
                    <tr><td class="text-secondary">更新时间</td><td>{{ $feedback->updated_at->format('Y-m-d H:i') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-feedbacks-show.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    var starBtns = document.querySelectorAll('.star-btn');
    var scoreInput = document.getElementById('satisfactionScore');
    var submitBtn = document.getElementById('submitSatisfaction');
    var form = document.getElementById('satisfactionForm');

    if (starBtns.length && scoreInput) {
        starBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var score = parseInt(btn.dataset.score);
                scoreInput.value = score;
                if (submitBtn) submitBtn.disabled = false;
                starBtns.forEach(function(b) {
                    var s = parseInt(b.dataset.score);
                    b.style.color = s <= score ? '#f59e0b' : '#d1d5db';
                    b.querySelector('i').className = s <= score ? 'ti ti-star-filled' : 'ti ti-star';
                });
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    score: parseInt(scoreInput.value),
                    comment: form.querySelector('[name="comment"]').value
                })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) location.reload();
                else alert(data.message || '提交失败');
            }).catch(function() { alert('请求失败'); });
        });
    }
});
</script>
@endpush
