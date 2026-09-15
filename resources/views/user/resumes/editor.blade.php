@extends('layouts.editor')

@section('title', '可视化编辑 - ' . $resume->title)

@section('editor-title', $resume->title)

@section('editor-scroll-mode', 'natural')

@section('editor-status')
<span x-bind:class="{
    'text-secondary': draftSaveState === 'idle' || draftSaveState === 'saved',
    'text-warning': draftSaveState === 'dirty',
    'text-primary': draftSaveState === 'saving',
    'text-danger': draftSaveState === 'error'
}" style="font-size: 13px;">
    <i class="ti me-1" x-bind:class="{
        'ti-check': draftSaveState === 'idle' || draftSaveState === 'saved',
        'ti-pencil': draftSaveState === 'dirty',
        'ti-loader-2 ti-spin': draftSaveState === 'saving',
        'ti-alert-circle': draftSaveState === 'error'
    }"></i>
    <span x-text="{
        idle: '已保存',
        saved: '已保存',
        dirty: '未保存',
        saving: '保存中...',
        error: '保存失败'
    }[draftSaveState] || '已保存'"></span>
</span>
@endsection

@section('editor-status-mobile')
<span x-bind:class="{
    'text-secondary': draftSaveState === 'idle' || draftSaveState === 'saved',
    'text-warning': draftSaveState === 'dirty',
    'text-primary': draftSaveState === 'saving',
    'text-danger': draftSaveState === 'error'
}" style="font-size: 12px;">
    <i class="ti me-1" x-bind:class="{
        'ti-check': draftSaveState === 'idle' || draftSaveState === 'saved',
        'ti-pencil': draftSaveState === 'dirty',
        'ti-loader-2 ti-spin': draftSaveState === 'saving',
        'ti-alert-circle': draftSaveState === 'error'
    }"></i>
</span>
@endsection

@section('editor-actions')
<a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-eye me-1"></i>预览
</a>
@if(!empty($templateUndoAvailable))
    <form method="POST" action="{{ route('user.resumes.template-undo', $resume) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-warning">
            <i class="ti ti-rotate-2 me-1"></i>撤销套用
        </button>
    </form>
@endif
@endsection

@section('content')
@include('user.resumes.editor-partials.styles')

<div class="d-lg-none alert alert-warning mb-0 text-center" style="border-radius:0;">
    <i class="ti ti-device-mobile me-1"></i>编辑器建议在电脑端使用，移动端可能无法完整操作。
</div>

<div id="resume-plan-features"
    data-advanced-model-enabled="{{ ($advancedModelEnabled ?? false) ? '1' : '0' }}"
    data-priority-queue-enabled="{{ ($priorityQueueEnabled ?? false) ? '1' : '0' }}"
    data-upgrade-url="{{ route('user.membership.pricing') }}"
    hidden></div>
{{-- Skeleton shown before Alpine initializes, positioned absolute so it doesn't affect flex layout --}}
<div id="editor-skeleton" class="d-flex w-100" style="position: absolute; inset: 0; z-index: 1050; background: var(--color-bg-secondary, #f8fafc);">
    <div class="editor-left-col col-12 col-lg-5 p-3">
        <div class="placeholder col-8 mb-3 rounded" style="height:24px"></div>
        <div class="placeholder placeholder-xs col-12 mb-2 rounded"></div>
        <div class="placeholder placeholder-xs col-10 mb-3 rounded"></div>
        @for ($i = 0; $i < 4; $i++)
        <div class="card mb-2"><div class="card-body p-3">
            <div class="placeholder col-6 mb-2 rounded" style="height:16px"></div>
            <div class="placeholder placeholder-xs col-12 rounded"></div>
            <div class="placeholder placeholder-xs col-9 rounded"></div>
        </div></div>
        @endfor
    </div>
    <div class="col-12 col-lg-7 p-4 d-none d-lg-block">
        <div class="placeholder col-12 rounded" style="height:400px"></div>
    </div>
</div>

<div class="editor-workspace" x-data="resumeEditor()" x-init="$nextTick(() => document.getElementById('editor-skeleton')?.remove())">
    @include('user.resumes.editor-partials.sidebar.hero')
    <div class="row g-0 editor-page-wrapper">
        <div id="editor-toast-container" style="position: fixed; top: 80px; right: 24px; z-index: 1050;"></div>
        @include('user.resumes.editor-partials.sidebar')
        @include('user.resumes.editor-partials.preview')
        @include('user.resumes.editor-partials.import-preview-modal')
        @include('user.resumes.editor-partials.optimized-diff-modal')
        @include('user.resumes.editor-partials.ai-panel-modals')
        @include('user.resumes.editor-partials.ai-workbench-modal')
        @include('user.resumes.editor-partials.ai-optimize-history-modal')
        @include('user.resumes.editor-partials.onboarding')
        {{-- 赛道选择弹窗 --}}
        <div class="modal modal-blur fade" id="trackModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-compass me-2"></i>选择求职赛道</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary mb-3">选择赛道后，AI优化将使用专属策略，更精准地匹配目标岗位要求。</p>
                        <form action="{{ route('user.resumes.update-career-track', $resume) }}" method="POST" id="editorTrackForm">
                            @csrf @method('PUT')
                            <input type="hidden" name="career_track_id" id="editorTrackIdInput" value="{{ $resume->career_track_id }}">

                            @php
                                $editorTracks = \App\Models\CareerTrack::active()->ordered()->get()->groupBy('category');
                                $editorCategoryLabels = \App\Models\CareerTrack::categoryLabels();
                            @endphp

                            @foreach($editorTracks as $category => $group)
                                <div class="mb-3">
                                    <h6 class="text-secondary mb-2">{{ $editorCategoryLabels[$category] ?? $category }}</h6>
                                    <div class="row g-2">
                                        @foreach($group as $track)
                                            <div class="col-6 col-md-4">
                                                <label class="form-selectgroup-item" style="cursor:pointer;">
                                                    <input type="radio" name="editor_track_radio" value="{{ $track->id }}" class="form-selectgroup-input" {{ $resume->career_track_id === $track->id ? 'checked' : '' }}>
                                                    <div class="card p-2 mb-0 editor-track-card {{ $resume->career_track_id === $track->id ? 'border-primary' : '' }}" style="transition: all .15s;">
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($track->icon)<i class="ti {{ $track->icon }}" style="color: {{ $track->color }}; font-size:1.1rem;"></i>@endif
                                                            <span class="fw-medium small">{{ $track->name }}</span>
                                                        </div>
                                                        <div class="text-secondary mt-1" style="font-size:.7rem; line-height:1.3;">{{ $track->description }}</div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="mt-2">
                                <label class="form-selectgroup-item" style="cursor:pointer;">
                                    <input type="radio" name="editor_track_radio" value="" class="form-selectgroup-input" {{ !$resume->career_track_id ? 'checked' : '' }}>
                                    <span class="small text-secondary">不选择赛道（使用通用优化策略）</span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" form="editorTrackForm" class="btn btn-primary"><i class="ti ti-check me-1"></i>确认选择</button>
                    </div>
                </div>
            </div>
        </div>
        <script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
        document.querySelectorAll('input[name="editor_track_radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById('editorTrackIdInput').value = radio.value;
                document.querySelectorAll('.editor-track-card').forEach(c => c.classList.remove('border-primary'));
                const card = radio.closest('.form-selectgroup-item')?.querySelector('.editor-track-card');
                if (card) card.classList.add('border-primary');
            });
        });
        </script>
    </div>
</div>
@endsection

@include('user.resumes.editor-partials.scripts')
