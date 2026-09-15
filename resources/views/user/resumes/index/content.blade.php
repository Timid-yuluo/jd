<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">简历总数</div>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2">{{ $stats['total'] }}</div>
                    <span class="text-secondary small">份</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">已做 ATS 评分</div>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2">{{ $stats['scored'] }}</div>
                    <span class="text-secondary small">份</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">已做 AI 优化</div>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2">{{ $stats['optimized'] }}</div>
                    <span class="text-secondary small">份</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">平均 ATS 分</div>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2">{{ $stats['average_ats'] > 0 ? $stats['average_ats'] : '--' }}</div>
                    @if($stats['average_ats'] > 0)
                        <span class="text-secondary small">分</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('user.resumes.index') }}" id="filter-form" class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">搜索简历</label>
                <input
                    type="text"
                    name="q"
                    value="{{ $keyword }}"
                    class="form-control"
                    placeholder="标题、目标职位或简历内容"
                >
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">ATS 状态</label>
                <select name="ats" class="form-select" data-auto-submit>
                    <option value="">全部</option>
                    <option value="scored" {{ $atsFilter === 'scored' ? 'selected' : '' }}>已评分</option>
                    <option value="unscored" {{ $atsFilter === 'unscored' ? 'selected' : '' }}>未评分</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">优化状态</label>
                <select name="optimized" class="form-select" data-auto-submit>
                    <option value="">全部</option>
                    <option value="yes" {{ $optimizeFilter === 'yes' ? 'selected' : '' }}>已优化</option>
                    <option value="no" {{ $optimizeFilter === 'no' ? 'selected' : '' }}>未优化</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="form-label">排序方式</label>
                <select name="sort" class="form-select" data-auto-submit>
                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>最新创建</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>最早创建</option>
                    <option value="ats_high" {{ $sort === 'ats_high' ? 'selected' : '' }}>ATS 从高到低</option>
                    <option value="ats_low" {{ $sort === 'ats_low' ? 'selected' : '' }}>ATS 从低到高</option>
                </select>
            </div>
            <div class="col-lg-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-search me-1"></i>筛选
                    </button>
                    <a href="{{ route('user.resumes.index') }}" class="btn btn-outline-secondary">
                        重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if(!$resumes->isEmpty())
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-secondary small">
            共找到 <strong>{{ $resumes->total() }}</strong> 份简历
        </div>
    </div>
@endif

<div class="row row-cards">
    @if($resumes->isEmpty())
        <div class="col-12">
            <div class="empty">
                <div class="empty-icon">
                    <i class="ti ti-file-text" style="font-size: 4rem; color: #ccc;"></i>
                </div>
                <p class="empty-title">暂无简历</p>
                <p class="empty-subtitle text-secondary">创建你的第一份简历，开启求职之旅。</p>
                <div class="empty-action">
                    <a href="{{ route('user.resumes.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>新建简历
                    </a>
                </div>
            </div>
        </div>
    @else
        @foreach($resumes as $resume)
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-lt rounded">
                                    <i class="ti ti-file-description fs-2"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-fill">
                                <h4 class="m-0 text-truncate" title="{{ $resume->title }}">{{ $resume->title }}</h4>
                                <div class="mt-1">
                                    @if($resume->ats_score)
                                        @php
                                            $atsLevel = $resume->ats_score >= 90 ? 'A' : ($resume->ats_score >= 70 ? 'B' : ($resume->ats_score >= 50 ? 'C' : 'D'));
                                            $atsColor = $resume->ats_score >= 90 ? 'success' : ($resume->ats_score >= 70 ? 'info' : ($resume->ats_score >= 50 ? 'warning' : 'danger'));
                                        @endphp
                                        <span class="badge bg-{{ $atsColor }}-lt text-{{ $atsColor }}">
                                            <strong>{{ $atsLevel }}</strong> · ATS {{ $resume->ats_score }}分
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-lt">未评分</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-secondary small mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="ti ti-briefcase me-2"></i>
                                <span class="text-truncate" title="{{ $resume->target_job ?: '未设置目标职位' }}">{{ $resume->target_job ?: '未设置目标职位' }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="ti ti-clock me-2"></i>
                                <span>更新于 {{ $resume->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @if($resume->careerTrack)
                                <span class="badge" style="background: {{ $resume->careerTrack->color ?? '#206bc4' }}15; color: {{ $resume->careerTrack->color ?? '#206bc4' }};">
                                    @if($resume->careerTrack->icon)<i class="ti {{ $resume->careerTrack->icon }} me-1"></i>@endif{{ $resume->careerTrack->name }}
                                </span>
                            @endif
                            @if($resume->optimized_text)
                                <span class="badge bg-primary-lt">已优化</span>
                            @else
                                <span class="badge bg-secondary-lt">未优化</span>
                            @endif
                            @if(!empty($resume->highlights))
                                <span class="badge bg-azure-lt">{{ count($resume->highlights) }} 条亮点</span>
                            @endif
                            <span class="badge bg-purple-lt">
                                {{ match($resume->template) { 'modern' => '现代', 'minimal' => '极简', 'timeline' => '时间线', 'creative' => '创意', 'elegant' => '优雅', default => '经典' } }}
                            </span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row g-2 mb-2">
                            <div class="col">
                                <a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-ghost-primary btn-sm w-100">
                                    <i class="ti ti-eye me-1"></i><span class="d-none d-sm-inline">查看</span>
                                </a>
                            </div>
                            <div class="col">
                                <a href="{{ route('user.resumes.print-pdf', ['resume' => $resume, 'auto_print' => 1]) }}" target="_blank" rel="noopener" class="btn btn-ghost-info btn-sm w-100">
                                    <i class="ti ti-file-type-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
                                </a>
                            </div>
                            <div class="col">
                                @if($canExportDocx ?? false)
                                    <a href="{{ route('user.resumes.export-docx', $resume) }}" class="btn btn-ghost-success btn-sm w-100">
                                        <i class="ti ti-file-type-docx me-1"></i><span class="d-none d-sm-inline">DOCX</span>
                                    </a>
                                @else
                                    <button type="button" class="btn btn-ghost-secondary btn-sm w-100" disabled title="免费版暂不支持 DOCX 导出">
                                        <i class="ti ti-file-type-docx me-1"></i><span class="d-none d-sm-inline">DOCX</span>
                                    </button>
                                @endif
                            </div>
                            <div class="col">
                                <a href="{{ route('user.resumes.editor', $resume) }}" class="btn btn-ghost-purple btn-sm w-100">
                                    <i class="ti ti-cube me-1"></i><span class="d-none d-sm-inline">编辑</span>
                                </a>
                            </div>
                            <div class="col">
                                <form action="{{ route('user.resumes.destroy', $resume) }}" method="POST" class="w-100" data-confirm-submit="确定要删除这份简历吗？删除后可在回收站中恢复。">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost-danger btn-sm w-100">
                                        <i class="ti ti-trash me-1"></i><span class="d-none d-sm-inline">删除</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                @if($resume->ats_score)
                                    @php
                                        $atsLevel = $resume->ats_score >= 90 ? 'A' : ($resume->ats_score >= 70 ? 'B' : ($resume->ats_score >= 50 ? 'C' : 'D'));
                                    @endphp
                                    <a href="{{ route('user.resumes.ats-report', $resume) }}" class="btn btn-ghost-info btn-sm w-100">
                                        <i class="ti ti-chart-bar me-1"></i>ATS {{ $atsLevel }} · {{ $resume->ats_score }}分
                                    </a>
                                @else
                                    <form action="{{ route('user.resumes.ats-score', $resume) }}" method="POST" class="w-100" data-quota-loading-text="评分中...">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost-info btn-sm w-100 submit-btn">
                                            <i class="ti ti-star me-1"></i>ATS评分
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="col">
                                <a href="{{ route('user.resumes.editor', $resume) }}" class="btn btn-ghost-primary btn-sm w-100" title="进入编辑台后使用 AI 优化功能">
                                    <i class="ti ti-sparkles me-1"></i>AI优化
                                </a>
                            </div>
                            <div class="col">
                                <form action="{{ route('user.resumes.duplicate', $resume) }}" method="POST" class="w-100">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost-secondary btn-sm w-100" title="复制此简历">
                                        <i class="ti ti-copy me-1"></i>复制
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @if($resumes->hasPages())
        <div class="col-12 mt-4">
            <div class="d-flex justify-content-center">
                {{ $resumes->links() }}
            </div>
        </div>
    @endif
    @endif
</div>
