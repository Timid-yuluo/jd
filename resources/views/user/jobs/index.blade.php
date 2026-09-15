@extends('layouts.user')

@section('title', '岗位匹配分析')

@section('page-pretitle', '智能分析')
@section('page-title', '岗位匹配分析')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card analyze-workbench-card">
            <div class="card-header analyze-workbench-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <ul class="nav nav-pills analyze-workbench-tabs" id="analyze-main-tabs" role="tablist" aria-label="岗位分析切换">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active px-3 py-2 fs-4 fw-semibold" data-analyze-tab="manual" aria-selected="true">手动填写</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link px-3 py-2 fs-4 fw-semibold" data-analyze-tab="history" aria-selected="false">历史复用</button>
                    </li>
                </ul>
                <h3 class="card-title mb-0 fs-3">粘贴岗位描述</h3>
            </div>
            <div class="card-body analyze-main-workspace analyze-workbench-body">
                <div data-analyze-panel="manual" class="analyze-tab-panel">
                @php
                    $prefillResumeId = old('resume_id', $prefillHistory?->resume_id);
                    $jobDescriptionMaxLength = max(500, (int) config('job-matching.job_description_max_length', 5000));
                @endphp
                @if(isset($quotaCheck))
                    @if($quotaCheck['allowed'])
                        <div class="alert alert-success py-2 d-flex align-items-center gap-2 flex-wrap">
                            <i class="ti ti-check me-1"></i>
                            <span>{{ $creditsHint }}</span>
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
                                        <div><i class="ti ti-ticket me-1"></i>本月免费次数已用完，但你还有 <strong>{{ count($availableCredits) }}</strong> 张可用次卡。</div>
                                        <div class="small text-secondary mt-1">提交分析时，系统将弹出次卡选择框，选择后可继续分析。</div>
                                    @else
                                        <div><i class="ti ti-alert-triangle me-1"></i>本月岗位匹配次数已用完，且暂无可用次卡。</div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-ticket me-1"></i>购买次卡
                                    </a>
                                    <a href="{{ route('user.membership.pricing') }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-crown me-1"></i>升级套餐
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
                <form action="{{ route('user.jobs.analyze.submit') }}" method="POST" id="analyzeForm" data-auto-analyze="{{ !empty($autoAnalyze) ? '1' : '0' }}">
                    @csrf
                    @if($prefillHistory)
                        <div class="alert alert-primary py-2">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="small">
                                    <i class="ti ti-history me-1"></i>
                                    已复用历史记录 #{{ $prefillHistory->id }}（{{ $prefillHistory->created_at?->format('Y-m-d H:i') }}）
                                </div>
                                <a href="{{ route('user.jobs.analyze') }}" class="btn btn-sm btn-outline-secondary">
                                    清除复用
                                </a>
                            </div>
                        </div>
                    @endif
                    @if($prefillExternal)
                        <div class="alert alert-info py-2">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="small">
                                    <i class="ti ti-briefcase me-1"></i>
                                    已带入外部岗位：{{ $prefillExternal->company ?: '未命名公司' }} / {{ $prefillExternal->title ?: '未命名岗位' }}
                                </div>
                                <a href="{{ route('user.jobs.analyze') }}" class="btn btn-sm btn-outline-secondary">
                                    清除带入
                                </a>
                            </div>
                        </div>
                    @endif
                    @if(!empty($autoAnalyze))
                        <div class="alert alert-primary py-2">
                            <i class="ti ti-player-play me-1"></i>已启用一键分析，页面会自动提交当前 JD。
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">选择简历</label>
                        <select name="resume_id" class="form-select analyze-field">
                            <option value="">自动使用最近更新的简历</option>
                            @foreach($resumes as $resume)
                                @php
                                    $completeness = $resume->completeness();
                                    $timeDiff = $resume->updated_at?->diffForHumans() ?? '';
                                @endphp
                                <option value="{{ $resume->id }}" {{ (string) $prefillResumeId === (string) $resume->id ? 'selected' : '' }}>
                                    {{ $resume->title }}　{{ $completeness }}%完整　{{ $timeDiff }}
                                </option>
                            @endforeach
                        </select>
                        @error('resume_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @if($resumes->isEmpty())
                            <div class="alert alert-warning mt-2 mb-0 py-2">
                                暂无可用简历，请先创建简历后再分析。
                                <a href="{{ route('user.resumes.create') }}" class="alert-link ms-1">去创建</a>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0">岗位描述 (JD)</label>
                            <button type="button" class="btn btn-sm btn-outline-warning" id="bookmarkJdBtn" title="收藏当前 JD">
                                <i class="ti ti-bookmark me-1"></i>收藏
                            </button>
                        </div>
                        <textarea name="job_description" class="form-control analyze-field @error('job_description') is-invalid @enderror"
                            rows="5" minlength="50" maxlength="{{ $jobDescriptionMaxLength }}" placeholder="请粘贴完整的岗位描述..." required style="min-height:120px;resize:vertical;">{{ $prefillJobDescription }}</textarea>
                        <div class="form-hint mt-1">
                            建议 50-{{ $jobDescriptionMaxLength }} 字，当前 <span id="jobDescriptionLength">{{ mb_strlen((string) $prefillJobDescription) }}</span> / {{ $jobDescriptionMaxLength }} 字
                        </div>
                        @error('job_description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback d-none" id="jobDescriptionClientError">岗位描述至少需要 50 字，请补充后再分析。</div>
                    </div>

                    @if(!empty($availableCredits))
                    <div class="border rounded p-3 mb-3 bg-light-subtle">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="useCreditSwitch" name="use_credit" value="1">
                            <label class="form-check-label fw-medium" for="useCreditSwitch">
                                <i class="ti ti-ticket me-1"></i>优先使用次卡（不消耗本月免费次数）
                            </label>
                        </div>
                        <div class="mt-2 d-none" id="creditSelectGroup">
                            <label class="form-label small">选择次卡</label>
                            <select name="credit_id" class="form-select form-select-sm" id="creditSelect" disabled>
                                @foreach($availableCredits as $credit)
                                    <option value="{{ $credit->id }}" data-remaining="{{ $credit->remaining }}">
                                        {{ $credit->name ?: ($credit->is_universal ? '通用次卡' : '岗位匹配次卡') }}
                                        （剩余 {{ $credit->remaining }} 次{{ $credit->is_universal ? '，通用' : '' }}）
                                        @if($credit->expires_at) · 到期 {{ date('Y-m-d', strtotime((string) $credit->expires_at)) }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif

                    <div class="d-flex gap-2 align-items-center flex-wrap analyze-action-row">
                        @if($resumes->isEmpty())
                            <a href="{{ route('user.resumes.create') }}" class="btn btn-primary analyze-btn-primary">
                                <i class="ti ti-plus me-2"></i>先创建简历
                            </a>
                        @else
                            <button type="submit" class="btn btn-primary analyze-btn-primary" id="analyzeBtn" data-default-text="开始分析">
                                <i class="ti ti-sparkles me-2"></i><span class="btn-text">开始分析</span>
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-secondary analyze-btn-secondary" id="clearFormBtn">
                            <i class="ti ti-refresh me-2"></i>清空
                        </button>
                        <a href="{{ route('user.jobs.batch') }}" class="btn btn-outline-secondary analyze-btn-secondary">
                            <i class="ti ti-stack me-2"></i>批量分析
                        </a>
                        <a href="{{ route('user.jobs.bookmarks') }}" class="btn btn-outline-secondary analyze-btn-secondary">
                            <i class="ti ti-bookmark me-2"></i>岗位收藏
                        </a>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2 d-none" id="analyzeProgressHint" role="status">
                        <i class="ti ti-loader-2 me-2"></i>分析请求已提交，正在处理中。若等待较久请不要重复提交。
                    </div>
                </form>
                </div>

                <div data-analyze-panel="history" class="analyze-tab-panel d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="text-secondary analyze-section-caption">选择历史分析，一键复用到当前页面。</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="batchDeleteBtn">
                                <i class="ti ti-trash me-1"></i>批量删除 (<span id="batchDeleteCount">0</span>)
                            </button>
                            <a href="{{ route('user.jobs.analyze.history.index') }}" class="btn btn-outline-primary px-3 py-2 fs-4">查看全部</a>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('user.jobs.analyze') }}" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="history_search" class="form-control" placeholder="搜索历史记录（公司/岗位/关键词）" value="{{ $historySearch ?? '' }}">
                            <button class="btn btn-outline-primary" type="submit"><i class="ti ti-search"></i></button>
                            @if(!empty($historySearch))
                                <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-secondary">清除</a>
                            @endif
                        </div>
                    </form>
                    @if(isset($recentAnalyses) && $recentAnalyses->isNotEmpty())
                        <div class="list-group list-group-flush analyze-history-list">
                            @foreach($recentAnalyses as $item)
                                @php
                                    $historyScore = (int) ($item->match_score ?? 0);
                                    $historyBadge = $historyScore >= 82 ? 'bg-success-lt text-success' : ($historyScore >= 70 ? 'bg-info-lt text-info' : ($historyScore >= 55 ? 'bg-warning-lt text-warning' : 'bg-danger-lt text-danger'));
                                    $jdPreview = \Illuminate\Support\Str::limit(trim((string) $item->job_description), 60);
                                @endphp
                                <div class="list-group-item px-0 analyze-history-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="d-flex align-items-start gap-2">
                                            <input type="checkbox" class="form-check-input mt-1 history-check" data-id="{{ $item->id }}">
                                            <div>
                                                <div class="fw-medium mb-1">
                                                    {{ $item->resume?->title ?? '简历已删除' }}
                                                    <span class="badge {{ $historyBadge }} ms-1">{{ $historyScore }} 分</span>
                                                </div>
                                                @if($jdPreview)
                                                    <div class="text-secondary small mb-1" title="{{ e((string) $item->job_description) }}">JD: {{ $jdPreview }}</div>
                                                @endif
                                                <div class="text-secondary mb-1">
                                                    {{ \Illuminate\Support\Str::limit((string) $item->summary, 80) ?: '已生成岗位匹配分析结果' }}
                                                </div>
                                                <div class="text-secondary small">{{ $item->created_at?->format('Y-m-d H:i') }}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            <a href="{{ route('user.jobs.analyze.history', ['analysis' => $item->id]) }}" class="btn btn-sm btn-outline-primary analyze-mini-btn">
                                                查看
                                            </a>
                                            <a href="{{ route('user.jobs.analyze', ['from_history' => $item->id]) }}" class="btn btn-sm btn-outline-secondary analyze-mini-btn">
                                                复用
                                            </a>
                                            <form action="{{ route('user.jobs.analyze.history.destroy', ['analysis' => $item->id]) }}" method="POST" class="delete-history-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 analyze-mini-btn">删除</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-secondary">{{ !empty($historySearch) ? '未找到匹配的历史记录。' : '暂无历史分析记录，完成一次匹配分析后会自动保存在这里。' }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="card mt-3 analyze-workbench-card">
            <div class="card-header analyze-workbench-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h3 class="card-title mb-0 fs-3">分析辅助</h3>
                <ul class="nav nav-pills analyze-workbench-tabs" id="analyze-help-tabs" role="tablist" aria-label="分析辅助切换">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link active px-3 py-2 fs-4 fw-semibold" data-help-tab="guide" aria-selected="true">使用说明</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link px-3 py-2 fs-4 fw-semibold" data-help-tab="sample" aria-selected="false">分析示例</button>
                    </li>
                </ul>
            </div>
            <div class="card-body analyze-workbench-body">
                <div data-help-panel="guide">
                    <div class="text-secondary">
                        <p><i class="ti ti-info-circle me-2"></i>岗位匹配分析可以帮助您：</p>
                        <ul class="mb-0">
                            <li class="mb-2">评估简历与岗位的匹配度</li>
                            <li class="mb-2">发现简历中的优势和不足</li>
                            <li class="mb-2">获取针对性的改进建议</li>
                            <li class="mb-2">识别岗位要求的关键技能</li>
                        </ul>
                    </div>
                </div>
                <div data-help-panel="sample" class="d-none">
                    <div class="small text-secondary analyze-section-caption">
                        <p class="mb-2">您可以粘贴类似的岗位描述：</p>
                        <ul class="mb-0">
                            <li>岗位职责和要求</li>
                            <li>任职资格</li>
                            <li>技能要求</li>
                            <li>工作经验要求</li>
                            <li>学历要求</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .analyze-workbench-card {
        border-radius: 14px;
        border: 1px solid #e7eef7;
        box-shadow: 0 6px 18px rgba(17, 24, 39, 0.04);
    }

    .analyze-workbench-header {
        background: #f8fbff;
        border-bottom: 1px solid #e7eef7;
    }

    .analyze-workbench-body {
        padding: 1.25rem;
    }

    .analyze-workbench-tabs .nav-link {
        border-radius: 10px;
    }

    .analyze-main-workspace {
        min-height: 620px;
    }

    .analyze-tab-panel {
        height: 100%;
    }

    .analyze-field {
        min-height: 48px;
        border-radius: 10px;
    }

    .analyze-btn-primary,
    .analyze-btn-secondary {
        min-height: 44px;
        border-radius: 10px;
        font-weight: 600;
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .analyze-action-row {
        margin-top: 0.25rem;
    }

    .analyze-history-list {
        max-height: 460px;
        overflow: auto;
        padding-right: 0.25rem;
    }

    .analyze-history-item {
        border-bottom: 1px dashed #e7eef7;
    }

    .analyze-mini-btn {
        min-height: 34px;
        border-radius: 8px;
        font-weight: 600;
    }

    .analyze-section-caption {
        line-height: 1.65;
    }
    .border-purple { border-color: #9333ea !important; }
    .text-purple { color: #9333ea !important; }
</style>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-jobs-index.js') }}"></script>
@endpush
