@extends('layouts.user')

@section('title', '我的反馈')
@section('page-title', '我的反馈')

@section('content')
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">我的反馈</h2>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                <i class="ti ti-plus me-1"></i> 新建反馈
            </button>
        </div>
    </div>
</div>

@if($feedbacks->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-message-circle fs-1 text-secondary"></i>
        <p class="text-secondary mt-2">暂无反馈记录</p>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
            <i class="ti ti-plus me-1"></i> 提交第一条反馈
        </button>
    </div>
</div>
@else
<div class="row row-cards">
    @foreach($feedbacks as $fb)
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('feedback.show', $fb) }}" class="card card-link">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge bg-{{ $fb->getStatusColor() }}-lt me-auto">{{ $fb->getStatusLabel() }}</span>
                    <span class="badge bg-{{ $fb->getPriorityColor() }}-lt">{{ $fb->getPriorityLabel() }}</span>
                </div>
                <div class="mb-2">
                    <span class="badge bg-{{ $fb->getAdoptionColor() }}-lt">{{ $fb->getAdoptionLabel() }}</span>
                    @if($fb->reward)
                        <span class="badge bg-success-lt">已奖励 {{ \App\Models\UserCredit::quotaLabel($fb->reward->quota_key) }} x {{ $fb->reward->credits }}</span>
                    @endif
                </div>
                <h3 class="card-title mb-1" style="font-size: 0.95rem;">{{ Str::limit($fb->title, 40) }}</h3>
                <div class="text-secondary small mb-2">
                    <span class="badge bg-{{ $fb->category === 'bug' ? 'red' : ($fb->category === 'suggestion' ? 'blue' : ($fb->category === 'ux' ? 'yellow' : 'secondary')) }}-lt">{{ $fb->getCategoryLabel() }}</span>
                </div>
                <p class="text-secondary small mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ Str::limit($fb->content, 80) }}</p>
                <div class="d-flex text-secondary small">
                    <span><i class="ti ti-message me-1"></i>{{ $fb->replies_count }} 条回复</span>
                    <span class="ms-auto">{{ $fb->created_at->format('m-d H:i') }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="mt-4 d-flex justify-content-center">
    {{ $feedbacks->links() }}
</div>
@endif
@endsection
