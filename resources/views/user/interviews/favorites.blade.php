@extends('layouts.user')

@section('title', '错题本 - 面试问题收藏')

@section('content')
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title"><i class="ti ti-star me-2 text-warning"></i>错题本</h2>
            <div class="text-secondary mt-1">收藏面试中答错或想重点复习的题目</div>
        </div>
        <div class="col-auto">
            <a href="{{ route('user.interviews.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>返回面试列表
            </a>
        </div>
    </div>
</div>

@if($favorites->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="ti ti-star-off mb-3" style="font-size:3rem;color:#ccc;"></i>
            <div class="text-secondary mb-3">还没有收藏任何题目</div>
            <div class="text-muted small">在面试报告页面点击星标图标即可收藏题目</div>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($favorites as $fav)
            @php $q = $fav->question; @endphp
            @if(!$q) @continue @endif
            <div class="col-12">
                <div class="card" id="fav-{{ $fav->id }}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @if($q->dimension)<span class="badge bg-info-lt text-info">{{ $q->dimension }}</span>@endif
                            @if($q->score !== null)
                                <span class="badge bg-{{ $q->score>=7?'success':($q->score>=5?'warning':'danger') }}-lt text-{{ $q->score>=7?'success':($q->score>=5?'warning':'danger') }}">{{ $q->score }}/10</span>
                            @endif
                            @if($q->interviewSession)
                                <span class="badge bg-secondary-lt">{{ $q->interviewSession->position ?? '面试' }}</span>
                            @endif
                            <span class="badge bg-warning-lt text-warning"><i class="ti ti-star-filled me-1"></i>已收藏</span>
                            <button type="button" class="btn btn-sm btn-link p-0 ms-auto text-danger fav-remove-btn" data-fav-id="{{ $fav->id }}" title="取消收藏">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <div class="fw-medium mb-2">{{ $q->question }}</div>

                        @if($q->answer)
                            <div class="small bg-light rounded p-2 mb-2" style="white-space:pre-wrap;">{{ $q->answer }}</div>
                        @endif

                        @if(!empty($q->feedback['comment']))
                            <div class="small mb-1"><span class="text-success fw-medium">点评：</span><span class="text-secondary">{{ $q->feedback['comment'] }}</span></div>
                        @endif
                        @if(!empty($q->feedback['suggestion']))
                            <div class="small mb-1"><span class="text-warning fw-medium">建议：</span><span class="text-secondary">{{ $q->feedback['suggestion'] }}</span></div>
                        @endif

                        @if($fav->note)
                            <div class="small mt-2 p-2 rounded" style="background:#fff8e1;">
                                <span class="fw-medium text-warning">我的笔记：</span>{{ $fav->note }}
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="small text-secondary">{{ $fav->created_at->diffForHumans() }}</span>
                            @if($q->interviewSession)
                                <a href="{{ route('user.interviews.report', $q->interviewSession) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-external-link me-1"></i>查看完整报告
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">{{ $favorites->withQueryString()->links() }}</div>
@endif
@endsection

@section('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fav-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('确定取消收藏？')) return;
            var favId = this.dataset.favId;
            var card = document.getElementById('fav-' + favId);
            fetch('{{ url("/user/question-favorites") }}/' + favId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(function(r) { return r.json(); }).then(function() {
                if (card) { card.style.transition = 'opacity .3s'; card.style.opacity = '0'; setTimeout(function() { card.remove(); }, 300); }
            }).catch(function() {});
        });
    });
});
</script>
@endsection
