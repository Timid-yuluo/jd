@extends('layouts.user')

@section('title', '简历模板库')

@section('page-pretitle', '模板中心')
@section('page-title', '简历模板库')

@section('page-actions')
<a href="{{ route('user.resumes.create') }}" class="btn btn-outline-primary">
    <i class="ti ti-plus me-2"></i>空白新建
</a>
<a href="{{ route('user.resume-templates.analytics') }}" class="btn btn-outline-secondary ms-2">
    <i class="ti ti-chart-bar me-2"></i>模板转化数据
</a>
@endsection

@section('content')
<style>
.tpl-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.tpl-tabs .tpl-tab {
    padding: 6px 18px; border-radius: 20px; font-size: 0.875rem; font-weight: 500;
    cursor: pointer; transition: all 0.2s; border: 1px solid #e5e7eb; background: #fff; color: #64748b;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
}
.tpl-tabs .tpl-tab:hover { border-color: #206bc4; color: #206bc4; background: #f0f6ff; }
.tpl-tabs .tpl-tab.active { background: #206bc4; color: #fff; border-color: #206bc4; }

.tpl-search-bar {
    display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;
}
.tpl-search-bar .search-input {
    flex: 1; min-width: 200px; position: relative;
}
.tpl-search-bar .search-input input {
    width: 100%; padding: 8px 14px 8px 38px; border-radius: 10px; border: 1px solid #e2e8f0;
    font-size: 0.9rem; transition: border-color 0.2s;
}
.tpl-search-bar .search-input input:focus { border-color: #206bc4; outline: none; box-shadow: 0 0 0 3px rgba(32,107,196,0.1); }
.tpl-search-bar .search-input .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.tpl-filter-select {
    padding: 8px 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 0.85rem;
    background: #fff; color: #475569; cursor: pointer; min-width: 110px;
}
.tpl-filter-select:focus { border-color: #206bc4; outline: none; }

.tpl-waterfall {
    column-count: 3; column-gap: 18px;
}
@media (max-width: 1199px) { .tpl-waterfall { column-count: 2; } }
@media (max-width: 767px) { .tpl-waterfall { column-count: 1; } }

.tpl-card {
    break-inside: avoid; margin-bottom: 18px; border-radius: 12px; overflow: hidden;
    background: #fff; border: 1px solid #e5e7eb; transition: box-shadow 0.25s, transform 0.25s;
    position: relative;
}
.tpl-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.1); transform: translateY(-2px); }

.tpl-card-preview {
    position: relative; overflow: hidden; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    min-height: 180px; display: flex; align-items: center; justify-content: center;
}
.tpl-card-preview img { width: 100%; display: block; object-fit: cover; }
.tpl-card-preview .preview-placeholder {
    padding: 30px 20px; text-align: center; color: #94a3b8; font-weight: 600; font-size: 1rem;
}
.tpl-card-overlay {
    position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex;
    align-items: center; justify-content: center; gap: 10px; opacity: 0;
    transition: opacity 0.25s;
}
.tpl-card:hover .tpl-card-overlay { opacity: 1; }
.tpl-card-overlay .overlay-btn {
    padding: 8px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    cursor: pointer; border: none; transition: transform 0.15s; text-decoration: none;
}
.tpl-card-overlay .overlay-btn:hover { transform: scale(1.05); }
.tpl-card-overlay .overlay-btn-primary { background: #fff; color: #1e293b; }
.tpl-card-overlay .overlay-btn-secondary { background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.4); }

.tpl-card-body { padding: 14px 16px; }
.tpl-card-title {
    font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0 0 6px 0;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.tpl-card-meta { font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; display: flex; gap: 12px; flex-wrap: wrap; }
.tpl-card-meta span { display: inline-flex; align-items: center; gap: 3px; }
.tpl-card-badges { display: flex; flex-wrap: wrap; gap: 4px; }
.tpl-card-badges .badge { font-size: 0.72rem; font-weight: 500; padding: 2px 8px; border-radius: 4px; }

.tpl-card-featured {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #fff; font-size: 0.72rem; font-weight: 600; padding: 3px 10px; border-radius: 6px;
}

.tpl-stats-bar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 16px; font-size: 0.85rem; color: #94a3b8;
}

.tpl-load-more {
    text-align: center; margin-top: 24px; padding-bottom: 20px;
}
.tpl-load-more .btn { padding: 10px 40px; border-radius: 10px; font-weight: 600; }
</style>

<div class="tpl-tabs">
    <a href="{{ route('user.resume-templates.index') }}" class="tpl-tab {{ $category === '' && $level === '' && ($sourceSlug ?? '') === '' ? 'active' : '' }}">
        <i class="ti ti-layout-grid"></i>全部
    </a>
    @foreach($categories as $cat)
        <a href="{{ route('user.resume-templates.index', array_filter(['category' => $cat, 'q' => $keyword, 'sort' => $sort, 'source' => $sourceSlug])) }}" class="tpl-tab {{ $category === $cat ? 'active' : '' }}">
            {{ $cat }}
        </a>
    @endforeach
    <span style="width: 1px; height: 24px; background: #e5e7eb; margin: 0 6px; align-self: center;"></span>
    <a href="{{ route('user.resume-templates.index', array_filter(['source' => 'builtin', 'q' => $keyword, 'category' => $category, 'sort' => $sort])) }}" class="tpl-tab {{ ($sourceSlug ?? '') === 'builtin' ? 'active' : '' }}">
        <i class="ti ti-home"></i>官方模板
    </a>
    @foreach($activeSources as $src)
        <a href="{{ route('user.resume-templates.index', array_filter(['source' => $src->slug, 'q' => $keyword, 'category' => $category, 'sort' => $sort])) }}" class="tpl-tab {{ ($sourceSlug ?? '') === $src->slug ? 'active' : '' }}">
            <i class="ti ti-cloud"></i>{{ $src->name }}
        </a>
    @endforeach
</div>

<div class="tpl-search-bar">
    <div class="search-input">
        <i class="ti ti-search search-icon"></i>
        <input type="text" id="tplSearchInput" value="{{ $keyword }}" placeholder="搜索模板名、岗位或行业关键词..." autocomplete="off">
    </div>
    <select class="tpl-filter-select" id="tplLevelSelect">
        <option value="">全部阶段</option>
        @foreach($levels as $item)
            <option value="{{ $item }}" {{ $level === $item ? 'selected' : '' }}>{{ $item }}</option>
        @endforeach
    </select>
    <select class="tpl-filter-select" id="tplSortSelect">
        <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>推荐优先</option>
        <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>使用最多</option>
        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>最新上架</option>
    </select>
    <select class="tpl-filter-select" id="tplFocusSelect">
        <option value="">全部侧重</option>
        @foreach($focusOptions as $option)
            <option value="{{ $option }}" {{ $focus === $option ? 'selected' : '' }}>{{ $option }}</option>
        @endforeach
    </select>
</div>

<div class="tpl-stats-bar">
    <span>共 <strong>{{ (int) $rawTotal }}</strong> 套模板{{ $diversify && (int) $displayedTotal !== (int) $rawTotal ? '，展示 <strong>'.(int) $displayedTotal.'</strong> 套（去同质化）' : '' }}</span>
    <div class="d-flex gap-2 align-items-center">
        <label class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="tplDiversifySwitch" {{ $diversify ? 'checked' : '' }}>
            <span class="form-check-label small">去同质化</span>
        </label>
    </div>
</div>

@if(($recentTemplates ?? collect())->isNotEmpty() || ($recentResumes ?? collect())->isNotEmpty())
<div class="row g-3 mb-4">
    @if(($recentTemplates ?? collect())->isNotEmpty())
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">最近使用模板</h3></div>
            <div class="list-group list-group-flush">
                @foreach($recentTemplates as $recentTemplate)
                    <a href="{{ route('user.resume-templates.show', $recentTemplate) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="text-truncate">{{ $recentTemplate->name }}</span>
                            <span class="badge bg-azure-lt ms-2">{{ $recentTemplate->style }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @if(($recentResumes ?? collect())->isNotEmpty())
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">继续编辑最近简历</h3></div>
            <div class="list-group list-group-flush">
                @foreach($recentResumes as $recentResume)
                    <a href="{{ route('user.resumes.editor', $recentResume) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="text-truncate">{{ trim((string) $recentResume->title) !== '' ? $recentResume->title : ('未命名简历 #'.$recentResume->id) }}</span>
                            <span class="text-secondary small ms-2">{{ optional($recentResume->updated_at)->format('m-d H:i') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endif

@if($templates->isEmpty())
<div class="empty py-5">
    <div class="empty-icon"><i class="ti ti-template" style="font-size: 4rem; color: #ccc;"></i></div>
    <p class="empty-title">暂无匹配模板</p>
    <p class="empty-subtitle text-secondary">请调整筛选条件后重试。</p>
</div>
@else
<div class="tpl-waterfall" id="tplWaterfall">
    @foreach($templates as $template)
    <div class="tpl-card">
        @if($template->is_featured)
        <div class="tpl-card-featured"><i class="ti ti-star-filled me-1"></i>推荐</div>
        @endif
        @if($template->isExternal())
        <div style="position: absolute; top: 10px; right: 10px; z-index: 2; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 5px;"><i class="ti ti-cloud me-1"></i>{{ $template->source?->name ?? '第三方' }}</div>
        @endif
        <div class="tpl-card-preview">
            @if(!empty($template->preview_image_url))
                <img src="{{ $template->preview_image_url }}" alt="{{ $template->name }}" loading="lazy">
            @else
                <div class="preview-placeholder">
                    <i class="ti ti-file-text" style="font-size: 2.5rem; display: block; margin-bottom: 8px;"></i>
                    {{ $template->position }}
                </div>
            @endif
            <div class="tpl-card-overlay">
                <a href="{{ route('user.resume-templates.show', $template) }}" class="overlay-btn overlay-btn-secondary">预览</a>
                <button type="button" class="overlay-btn overlay-btn-primary js-open-apply-modal"
                    data-action="{{ route('user.resume-templates.apply', $template) }}"
                    data-template-name="{{ $template->name }}">
                    <i class="ti ti-wand me-1"></i>套用
                </button>
            </div>
        </div>
        <div class="tpl-card-body">
            <div class="tpl-card-title" title="{{ $template->name }}">{{ $template->name }}</div>
            <div class="tpl-card-meta">
                <span><i class="ti ti-briefcase"></i>{{ $template->position }}</span>
                <span><i class="ti ti-building"></i>{{ $template->industry ?: '通用' }}</span>
            </div>
            <div class="tpl-card-badges">
                <span class="badge bg-blue-lt">{{ $template->category }}</span>
                <span class="badge bg-purple-lt">{{ $template->level }}</span>
                <span class="badge bg-green-lt">ATS {{ $template->ats_level }}</span>
                @if(!empty($template->focus_label))
                <span class="badge bg-indigo-lt">{{ $template->focus_label }}</span>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($templates->hasPages())
<div class="tpl-load-more">
    @if($templates->currentPage() < $templates->lastPage())
    <a href="{{ $templates->nextPageUrl() }}" class="btn btn-outline-primary" id="tplLoadMoreBtn">
        <i class="ti ti-arrow-down me-1"></i>加载更多
    </a>
    @endif
    <div class="text-secondary small mt-2">
        第 {{ $templates->currentPage() }} / {{ $templates->lastPage() }} 页
    </div>
</div>
@endif
@endif

<div class="modal modal-blur fade" id="applyTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="applyTemplateForm">
                @csrf
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
<script src="{{ asset('js/pages/user-resume-templates-index.js') }}"></script>
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tplSearchInput');
    const levelSelect = document.getElementById('tplLevelSelect');
    const sortSelect = document.getElementById('tplSortSelect');
    const focusSelect = document.getElementById('tplFocusSelect');
    const diversifySwitch = document.getElementById('tplDiversifySwitch');

    function buildUrl() {
        const params = new URLSearchParams();
        const q = searchInput ? searchInput.value.trim() : '';
        const level = levelSelect ? levelSelect.value : '';
        const sort = sortSelect ? sortSelect.value : 'featured';
        const focus = focusSelect ? focusSelect.value : '';
        const diversify = diversifySwitch ? (diversifySwitch.checked ? '1' : '0') : '0';
        const currentCategory = '{{ $category }}';
        const currentSource = '{{ $sourceSlug ?? '' }}';
        if (q) params.set('q', q);
        if (level) params.set('level', level);
        if (sort && sort !== 'featured') params.set('sort', sort);
        if (focus) params.set('focus', focus);
        params.set('diversify', diversify);
        if (currentCategory) params.set('category', currentCategory);
        if (currentSource) params.set('source', currentSource);
        return '{{ route('user.resume-templates.index') }}?' + params.toString();
    }

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.location.href = buildUrl();
            }
        });
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                if (searchInput.value.trim() === '') {
                    window.location.href = buildUrl();
                }
            }, 800);
        });
    }

    [levelSelect, sortSelect, focusSelect].forEach(function(sel) {
        if (sel) {
            sel.addEventListener('change', function() {
                window.location.href = buildUrl();
            });
        }
    });

    if (diversifySwitch) {
        diversifySwitch.addEventListener('change', function() {
            window.location.href = buildUrl();
        });
    }
});
</script>
@endpush
