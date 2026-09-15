@extends('layouts.user')

@section('title', '编辑简历')

@section('page-pretitle', '简历管理')
@section('page-title', '编辑简历')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('user.resumes.update', $resume) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">基本信息</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">简历标题</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title', $resume->title) }}" placeholder="例如：前端开发工程师简历" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">目标职位</label>
                                <input type="text" name="target_job" class="form-control @error('target_job') is-invalid @enderror"
                                    value="{{ old('target_job', $resume->target_job) }}" placeholder="例如：前端开发工程师">
                                @error('target_job')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">简历模板</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="template" value="classic" class="form-selectgroup-input" {{ old('template', $resume->template ?? 'classic') === 'classic' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex flex-column align-items-center p-3">
                                        <i class="ti ti-layout-list mb-2 fs-2"></i>
                                        <span class="fw-medium">经典</span>
                                        <span class="text-secondary small">传统上下布局</span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-4">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="template" value="modern" class="form-selectgroup-input" {{ old('template', $resume->template ?? 'classic') === 'modern' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex flex-column align-items-center p-3">
                                        <i class="ti ti-layout-sidebar-left mb-2 fs-2"></i>
                                        <span class="fw-medium">现代</span>
                                        <span class="text-secondary small">双栏侧边栏</span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-4">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="template" value="minimal" class="form-selectgroup-input" {{ old('template', $resume->template ?? 'classic') === 'minimal' ? 'checked' : '' }}>
                                    <span class="form-selectgroup-label d-flex flex-column align-items-center p-3">
                                        <i class="ti ti-minus mb-2 fs-2"></i>
                                        <span class="fw-medium">极简</span>
                                        <span class="text-secondary small">优雅留白</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        @error('template')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">简历内容</h3>
                    <div class="card-actions">
                        <input type="file" id="resume-file" accept=".txt,.md,.pdf,application/pdf" style="display: none;" data-action="import-file">
                        <button type="button" id="resume-import-btn" class="btn btn-outline-primary btn-sm" data-action="import-resume">
                            <i class="ti ti-upload me-1"></i>重新导入 (.txt, .md, .pdf)
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">简历内容</label>
                        <textarea name="content_raw" id="content_raw" rows="20" class="form-control @error('content_raw') is-invalid @enderror"
                            placeholder="请粘贴你的简历内容..." required>{{ old('content_raw', $resume->content_raw) }}</textarea>
                        @error('content_raw')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-2">
                            <div class="form-hint">修改正文后，建议重新执行 AI 优化和 ATS 评分。</div>
                            <div class="form-hint text-end">
                                <span id="content-count">0</span> 字符 / <span id="line-count">0</span> 行
                            </div>
                        </div>
                        <div id="import-status" class="form-hint mt-2 text-secondary" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('user.resumes.show', $resume) }}" class="btn btn-outline-secondary">取消</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-2"></i>保存修改
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">编辑提示</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">当前状态</div>
                    <div class="mt-2">
                        <span class="badge bg-secondary-lt" id="title-status">待检查标题</span>
                        <span class="badge bg-secondary-lt" id="target-status">待检查目标岗位</span>
                        <span class="badge bg-secondary-lt" id="content-status">正文较少</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small mb-2">当前 AI 状态</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge {{ $resume->optimized_text ? 'bg-primary-lt' : 'bg-secondary-lt' }}">
                            {{ $resume->optimized_text ? '已有优化版本' : '暂无优化版本' }}
                        </span>
                        <span class="badge {{ $resume->ats_score ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                            {{ $resume->ats_score ? '已有 ATS 分数' : '暂无 ATS 分数' }}
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small mb-2">建议检查</div>
                    <ul class="list-unstyled text-secondary mb-0">
                        <li class="mb-2">确认目标岗位是否与当前投递方向一致。</li>
                        <li class="mb-2">确认项目成果是否有明确数字支撑。</li>
                        <li>确认技术关键词覆盖了岗位 JD 的核心要求。</li>
                    </ul>
                </div>
                <div class="alert alert-warning mb-0">
                    如果本次修改较大，建议保存后重新执行 AI 优化和 ATS 评分。
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/pdfjs/pdf.min.mjs') }}" type="module"></script>
@endpush

@push('scripts')
<script src="{{ asset('js/pages/user-resumes-edit.js') }}"></script>
@endpush