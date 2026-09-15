@extends('layouts.user')

@section('title', '控制台')

@section('page-pretitle', '用户中心')
@section('page-title', '控制台')

@section('content')
@php $plan = Auth::user()->currentPlan(); @endphp

{{-- 系统公告轮播 --}}
@if(!empty($announcements))
<div class="card mb-4 announcement-banner" id="announcementBanner">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center">
            <span class="badge bg-primary-lt text-primary me-3"><i class="ti ti-speakerphone me-1"></i>公告</span>
            <div class="announcement-carousel flex-fill" id="announcementCarousel">
                @foreach($announcements as $idx => $announcement)
                <div class="announcement-item {{ $idx === 0 ? 'active' : '' }}" data-index="{{ $idx }}">
                    <span class="fw-medium">{{ $announcement['title'] }}</span>
                    <span class="text-secondary ms-2 small">{{ \Carbon\Carbon::parse($announcement['sent_at'])->diffForHumans() }}</span>
                </div>
                @endforeach
            </div>
            @if(count($announcements) > 1)
            <div class="announcement-dots ms-3">
                @foreach($announcements as $idx => $a)
                <span class="dot {{ $idx === 0 ? 'active' : '' }}" data-index="{{ $idx }}"></span>
                @endforeach
            </div>
            @endif
            <button type="button" class="btn-close ms-3" id="dismissAnnouncement" title="关闭"></button>
        </div>
    </div>
</div>
<style>
.announcement-banner { border-left: 3px solid #4c6fff; }
.announcement-carousel { position: relative; overflow: hidden; height: 24px; }
.announcement-item { position: absolute; top: 0; left: 0; width: 100%; opacity: 0; transition: opacity .4s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 24px; }
.announcement-item.active { opacity: 1; }
.announcement-dots { display: flex; gap: 4px; align-items: center; }
.announcement-dots .dot { width: 6px; height: 6px; border-radius: 50%; background: #ccc; cursor: pointer; transition: background .2s; }
.announcement-dots .dot.active { background: #4c6fff; }
</style>
@endif

{{-- 新用户引导 --}}
@if($showOnboarding ?? false)
<div class="card onboarding-card mb-4" id="onboardingGuide">
    <div class="card-body py-4 px-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="ti ti-rocket me-2 text-primary"></i>欢迎使用职路通</h3>
                <p class="text-secondary mb-0">按照以下步骤，快速开启你的求职之旅</p>
            </div>
            <button type="button" class="btn-close" id="dismissOnboarding" title="关闭引导"></button>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="onboarding-step">
                    <div class="onboarding-step-num">1</div>
                    <div class="onboarding-step-body">
                        <div class="fw-semibold mb-1">创建简历</div>
                        <div class="small text-secondary mb-2">使用智能编辑器快速创建专业简历</div>
                        <a href="{{ route('user.resumes.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>创建简历</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="onboarding-step">
                    <div class="onboarding-step-num">2</div>
                    <div class="onboarding-step-body">
                        <div class="fw-semibold mb-1">ATS 评分</div>
                        <div class="small text-secondary mb-2">AI 分析简历通过率，获取优化建议</div>
                        <a href="{{ route('user.resumes.create') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-robot me-1"></i>了解评分</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="onboarding-step">
                    <div class="onboarding-step-num">3</div>
                    <div class="onboarding-step-body">
                        <div class="fw-semibold mb-1">模拟面试</div>
                        <div class="small text-secondary mb-2">AI 模拟真实面试，提升实战能力</div>
                        <a href="{{ route('user.interviews.create') }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-player-play me-1"></i>开始面试</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card dashboard-hero mb-4 overflow-hidden">
    <div class="dashboard-hero-glow"></div>
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
        <div>
            @php
                $hour = (int) now()->format('H');
                $greeting = $hour < 6 ? '夜深了' : ($hour < 12 ? '早上好' : ($hour < 18 ? '下午好' : '晚上好'));
                $motivation = '';
                if ($stats['resume_count'] === 0) {
                    $motivation = '创建你的第一份简历，开启求职之旅';
                } elseif ($stats['interview_count'] === 0) {
                    $motivation = '试试 AI 模拟面试，提升面试技巧';
                } elseif ($stats['resume_count'] > 0 && $stats['interview_count'] > 0) {
                    $motivation = '今天也加油拿 Offer';
                }
            @endphp
            <div class="dashboard-kicker mb-2">{{ $greeting }}</div>
            <h2 class="dashboard-heading mb-2">{{ Auth::user()->name }}，{{ $motivation }}</h2>
            <div class="dashboard-subtitle">当前套餐：<span class="fw-semibold text-body">{{ $plan ? $plan->name : '免费版' }}</span></div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.resumes.create') }}" class="btn btn-primary" data-guide-id="dash-create-resume" data-guide-text="点击这里创建你的第一份简历，AI 将辅助你完成" data-guide-position="bottom">
                <i class="ti ti-plus me-1"></i>新建简历
            </a>
            <a href="{{ route('user.interviews.create') }}" class="btn btn-outline-primary" data-guide-id="dash-start-interview" data-guide-text="选择岗位开始 AI 模拟面试，提升实战能力" data-guide-position="bottom">
                <i class="ti ti-player-play me-1"></i>开始面试
            </a>
            <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>个人设置
            </a>
        </div>
    </div>
</div>

<div class="row g-3 dashboard-stat-grid">
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-secondary mb-1">我的简历</div>
                        <div class="dashboard-stat-number">{{ $stats['resume_count'] }}</div>
                    </div>
                    <span class="dashboard-stat-icon bg-primary"><i class="ti ti-file-text"></i></span>
                </div>
                <a href="{{ route('user.resumes.index') }}" class="small text-decoration-none dashboard-link">去管理简历 <i class="ti ti-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-secondary mb-1">AI 面试</div>
                        <div class="dashboard-stat-number">{{ $stats['interview_count'] }}</div>
                    </div>
                    <span class="dashboard-stat-icon bg-azure"><i class="ti ti-message-chatbot"></i></span>
                </div>
                <a href="{{ route('user.interviews.index') }}" class="small text-decoration-none dashboard-link">查看面试记录 <i class="ti ti-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-secondary mb-1">今日新增岗位</div>
                        <div class="dashboard-stat-number">{{ $stats['today_new_jobs'] }}</div>
                    </div>
                    <span class="dashboard-stat-icon bg-green"><i class="ti ti-briefcase"></i></span>
                </div>
                <a href="{{ route('user.kanban.index') }}" class="small text-decoration-none dashboard-link">查看岗位看板 <i class="ti ti-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card dashboard-stat h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-secondary mb-1">会员套餐</div>
                        <div class="dashboard-plan-name">{{ $plan ? $plan->name : '免费版' }}</div>
                    </div>
                    <span class="dashboard-stat-icon bg-warning"><i class="ti ti-crown"></i></span>
                </div>
                <a href="{{ route('user.membership.pricing') }}" class="small text-decoration-none dashboard-link">查看套餐权益 <i class="ti ti-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

{{-- 求职进度总览 + 快捷操作 --}}
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card h-100" style="border-radius:12px;border:1px solid #e9eef5;">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-chart-dots-3 me-2 text-primary-custom"></i>求职进度总览</h3>
            </div>
            <div class="card-body">
                @php
                    $dashboardUser = Auth::user();
                    $kanbanStats = \Illuminate\Support\Facades\Cache::remember("user:dashboard:{$dashboardUser->id}:kanban_stats", 120, function() use ($dashboardUser) {
                        try {
                            $row = \App\Models\JobApplication::where('user_id', $dashboardUser->id)
                                ->selectRaw("
                                    COUNT(*) as total,
                                    SUM(CASE WHEN status = 'wishlist' THEN 1 ELSE 0 END) as wishlist,
                                    SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied,
                                    SUM(CASE WHEN status = 'interviewing' THEN 1 ELSE 0 END) as interviewing,
                                    SUM(CASE WHEN status = 'offered' THEN 1 ELSE 0 END) as offered,
                                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                                ")->first();
                            return $row ? (array) $row->getAttributes() : ['total'=>0,'wishlist'=>0,'applied'=>0,'interviewing'=>0,'offered'=>0,'rejected'=>0];
                        } catch (\Throwable) {
                            return ['total'=>0,'wishlist'=>0,'applied'=>0,'interviewing'=>0,'offered'=>0,'rejected'=>0];
                        }
                    });
                @endphp
                <div class="row g-3 text-center">
                    <div class="col">
                        <div class="fw-bold fs-3 text-secondary">{{ $kanbanStats['wishlist'] }}</div>
                        <div class="small text-secondary">待投递</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-3 text-info">{{ $kanbanStats['applied'] }}</div>
                        <div class="small text-secondary">已投递</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-3 text-warning">{{ $kanbanStats['interviewing'] }}</div>
                        <div class="small text-secondary">面试中</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-3 text-success">{{ $kanbanStats['offered'] }}</div>
                        <div class="small text-secondary">已录用</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-3 text-danger">{{ $kanbanStats['rejected'] }}</div>
                        <div class="small text-secondary">未通过</div>
                    </div>
                </div>
                @if($kanbanStats['total'] > 0)
                <div class="progress mt-3" style="height:10px;" role="progressbar" aria-label="求职进度" aria-valuenow="{{ $kanbanStats['total'] }}" aria-valuemin="0" aria-valuemax="{{ $kanbanStats['total'] }}">
                    @php $t = max(1, $kanbanStats['total']); @endphp
                    <div class="progress-bar bg-secondary" style="width:{{ round($kanbanStats['wishlist']/$t*100) }}%" aria-label="待投递 {{ $kanbanStats['wishlist'] }}"></div>
                    <div class="progress-bar bg-info" style="width:{{ round($kanbanStats['applied']/$t*100) }}%" aria-label="已投递 {{ $kanbanStats['applied'] }}"></div>
                    <div class="progress-bar bg-warning" style="width:{{ round($kanbanStats['interviewing']/$t*100) }}%" aria-label="面试中 {{ $kanbanStats['interviewing'] }}"></div>
                    <div class="progress-bar bg-success" style="width:{{ round($kanbanStats['offered']/$t*100) }}%" aria-label="已录用 {{ $kanbanStats['offered'] }}"></div>
                    <div class="progress-bar bg-danger" style="width:{{ round($kanbanStats['rejected']/$t*100) }}%" aria-label="未通过 {{ $kanbanStats['rejected'] }}"></div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100" style="border-radius:12px;border:1px solid #e9eef5;">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-bolt me-2 text-primary-custom"></i>快捷操作</h3>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('user.resumes.create') }}" class="btn btn-outline-primary btn-sm text-start">
                    <i class="ti ti-plus me-2"></i>新建简历
                </a>
                <a href="{{ route('user.interviews.create') }}" class="btn btn-outline-azure btn-sm text-start">
                    <i class="ti ti-player-play me-2"></i>开始面试
                </a>
                <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-warning btn-sm text-start">
                    <i class="ti ti-radar me-2"></i>岗位匹配
                </a>
                <a href="{{ route('user.kanban.index') }}" class="btn btn-outline-success btn-sm text-start">
                    <i class="ti ti-layout-kanban me-2"></i>求职看板
                </a>
                <a href="{{ route('user.offers.compare') }}" class="btn btn-outline-info btn-sm text-start">
                    <i class="ti ti-scale me-2"></i>Offer 对比
                </a>
                <a href="{{ route('user.interviews.calendar') }}" class="btn btn-outline-dark btn-sm text-start">
                    <i class="ti ti-calendar me-2"></i>面试日历
                </a>
                <a href="{{ route('user.membership.pricing') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="ti ti-crown me-2"></i>升级套餐
                </a>
            </div>
        </div>
    </div>
</div>

@if(count($todoItems) > 0)
<div class="card dashboard-todo mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-semibold text-body"><i class="ti ti-list-check me-1"></i>待办提醒</span>
            @foreach($todoItems as $item)
                <a href="{{ $item['url'] }}" class="dashboard-todo-item d-inline-flex align-items-center gap-1 text-decoration-none">
                    <i class="ti {{ $item['icon'] }}"></i>
                    <span>{{ $item['text'] }}</span>
                    <span class="badge {{ $item['badge_class'] }} rounded-pill px-2 py-1" style="font-size:.72rem">{{ $item['badge'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="row mt-1 g-4">
    <div class="col-lg-4">
        <div class="card dashboard-list-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="ti ti-file-text me-2 text-primary-custom"></i>最近简历
                </h3>
                @if(!empty($recentResumes))
                    <a href="{{ route('user.resumes.index') }}" class="text-decoration-none text-secondary small">
                        查看全部 <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if(empty($recentResumes))
                        <div class="text-center py-5">
                        <i class="ti ti-file-x text-secondary fs-1 mb-2"></i>
                        <div class="text-secondary">暂无简历</div>
                        <a href="{{ route('user.resumes.create') }}" class="btn btn-primary btn-sm mt-3">
                            <i class="ti ti-plus me-1"></i>创建第一个简历
                        </a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentResumes as $resume)
                            <a href="{{ route('user.resumes.show', $resume['id']) }}" class="list-group-item list-group-item-action px-0 dashboard-list-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-fill me-3">
                                        <div class="fw-medium text-body">{{ $resume['title'] }}</div>
                                        <div class="text-secondary small">{{ \Carbon\Carbon::parse($resume['created_at'])->diffForHumans() }}</div>
                                        @php
                                            $moduleCount = $resume['modules_count'] ?? 0;
                                            $comp = min(100, (int) round($moduleCount / 8 * 100));
                                        @endphp
                                        <div class="progress mt-1" style="height:4px;width:120px;">
                                            <div class="progress-bar bg-{{ $comp >= 80 ? 'success' : ($comp >= 40 ? 'warning' : 'danger') }}" style="width:{{ $comp }}%"></div>
                                        </div>
                                        <div class="small text-secondary mt-1">完善度 {{ $comp }}%</div>
                                    </div>
                                    @if($resume['ats_score'])
                                        <span class="badge bg-primary-custom dashboard-badge">{{ $resume['ats_score'] }}分</span>
                                    @else
                                        <span class="badge bg-secondary dashboard-badge">未测评</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card dashboard-list-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="ti ti-message-chatbot me-2 text-primary-custom"></i>最近面试
                </h3>
                @if(!empty($recentInterviews))
                    <a href="{{ route('user.interviews.index') }}" class="text-decoration-none text-secondary small">
                        查看全部 <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if(empty($recentInterviews))
                    <div class="text-center py-5">
                        <i class="ti ti-message-dots text-secondary fs-1 mb-2"></i>
                        <div class="text-secondary">暂无面试记录</div>
                        <a href="{{ route('user.interviews.create') }}" class="btn btn-primary btn-sm mt-3">
                            <i class="ti ti-player-play me-1"></i>开始模拟面试
                        </a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentInterviews as $interview)
                            <a href="{{ route('user.interviews.show', $interview['id']) }}" class="list-group-item list-group-item-action px-0 dashboard-list-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium text-body">{{ $interview['resume']['title'] ?? '简历面试' }}</div>
                                        <div class="text-secondary small">{{ \Carbon\Carbon::parse($interview['created_at'])->diffForHumans() }}</div>
                                    </div>
                                    <span class="badge bg-{{ $interview['status'] === 'completed' ? 'success' : 'warning' }} dashboard-badge">
                                        {{ $interview['status'] === 'completed' ? '已完成' : '进行中' }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card dashboard-list-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="ti ti-radar me-2 text-primary-custom"></i>岗位匹配分析
                </h3>
                @if(!empty($recentMatchAnalyses))
                    <a href="{{ route('user.jobs.analyze') }}" class="text-decoration-none text-secondary small">
                        查看全部 <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if(empty($recentMatchAnalyses))
                    <div class="text-center py-5">
                        <i class="ti ti-radar-off text-secondary fs-1 mb-2"></i>
                        <div class="text-secondary">暂无匹配分析记录</div>
                        <a href="{{ route('user.jobs.analyze') }}" class="btn btn-outline-primary btn-sm mt-3">
                            <i class="ti ti-plus me-1"></i>开始岗位匹配
                        </a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentMatchAnalyses as $analysis)
                            <a href="{{ route('user.jobs.analyze') }}" class="list-group-item list-group-item-action px-0 dashboard-list-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium text-body text-truncate" style="max-width:200px">{{ $analysis['resume']['title'] ?? '岗位匹配分析' }}</div>
                                        <div class="text-secondary small">{{ \Carbon\Carbon::parse($analysis['created_at'])->diffForHumans() }}</div>
                                    </div>
                                    @if($analysis['match_score'])
                                        <span class="badge bg-{{ $analysis['match_score'] >= 80 ? 'success' : ($analysis['match_score'] >= 60 ? 'warning' : 'danger') }} dashboard-badge">
                                            {{ $analysis['match_score'] }}分
                                        </span>
                                    @else
                                        <span class="badge bg-secondary dashboard-badge">--分</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    .dashboard-hero {
        border: 1px solid var(--color-border-primary, #e4ebf5);
        border-radius: 16px;
        background: linear-gradient(135deg, var(--color-bg-tertiary, #f5f9ff) 0%, var(--color-bg-card, #ffffff) 48%, var(--color-bg-secondary, #f8f7ff) 100%);
        box-shadow: 0 14px 30px rgba(18, 38, 63, 0.07);
        position: relative;
    }

    .dashboard-hero-glow {
        position: absolute;
        right: -120px;
        top: -120px;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(66, 133, 244, 0.18) 0%, rgba(66, 133, 244, 0) 68%);
        pointer-events: none;
    }

    .dashboard-kicker {
        color: var(--color-accent, #4c6fff);
        font-size: .82rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
    }

    .dashboard-heading {
        font-size: clamp(1.2rem, 2.3vw, 1.75rem);
        font-weight: 700;
        color: var(--color-text-primary, #16223b);
    }

    .dashboard-subtitle {
        color: var(--color-text-secondary, #627089);
        font-size: .95rem;
    }

    .dashboard-stat-grid .dashboard-stat {
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .dashboard-stat {
        border: 1px solid var(--color-border-primary, #e9eef5);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.04);
    }

    .dashboard-stat:hover {
        transform: translateY(-2px);
        border-color: var(--color-border-focus, #dae5f5);
        box-shadow: 0 10px 24px rgba(18, 38, 63, 0.08);
    }

    .dashboard-stat-number {
        font-size: 1.9rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .dashboard-plan-name {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .dashboard-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }

    .dashboard-list-card {
        border: 1px solid var(--color-border-primary, #e9eef5);
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(17, 32, 66, 0.04);
    }

    .dashboard-list-item {
        transition: background-color .15s ease, transform .15s ease;
        border-radius: 10px;
        padding-left: .25rem !important;
        padding-right: .25rem !important;
    }

    .dashboard-list-item:hover {
        background-color: var(--color-bg-hover, #f8fbff);
        transform: translateX(2px);
    }

    .dashboard-link {
        color: var(--color-accent, #4c6fff);
        font-weight: 600;
    }

    .dashboard-badge {
        font-weight: 600;
        letter-spacing: .01em;
        border-radius: 999px;
        padding: .35rem .55rem;
    }

    .dashboard-todo {
        border: 1px solid var(--color-border-primary, #e9eef5);
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.04);
        background: linear-gradient(135deg, var(--color-bg-tertiary, #fffdf5) 0%, var(--color-bg-card, #ffffff) 100%);
    }

    .dashboard-todo-item {
        color: var(--color-accent, #4c6fff);
        font-size: .88rem;
        font-weight: 500;
        padding: .3rem .6rem;
        border-radius: 8px;
        transition: background-color .15s ease;
    }

    .dashboard-todo-item:hover {
        background-color: var(--color-bg-hover, #f0f4ff);
        color: var(--color-accent, #3a5bd9);
    }

    .onboarding-card {
        border: 2px solid var(--color-border-focus, #e0e8ff);
        border-radius: 16px;
        background: linear-gradient(135deg, var(--color-bg-hover, #f0f4ff) 0%, var(--color-bg-card, #ffffff) 50%, var(--color-bg-tertiary, #f5f0ff) 100%);
        box-shadow: 0 8px 24px rgba(66, 133, 244, 0.1);
    }

    .onboarding-step {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: .75rem;
        border-radius: 10px;
        background: var(--color-bg-backdrop, rgba(255,255,255,0.7));
        border: 1px solid var(--color-border-primary, #e9eef5);
        transition: transform .15s, box-shadow .15s;
    }
    .onboarding-step:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(66, 133, 244, 0.08);
    }

    .onboarding-step-num {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 50%;
        background: var(--color-accent, #4c6fff);
        color: #fff;
        font-weight: 700;
        font-size: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 992px) {
        .dashboard-hero .btn {
            flex: 1 1 auto;
        }
    }

    @media (max-width: 576px) {
        .dashboard-hero {
            border-radius: 14px;
        }

        .dashboard-stat-number {
            font-size: 1.6rem;
        }

        .dashboard-plan-name {
            font-size: 1.05rem;
        }
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
document.addEventListener('DOMContentLoaded', function() {
    // 新用户引导关闭
    const dismissBtn = document.getElementById('dismissOnboarding');
    const guide = document.getElementById('onboardingGuide');
    if (dismissBtn && guide) {
        dismissBtn.addEventListener('click', function() {
            guide.style.transition = 'opacity .3s, transform .3s';
            guide.style.opacity = '0';
            guide.style.transform = 'translateY(-10px)';
            setTimeout(() => guide.remove(), 300);
            fetch('{{ route("user.dashboard.dismiss-onboarding") }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'}
            }).catch(() => {});
        });
    }

    // 公告轮播
    const items = document.querySelectorAll('.announcement-item');
    const dots = document.querySelectorAll('.announcement-dots .dot');
    const carousel = document.getElementById('announcementCarousel');
    if (items.length > 1) {
        let current = 0;
        let timer = null;
        const show = function(idx) {
            items.forEach(function(el) { el.classList.remove('active'); });
            dots.forEach(function(el) { el.classList.remove('active'); });
            if (items[idx]) items[idx].classList.add('active');
            if (dots[idx]) dots[idx].classList.add('active');
            current = idx;
        };
        const startAuto = function() {
            if (timer) clearInterval(timer);
            timer = setInterval(function() { show((current + 1) % items.length); }, 5000);
        };
        const stopAuto = function() {
            if (timer) { clearInterval(timer); timer = null; }
        };
        dots.forEach(function(dot) {
            dot.addEventListener('click', function() { show(parseInt(dot.dataset.index)); startAuto(); });
        });
        // Pause on hover, resume on leave
        if (carousel) {
            carousel.addEventListener('mouseenter', stopAuto);
            carousel.addEventListener('mouseleave', startAuto);
        }
        startAuto();
    }
    // 关闭公告
    const dismissAnn = document.getElementById('dismissAnnouncement');
    const banner = document.getElementById('announcementBanner');
    if (dismissAnn && banner) {
        dismissAnn.addEventListener('click', function() {
            banner.style.transition = 'opacity .3s, max-height .3s';
            banner.style.opacity = '0';
            banner.style.maxHeight = '0';
            banner.style.overflow = 'hidden';
            setTimeout(() => banner.remove(), 300);
        });
    }
});
</script>
@endpush
