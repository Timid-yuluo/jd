@extends('layouts.user')

@section('title', $resume->title)

@section('page-pretitle', '简历详情')
@section('page-title', $resume->title)

@section('page-actions')
<div class="d-flex gap-2 flex-wrap align-items-center">
    <a href="{{ route('user.resumes.editor', $resume) }}" class="btn btn-primary">
        <i class="ti ti-cube me-1"></i>智能编辑台
    </a>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-info" data-action="export-pdf">
            <i class="ti ti-file-type-pdf me-1"></i>PDF
        </button>
        @if($canExportDocx ?? false)
            <a href="{{ route('user.resumes.export-docx', $resume) }}" class="btn btn-outline-success">
                <i class="ti ti-file-type-docx me-1"></i>DOCX
            </a>
        @else
            <button type="button" class="btn btn-outline-secondary" disabled title="免费版暂不支持 DOCX 导出">
                <i class="ti ti-file-type-docx me-1"></i>DOCX
            </button>
        @endif
    </div>
    <button type="button" class="btn {{ $resume->is_shareable && $resume->share_token ? 'btn-outline-success' : 'btn-outline-secondary' }}" data-bs-toggle="modal" data-bs-target="#shareModal">
        <i class="ti ti-share me-1"></i>{{ $resume->is_shareable && $resume->share_token ? '已分享' : '分享' }}
    </button>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="ti ti-dots-vertical"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <button type="button" class="dropdown-item" data-action="copy-resume">
                <i class="ti ti-copy me-2"></i>复制原文
            </button>
            <a href="{{ route('user.resumes.versions', $resume) }}" class="dropdown-item">
                <i class="ti ti-history me-2"></i>版本历史
            </a>
            <a href="{{ route('user.jobs.analyze') }}" class="dropdown-item">
                <i class="ti ti-briefcase me-2"></i>岗位匹配
            </a>
            <div class="dropdown-divider"></div>
            <button type="button" class="dropdown-item" data-action="suggest-module-order">
                <i class="ti ti-arrows-sort me-2"></i>AI 排序建议
            </button>
            <button type="button" class="dropdown-item" data-action="translate-resume" data-direction="zh_to_en">
                <i class="ti ti-language me-2"></i>翻译为英文
            </button>
        </div>
    </div>
</div>

{{-- 分享弹窗 --}}
<div class="modal modal-blur fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">
                    <i class="ti ti-share me-2"></i>分享简历
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                @if($resume->is_shareable && $resume->share_token)
                    <div class="mb-3">
                        <label class="form-label text-secondary">分享链接</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareLinkInput" value="{{ route('share.resume', $resume->share_token) }}" readonly>
                            <button type="button" class="btn btn-primary" id="copyShareLink" title="复制链接">
                                <i class="ti ti-copy me-1"></i>复制
                            </button>
                        </div>
                        <div class="form-hint">获得此链接的人可以查看你的简历</div>
                    </div>

                    {{-- 二维码 --}}
                    <div class="mb-3 text-center">
                        <label class="form-label text-secondary d-block"><i class="ti ti-qrcode me-1"></i>扫码查看</label>
                        <div id="shareQrCode" class="d-inline-block p-2 bg-white border rounded" style="line-height:0;"></div>
                        <div class="form-hint mt-1">扫描二维码直接在手机上查看简历</div>
                    </div>
                    {{-- 分享统计 --}}
                    <div class="mb-3">
                        <div class="d-flex gap-3">
                            <div class="text-center">
                                <div class="fw-bold fs-3">{{ $resume->share_view_count ?? 0 }}</div>
                                <div class="small text-secondary">浏览次数</div>
                            </div>
                        </div>
                    </div>
                    <div id="copySuccess" class="alert alert-success py-2 d-none" role="alert">
                        <i class="ti ti-check me-1"></i>链接已复制到剪贴板
                    </div>

                    {{-- 密码保护设置 --}}
                    <div class="border-top pt-3 mt-3">
                        <form action="{{ route('user.resumes.update-share-password', $resume) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-2">
                                <label class="form-label text-secondary"><i class="ti ti-lock me-1"></i>访问密码保护</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="share_password" class="form-control" placeholder="留空则不设密码" value="{{ old('share_password', $resume->share_password ?? '') }}" maxlength="32">
                                    <button type="submit" class="btn btn-outline-primary">
                                        {{ $resume->share_password ? '更新密码' : '设置密码' }}
                                    </button>
                                </div>
                                <div class="form-hint">设置后，访问者需输入密码才能查看简历</div>
                            </div>
                        </form>
                        @if($resume->share_password)
                        <form action="{{ route('user.resumes.update-share-password', $resume) }}" method="POST" class="mt-1">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="share_password" value="">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="ti ti-lock-open me-1"></i>移除密码保护
                            </button>
                        </form>
                        @endif
                    </div>
                @else
                    <div class="text-center py-3">
                        <div class="mb-3">
                            <i class="ti ti-link-off text-secondary" style="font-size: 2.5rem;"></i>
                        </div>
                        <p class="text-secondary mb-3">分享链接尚未开启，开启后可生成唯一链接供他人查看。</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <form action="{{ route('user.resumes.toggle-share', $resume) }}" method="POST" class="w-100">
                    @csrf
                    @if($resume->is_shareable && $resume->share_token)
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="ti ti-link-off me-1"></i>关闭分享
                        </button>
                    @else
                        <button type="submit" class="btn btn-success w-100">
                            <i class="ti ti-link me-1"></i>开启分享并生成链接
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 赛道选择弹窗 --}}
<div class="modal modal-blur fade" id="trackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-compass me-2"></i>选择求职赛道</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary mb-3">选择赛道后，AI优化将使用专属策略，更精准地匹配目标岗位要求。</p>
                <form action="{{ route('user.resumes.update-career-track', $resume) }}" method="POST" id="trackForm">
                    @csrf @method('PUT')
                    <input type="hidden" name="career_track_id" id="trackIdInput" value="{{ $resume->career_track_id }}">

                    @php
                        $tracks = \App\Models\CareerTrack::active()->ordered()->get()->groupBy('category');
                        $categoryLabels = \App\Models\CareerTrack::categoryLabels();
                    @endphp

                    @foreach($tracks as $category => $group)
                        <div class="mb-3">
                            <h6 class="text-secondary mb-2">{{ $categoryLabels[$category] ?? $category }}</h6>
                            <div class="row g-2">
                                @foreach($group as $track)
                                    <div class="col-6 col-md-4">
                                        <label class="form-selectgroup-item track-option" style="cursor:pointer;">
                                            <input type="radio" name="track_radio" value="{{ $track->id }}" class="form-selectgroup-input" {{ $resume->career_track_id === $track->id ? 'checked' : '' }}>
                                            <div class="card p-2 mb-0 track-card {{ $resume->career_track_id === $track->id ? 'border-primary' : '' }}" style="transition: all .15s;">
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
                        <label class="form-selectgroup-item track-option" style="cursor:pointer;">
                            <input type="radio" name="track_radio" value="" class="form-selectgroup-input" {{ !$resume->career_track_id ? 'checked' : '' }}>
                            <span class="small text-secondary">不选择赛道（使用通用优化策略）</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" form="trackForm" class="btn btn-primary"><i class="ti ti-check me-1"></i>确认选择</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.querySelectorAll('input[name="track_radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('trackIdInput').value = radio.value;
        document.querySelectorAll('.track-card').forEach(c => c.classList.remove('border-primary'));
        if (radio.closest('.track-option')) {
            const card = radio.closest('.track-option').querySelector('.track-card');
            if (card) card.classList.add('border-primary');
        }
    });
});
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/user-resumes-show.js') }}"></script>
@endpush


@section('content')
<div data-json-route-user-resumes-export-tasks-create='@json(route('user.resumes.export-tasks.create'))' data-json-route-user-resumes-export-tasks-status-taskid-task-id='@json(route('user.resumes.export-tasks.status', ['taskId' => '__TASK_ID__']))' data-json-route-user-resumes-print-pdf-resume-resume='@json(route('user.resumes.print-pdf', ['resume' => $resume]))' data-json-export-policy='@json(['can_export_docx' => (bool) ($canExportDocx ?? false)])'>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">简历内容</h3>
            </div>
            <div class="card-body">
                @if($resume->ats_score)
                    <div class="alert alert-info mb-3">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-star me-2"></i>
                            <span>ATS评分：<strong>{{ $resume->ats_score }}分</strong></span>
                        </div>
                    </div>
                    @if($atsScoreHistory->count() >= 2)
                    <div class="card mb-3" style="border:1px solid #e9eef5;border-radius:10px;">
                        <div class="card-body py-2 px-3">
                            <div class="small fw-semibold text-secondary mb-1"><i class="ti ti-chart-line me-1"></i>评分趋势</div>
                            <svg width="100%" height="80" viewBox="0 0 300 80" preserveAspectRatio="none">
                                @php
                                    $scores = $atsScoreHistory->pluck('score')->all();
                                    $minS = max(0, min($scores) - 5);
                                    $maxS = min(100, max($scores) + 5);
                                    $range = max(1, $maxS - $minS);
                                    $pts = [];
                                    foreach ($scores as $i => $s) {
                                        $x = count($scores) > 1 ? ($i / (count($scores) - 1)) * 280 + 10 : 150;
                                        $y = 70 - (($s - $minS) / $range) * 60;
                                        $pts[] = "{$x}," . round($y, 1);
                                    }
                                @endphp
                                <polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="#4c6fff" stroke-width="2" stroke-linejoin="round"/>
                                @foreach ($scores as $i => $s)
                                    @php
                                        $x = count($scores) > 1 ? ($i / (count($scores) - 1)) * 280 + 10 : 150;
                                        $y = 70 - (($s - $minS) / $range) * 60;
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ round($y, 1) }}" r="3" fill="#4c6fff"/>
                                    <text x="{{ $x }}" y="{{ round($y, 1) - 6 }}" text-anchor="middle" font-size="9" fill="#4c6fff" font-weight="600">{{ $s }}</text>
                                @endforeach
                            </svg>
                            <div class="d-flex justify-content-between small text-secondary mt-1">
                                <span>{{ $atsScoreHistory->first()?->created_at?->format('m/d') }}</span>
                                <span>{{ $atsScoreHistory->last()?->created_at?->format('m/d') }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif

                @if($resume->ats_score || $resume->optimized_text)
                    <div class="alert alert-warning mb-3">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-triangle me-2"></i></div>
                            <div>如果你近期修改过简历正文或目标岗位，建议重新执行 AI 优化和 ATS 评分，避免继续使用旧结果。</div>
                        </div>
                    </div>
                @endif

                <div class="resume-content mb-4 p-0 border rounded bg-white overflow-hidden" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05); min-height: 400px;">
                    {{-- PDF导出源 --}}
                    <div id="resume-pdf-source" style="display: none; width: 210mm; margin: 0 auto;">
                        @if(!empty($defaultModules))
                            @php
                                $pdfTheme = $resume->theme ?? 'blue';
                            @endphp
                            @include('user.resumes.templates.' . ($resume->template ?: 'classic'), ['theme' => $pdfTheme, 'forceModules' => $defaultModules])
                        @elseif($resume->modules->isNotEmpty())
                            @include('user.resumes.templates.' . ($resume->template ?: 'classic'), ['theme' => $resume->theme])
                        @else
                            <div style="padding: 24mm;">
                                <h2 style="margin: 0 0 10px 0; font-size: 22px;">{{ $resume->title }}</h2>
                                <p style="margin: 0 0 12px 0; color: #6b7280;">目标岗位：{{ $resume->target_job ?: '未填写' }}</p>
                                <hr style="margin: 0 0 16px 0;">
                                <div style="white-space: pre-wrap; line-height: 1.7; font-size: 14px;">{{ $resume->content_raw }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- 状态栏 --}}
                    <div class="p-3 border-bottom bg-light">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-secondary-lt">原始简历</span>
                            <span class="badge {{ $resume->optimized_text ? 'bg-primary-lt' : 'bg-secondary-lt' }}">
                                {{ $resume->optimized_text ? '已生成优化版本' : '未生成优化版本' }}
                            </span>
                            <span class="badge {{ $resume->ats_score ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                {{ $resume->ats_score ? 'ATS 已评分' : 'ATS 未评分' }}
                            </span>
                            <span class="badge bg-azure-lt">
                                <i class="ti ti-palette me-1"></i>
                                {{ match($resume->template) { 'modern' => '现代模板', 'minimal' => '极简模板', 'timeline' => '时间线模板', 'creative' => '创意模板', 'elegant' => '优雅模板', default => '经典模板' } }}
                            </span>
                            @if(!empty($defaultModules) || $resume->modules->isNotEmpty())
                                <span class="badge bg-purple-lt">
                                    <i class="ti ti-cubes me-1"></i>模块化
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- 简历预览 — A4纸张效果 + 主题色，与编辑器视觉一致 --}}
                    <div class="d-flex justify-content-center py-4 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="bg-white shadow-lg"
                             style="width: 210mm; min-height: 297mm; box-sizing: border-box;
                                 @if(!empty($defaultModules))
                                     padding: 10mm; font-family: 'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif;
                                     --accent-color: {{ match($resume->theme) {
                                         'coral' => '#ff6b6b',
                                         'green' => '#27ae60',
                                         'purple' => '#7c3aed',
                                         'orange' => '#f59e0b',
                                         default => '#2563eb'
                                     } }};
                                 @elseif($resume->modules->isNotEmpty())
                                     padding: 10mm; font-family: 'PingFang SC','Microsoft YaHei','Hiragino Sans GB','WenQuanYi Micro Hei',sans-serif;
                                     --accent-color: {{ match($resume->theme) {
                                         'coral' => '#ff6b6b',
                                         'green' => '#27ae60',
                                         'purple' => '#7c3aed',
                                         'orange' => '#f59e0b',
                                         default => '#2563eb'
                                     } }};
                                 @else
                                     padding: 40px; font-family: inherit;
                                 @endif">
                            @if(!empty($defaultModules))
                                @php $previewTheme = $resume->theme ?? 'blue'; @endphp
                                @include('user.resumes.templates.' . ($resume->template ?: 'classic'), ['theme' => $previewTheme, 'forceModules' => $defaultModules])
                            @elseif($resume->modules->isNotEmpty())
                                @include('user.resumes.templates.' . ($resume->template ?: 'classic'), ['theme' => $resume->theme, 'forceModules' => $resume->modules->toArray()])
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">简历原文</h3>
                    </div>
                    <div class="card-body">
                        <pre id="resume-raw-content-secondary" class="pre-wrap text-dark" style="font-family: inherit; font-size: 0.98rem; line-height: 1.7; border: 1px solid #e5e7eb; background: #f8fafc; padding: 16px; margin: 0; border-radius: 8px;">{{ $resume->content_raw }}</pre>
                    </div>
                </div>

                @if($resume->optimized_text)
                    <div class="mt-4">
                        <h4 class="border-bottom pb-2 mb-3">
                            <i class="ti ti-wand me-2"></i>AI优化建议
                        </h4>
                        <div class="resume-optimized p-4 border rounded bg-primary-lt" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <pre class="pre-wrap text-dark" style="font-family: inherit; font-size: 1rem; line-height: 1.6; border: none; background: transparent; padding: 0;">{{ $resume->optimized_text }}</pre>
                        </div>
                    </div>
                @endif

                @if($resume->highlights)
                    <div class="mt-4">
                        <h4 class="border-bottom pb-2 mb-3">
                            <i class="ti ti-highlight me-2"></i>亮点提取
                        </h4>
                        <div class="row">
                            @foreach($resume->highlights as $highlight)
                                <div class="col-md-6 mb-2">
                                    <div class="card card-sm bg-primary-lt">
                                        <div class="card-body py-2">
                                            <i class="ti ti-check text-primary me-2"></i>{{ $highlight }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">简历信息</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">简历完善度</div>
                    @php $comp = $resume->completeness(); @endphp
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar {{ $comp >= 80 ? 'bg-success' : ($comp >= 40 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $comp }}%"></div>
                    </div>
                    <div class="small text-secondary mt-2">{{ $comp }}% · {{ $comp >= 80 ? '简历较完善' : ($comp >= 40 ? '建议补充更多模块' : '请添加基本信息和模块') }}</div>
                    {{-- 完整度检查清单 --}}
                    @php $checklist = $resume->completenessChecklist(); @endphp
                    <div class="mt-2">
                        @foreach($checklist as $item)
                            <div class="d-flex align-items-center gap-2 py-1 {{ !$item['passed'] ? 'text-secondary' : '' }}">
                                <i class="ti {{ $item['passed'] ? 'ti-circle-check text-success' : 'ti-circle text-secondary' }}" style="font-size:.85rem;"></i>
                                <span class="small {{ $item['passed'] ? 'text-dark' : '' }}">{{ $item['name'] }}</span>
                                @if(!$item['passed'])
                                    <span class="small text-muted" title="{{ $item['tip'] }}" data-bs-toggle="tooltip"><i class="ti ti-info-circle"></i></span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">当前模板</div>
                    <div class="mt-1">
                        <span class="badge bg-azure-lt">
                            {{ match($resume->template) { 'modern' => '现代', 'minimal' => '极简', 'timeline' => '时间线', 'creative' => '创意', 'elegant' => '优雅', default => '经典' } }}
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">当前主题</div>
                    <div class="mt-1">
                        @php
                            $themeColor = match($resume->theme) {
                                'coral' => '#ff6b6b',
                                'green' => '#27ae60',
                                'purple' => '#7c3aed',
                                'orange' => '#f59e0b',
                                default => '#2563eb',
                            };
                            $themeLabel = match($resume->theme) {
                                'coral' => '珊瑚红',
                                'green' => '清新绿',
                                'purple' => '紫罗兰',
                                'orange' => '活力橙',
                                default => '商务蓝',
                            };
                        @endphp
                        <span class="badge" style="background: {{ $themeColor }}; color: #fff;">
                            {{ $themeLabel }}
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">目标职位</div>
                    <div>{{ $resume->target_job ?: '未填写' }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">求职赛道</div>
                    <div class="mt-1">
                        @if($resume->careerTrack)
                            <span class="badge" style="background: {{ $resume->careerTrack->color ?? '#206bc4' }}20; color: {{ $resume->careerTrack->color ?? '#206bc4' }};">
                                @if($resume->careerTrack->icon)<i class="ti {{ $resume->careerTrack->icon }} me-1"></i>@endif
                                {{ $resume->careerTrack->name }}
                            </span>
                            <div class="small text-secondary mt-1">{{ $resume->careerTrack->description }}</div>
                        @else
                            <span class="text-secondary">未选择</span>
                            <div class="small text-secondary mt-1">选择赛道可获得专属优化策略</div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="modal" data-bs-target="#trackModal">
                        <i class="ti ti-compass me-1"></i>{{ $resume->careerTrack ? '切换赛道' : '选择赛道' }}
                    </button>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">亮点数量</div>
                    <div>{{ is_array($resume->highlights) ? count($resume->highlights) : 0 }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">创建时间</div>
                    <div>{{ $resume->created_at->format('Y-m-d H:i') }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">最后更新</div>
                    <div>{{ $resume->updated_at->format('Y-m-d H:i') }}</div>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="{{ route('user.resumes.editor', $resume) }}" class="btn btn-primary">
                        <i class="ti ti-cube me-2"></i>进入智能编辑台
                    </a>
                    <form action="{{ route('user.resumes.ats-score', $resume) }}" method="POST" data-quota-loading-text="重新评分中...">
                        @csrf
                        <button type="submit" class="btn btn-outline-info submit-btn w-100">
                            <i class="ti ti-refresh me-2"></i>重新 ATS 评分
                        </button>
                    </form>
                    <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-warning">
                        <i class="ti ti-briefcase me-2"></i>去做岗位匹配
                    </a>
                    <a href="{{ route('user.interviews.create', ['resume_id' => $resume->id]) }}" class="btn btn-success">
                        <i class="ti ti-message-chatbot me-2"></i>开始AI面试
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
