@extends('layouts.user')

@section('title', '平台动态')
@section('page-pretitle', '发现')
@section('page-title', '平台动态')

@php $catIcons = ['公告' => 'speakerphone', '活动' => 'confetti', '更新' => 'stars', '洞察' => 'bulb']; $catClass = ['公告' => 'announcement', '活动' => 'event', '更新' => 'update', '洞察' => 'insight']; @endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        @forelse($events as $event)
        <div class="timeline-item">
            <div class="timeline-dot {{ $event->is_pinned ? 'pinned' : ($catClass[$event->category] ?? '') }}">
                <i class="ti ti-{{ $catIcons[$event->category] ?? 'point' }}"></i>
            </div>
            <div class="timeline-card card-{{ $catClass[$event->category] ?? '' }} {{ $event->is_pinned ? 'is-pinned' : '' }}" data-event-url="{{ route('public.events.show', $event) }}">
                @if($event->cover_image)
                <div class="timeline-card-cover" style="background-image:url('{{ $event->cover_image }}')">
                    <div class="cover-icon" style="color:{{ $event->category === '公告' ? '#2563eb' : ($event->category === '活动' ? '#16a34a' : ($event->category === '更新' ? '#0891b2' : '#7c3aed')) }}">
                        <i class="ti ti-{{ $catIcons[$event->category] ?? 'point' }}"></i>
                    </div>
                </div>
                @endif
                <div class="timeline-card-body">
                    <div class="timeline-meta">
                        <span class="timeline-tag tag-{{ $catClass[$event->category] ?? '' }}">{{ $event->category }}</span>
                        @if($event->is_pinned)
                        <span class="badge bg-yellow-lt text-yellow"><i class="ti ti-pinned-filled me-1"></i>置顶</span>
                        @endif
                        <span class="timeline-date ms-auto">{{ $event->published_at?->format('Y-m-d') }}</span>
                    </div>
                    <div class="timeline-title">{{ $event->title }}</div>
                    @if($event->summary)
                    <p class="timeline-summary">{{ Str::limit($event->summary, 120) }}</p>
                    @endif
                    <div class="timeline-footer">
                        <span><i class="ti ti-eye me-1"></i>{{ $event->view_count }} 次浏览</span>
                        <span class="ms-auto"><i class="ti ti-clock me-1"></i>{{ $event->published_at?->format('m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="timeline-empty">
            <i class="ti ti-news-off"></i>
            <p class="fw-medium mb-1">暂无平台动态</p>
            <p class="small text-secondary">新的公告、活动或更新将在这里展示</p>
        </div>
        @endforelse

        @if($events->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $events->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal --}}
<div class="modal fade event-modal" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="timeline-tag tag-update" id="modalCategory"></span>
                    <small class="text-secondary ms-2" id="modalDate"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="modalCover" class="modal-cover d-none" src="" alt="">
                <h3 class="mb-3" id="modalTitle" style="font-weight:700"></h3>
                <p id="modalSummary" class="text-secondary d-none" style="line-height:1.7"></p>
                <div class="modal-content-body" id="modalContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/events-timeline.css') }}">
@endpush

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function(){
    var catClassMap = { '公告': 'announcement', '活动': 'event', '更新': 'update', '洞察': 'insight' };
    var modalEl = document.getElementById('eventModal');
    var modal = null;
    var loading = false;

    function getModal() {
        if (!modal && typeof bootstrap !== 'undefined') {
            modal = new bootstrap.Modal(modalEl);
        }
        return modal;
    }

    function openModal() {
        var m = getModal();
        if (m) { m.show(); return; }
        // Fallback: manual show
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        document.body.classList.add('modal-open');
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'event-modal-backdrop';
        document.body.appendChild(backdrop);
    }

    function closeModal() {
        var m = getModal();
        if (m) { m.hide(); return; }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.body.classList.remove('modal-open');
        var bd = document.getElementById('event-modal-backdrop');
        if (bd) bd.remove();
    }

    modalEl.querySelector('.btn-close').addEventListener('click', closeModal);
    modalEl.addEventListener('click', function(e){ if (e.target === modalEl) closeModal(); });

    document.querySelectorAll('.timeline-card').forEach(function(card){
        card.addEventListener('click', function(){
            if (loading) return;
            var url = this.dataset.eventUrl + '/content';
            loading = true;

            document.getElementById('modalContent').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            document.getElementById('modalCategory').textContent = '';
            document.getElementById('modalTitle').textContent = '';
            document.getElementById('modalDate').textContent = '';
            document.getElementById('modalSummary').classList.add('d-none');
            document.getElementById('modalCover').classList.add('d-none');
            openModal();

            fetch(url).then(function(r){ return r.json(); }).then(function(data){
                document.getElementById('modalCategory').textContent = data.category;
                document.getElementById('modalCategory').className = 'timeline-tag tag-' + (catClassMap[data.category] || 'update');
                document.getElementById('modalTitle').textContent = data.title;
                document.getElementById('modalDate').textContent = data.published_at;
                document.getElementById('modalContent').innerHTML = data.content || '';
                if (data.summary) {
                    document.getElementById('modalSummary').textContent = data.summary;
                    document.getElementById('modalSummary').classList.remove('d-none');
                }
                if (data.cover_image) {
                    document.getElementById('modalCover').src = data.cover_image;
                    document.getElementById('modalCover').classList.remove('d-none');
                }
                loading = false;
            }).catch(function(){
                document.getElementById('modalContent').innerHTML = '<p class="text-danger">加载失败，请重试</p>';
                loading = false;
            });
        });
    });
})();
</script>
@endpush
