@extends('layouts.user')

@section('title', $template->name . ' - 模板详情')

@section('page-pretitle', '模板中心')
@section('page-title', $template->name)

@section('page-actions')
<a href="{{ route('user.resume-templates.index') }}" class="btn btn-outline-secondary me-2">
    <i class="ti ti-arrow-left me-1"></i>返回模板库
</a>
<button
    type="button"
    class="btn btn-primary js-open-apply-modal"
    data-action="{{ route('user.resume-templates.apply', $template) }}"
    data-template-name="{{ $template->name }}"
>
    <i class="ti ti-wand me-1"></i>一键套用
</button>
@endsection

@section('content')
<style>
.tpl-detail-layout { display: flex; gap: 24px; }
@media (max-width: 991px) { .tpl-detail-layout { flex-direction: column; } }

.tpl-detail-preview {
    flex: 1; min-width: 0;
}
.tpl-detail-preview .preview-container {
    background: #f3f4f6; border-radius: 12px; padding: 24px; overflow: auto;
    max-height: 80vh;
}
.tpl-detail-preview .preview-page {
    margin: 0 auto; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.tpl-detail-sidebar {
    width: 340px; flex-shrink: 0;
}
@media (max-width: 991px) { .tpl-detail-sidebar { width: 100%; } }

.tpl-detail-sidebar .sidebar-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
    padding: 20px; margin-bottom: 16px;
}
.tpl-detail-sidebar .sidebar-card h4 {
    font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase;
    letter-spacing: 0.05em; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;
}

.tpl-detail-name { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; }
.tpl-detail-position { font-size: 0.9rem; color: #64748b; margin-bottom: 12px; }
.tpl-detail-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
.tpl-detail-badges .badge { font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }

.tpl-detail-apply-btn {
    display: block; width: 100%; padding: 12px; border-radius: 10px; font-size: 1rem;
    font-weight: 600; text-align: center; cursor: pointer; border: none;
    background: linear-gradient(135deg, #206bc4 0%, #1a56a0 100%); color: #fff;
    transition: transform 0.15s, box-shadow 0.15s;
}
.tpl-detail-apply-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(32,107,196,0.3); }

.tpl-detail-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid #f8fafc; font-size: 0.85rem;
}
.tpl-detail-info-row:last-child { border-bottom: none; }
.tpl-detail-info-row .label { color: #94a3b8; }
.tpl-detail-info-row .value { color: #1e293b; font-weight: 500; }

.tpl-detail-points { list-style: none; padding: 0; margin: 0; }
.tpl-detail-points li {
    padding: 6px 0; font-size: 0.85rem; color: #475569;
    display: flex; align-items: flex-start; gap: 8px;
}
.tpl-detail-points li i { color: #206bc4; margin-top: 2px; flex-shrink: 0; }

.tpl-related-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}
.tpl-related-card {
    border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px;
    transition: box-shadow 0.2s; text-decoration: none; display: block;
}
.tpl-related-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.tpl-related-card .name { font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tpl-related-card .meta { font-size: 0.78rem; color: #94a3b8; margin-bottom: 8px; }
.tpl-related-card .actions { display: flex; gap: 6px; }
.tpl-related-card .actions .btn { font-size: 0.75rem; padding: 3px 10px; }
</style>

<div class="tpl-detail-layout">
    <div class="tpl-detail-preview">
        <div class="preview-container">
            <div class="preview-page" style="width: 210mm; min-height: 297mm; transform: scale(0.58); transform-origin: top center; margin-bottom: -120mm;">
                @include('user.resumes.templates.' . ($template->template ?: 'classic'), [
                    'resume' => $previewResume,
                    'theme' => $template->theme ?: 'blue',
                    'forceModules' => $previewModules,
                ])
            </div>
        </div>
    </div>

    <div class="tpl-detail-sidebar">
        <div class="sidebar-card">
            <div class="tpl-detail-name">{{ $template->name }}</div>
            <div class="tpl-detail-position">
                <i class="ti ti-briefcase me-1"></i>{{ $template->position }}
                <span class="ms-2"><i class="ti ti-building me-1"></i>{{ $template->industry ?: '通用行业' }}</span>
            </div>
            <div class="tpl-detail-badges">
                <span class="badge bg-blue-lt">{{ $template->category }}</span>
                <span class="badge bg-purple-lt">{{ $template->level }}</span>
                <span class="badge bg-green-lt">ATS {{ $template->ats_level }}</span>
                @if(!empty($focusLabel))
                <span class="badge bg-indigo-lt">{{ $focusLabel }}</span>
                @endif
                @if($template->is_featured)
                <span class="badge bg-orange-lt"><i class="ti ti-star-filled me-1"></i>推荐</span>
                @endif
                @if($isExternal)
                <span class="badge" style="background: #eef2ff; color: #4f46e5;"><i class="ti ti-cloud me-1"></i>{{ $template->source?->name ?? '第三方' }}</span>
                @endif
            </div>
            @if($isExternal && $externalUrl)
            <a href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary w-100 mb-2" style="border-radius: 10px;">
                <i class="ti ti-external-link me-1"></i>查看原始模板
            </a>
            @endif
            <button type="button" class="tpl-detail-apply-btn js-open-apply-modal"
                data-action="{{ route('user.resume-templates.apply', $template) }}"
                data-template-name="{{ $template->name }}">
                <i class="ti ti-wand me-1"></i>一键套用此模板
            </button>
        </div>

        <div class="sidebar-card">
            <h4>模板信息</h4>
            <div class="tpl-detail-info-row">
                <span class="label">版式风格</span>
                <span class="value">{{ $template->style }}</span>
            </div>
            <div class="tpl-detail-info-row">
                <span class="label">布局模板</span>
                <span class="value">{{ $template->template }}</span>
            </div>
            <div class="tpl-detail-info-row">
                <span class="label">主题配色</span>
                <span class="value">{{ $template->theme }}</span>
            </div>
            <div class="tpl-detail-info-row">
                <span class="label">内容密度</span>
                <span class="value">{{ $template->density }}</span>
            </div>
            <div class="tpl-detail-info-row">
                <span class="label">ATS 友好度</span>
                <span class="value">ATS {{ $template->ats_level }}</span>
            </div>
            <div class="tpl-detail-info-row">
                <span class="label">使用次数</span>
                <span class="value">{{ number_format((int) ($template->usage_count ?? 0)) }}</span>
            </div>
            @if($isExternal)
            <div class="tpl-detail-info-row">
                <span class="label">模板来源</span>
                <span class="value" style="color: #4f46e5;"><i class="ti ti-cloud me-1"></i>{{ $template->source?->name ?? '第三方' }}</span>
            </div>
            @endif
        </div>

        @if(!empty($diffPoints))
        <div class="sidebar-card">
            <h4>差异化亮点</h4>
            <ul class="tpl-detail-points">
                @foreach($diffPoints as $point)
                <li><i class="ti ti-point-filled"></i>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($suitableScenes))
        <div class="sidebar-card">
            <h4>适用场景</h4>
            <div class="d-flex flex-wrap gap-2">
                @foreach($suitableScenes as $scene)
                <span class="badge bg-indigo-lt">{{ $scene }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(is_array($template->tags) && !empty($template->tags))
        <div class="sidebar-card">
            <h4>标签</h4>
            <div class="d-flex flex-wrap gap-2">
                @foreach($template->tags as $tag)
                <span class="badge bg-secondary-lt">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@if($relatedTemplates->isNotEmpty())
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title mb-0">相关推荐</h3>
    </div>
    <div class="card-body">
        <div class="tpl-related-grid">
            @foreach($relatedTemplates as $related)
            <div class="tpl-related-card">
                <div class="name" title="{{ $related->name }}">{{ $related->name }}</div>
                <div class="meta">{{ $related->position }} · {{ $related->level }}</div>
                @if(!empty($related->recommend_reason))
                <div class="mb-2"><span class="badge bg-blue-lt">{{ $related->recommend_reason }}</span></div>
                @endif
                <div class="actions">
                    <a href="{{ route('user.resume-templates.show', ['resumeTemplate' => $related, 'scene' => 'related', 'from' => $template->id]) }}" class="btn btn-outline-secondary btn-sm">查看</a>
                    @if($related->position === $template->position)
                    <a href="{{ route('user.resume-templates.show', ['resumeTemplate' => $template, 'compare' => $related->id]) }}" class="btn btn-outline-primary btn-sm">对比</a>
                    @endif
                    <button type="button" class="btn btn-primary btn-sm js-open-apply-modal"
                        data-action="{{ route('user.resume-templates.apply', $related) }}"
                        data-template-name="{{ $related->name }}"
                        data-source="related_card"
                        data-from-template-id="{{ $template->id }}">
                        套用
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($compareTarget)
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title mb-0">同岗位模板对比</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="fw-bold mb-2">{{ $template->name }}</div>
                <div class="rounded border overflow-auto p-3" style="background: #f3f4f6;">
                    <div class="mx-auto bg-white shadow-sm" style="width: 210mm; min-height: 297mm; transform: scale(0.52); transform-origin: top center; margin-bottom: -130mm;">
                        @include('user.resumes.templates.' . ($template->template ?: 'classic'), [
                            'resume' => $previewResume,
                            'theme' => $template->theme ?: 'blue',
                            'forceModules' => $previewModules,
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">{{ $compareTarget->name }}</div>
                    <a href="{{ route('user.resume-templates.show', $compareTarget) }}" class="btn btn-outline-secondary btn-sm">切到此模板</a>
                </div>
                <div class="rounded border overflow-auto p-3" style="background: #f3f4f6;">
                    <div class="mx-auto bg-white shadow-sm" style="width: 210mm; min-height: 297mm; transform: scale(0.52); transform-origin: top center; margin-bottom: -130mm;">
                        @include('user.resumes.templates.' . ($compareTarget->template ?: 'classic'), [
                            'resume' => $comparePreviewResume,
                            'theme' => $compareTarget->theme ?: 'blue',
                            'forceModules' => $comparePreviewModules,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal modal-blur fade" id="applyTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="applyTemplateForm">
                @csrf
                <input type="hidden" name="source" id="applyTemplateSource" value="">
                <input type="hidden" name="from_template_id" id="applyTemplateFromId" value="">
                <input type="hidden" name="idempotency_key" id="applyTemplateIdempotencyKey" value="">
                <div class="modal-header">
                    <h5 class="modal-title">套用模板</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary mb-2">当前模板：<span class="fw-bold" id="applyTemplateName">-</span></p>
                    <label class="form-label">套用目标</label>
                    <select name="resume_id" class="form-select">
                        <option value="">新建一份简历并套用</option>
                        @foreach($resumeTargets as $target)
                            <option value="{{ $target['id'] }}">套用到现有简历：{{ $target['title'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-hint mt-2">选择现有简历时会保留原内容，仅切换模板样式与主题。</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认套用</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-resume-templates-show.js') }}"></script>
@endpush
