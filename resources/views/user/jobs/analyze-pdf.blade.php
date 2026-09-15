@extends('layouts.user')

@section('title', '匹配报告 - 导出')

@php
    $score = $result['match_score'] ?? 0;
    $level = $result['level'] ?? '待提升';
    $levelColors = [
        '卓越' => ['bg' => 'text-primary', 'bar' => 'bg-primary', 'badge' => 'bg-primary-lt'],
        '优秀' => ['bg' => 'text-success', 'bar' => 'bg-success', 'badge' => 'bg-success-lt'],
        '良好' => ['bg' => 'text-info', 'bar' => 'bg-info', 'badge' => 'bg-info-lt'],
        '合格' => ['bg' => 'text-warning', 'bar' => 'bg-warning', 'badge' => 'bg-warning-lt'],
        '待提升' => ['bg' => 'text-danger', 'bar' => 'bg-danger', 'badge' => 'bg-danger-lt'],
    ];
    $colors = $levelColors[$level] ?? $levelColors['待提升'];
@endphp

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .card { break-inside: avoid; }
        body { font-size: 12px; }
        .page-header { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">匹配报告导出</h2>
            </div>
            <div class="col-auto d-flex gap-2 no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="ti ti-printer me-1"></i>打印 / 保存 PDF
                </button>
                <a href="{{ URL::previous() }}" class="btn btn-outline-secondary">返回</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body"><div class="container-xl">
    {{-- 报告头部 --}}
    <div class="card mb-4">
        <div class="card-body text-center py-4">
            <div class="mb-2">
                <span class="badge {{ $colors['badge'] }} fs-5">{{ $level }}</span>
            </div>
            <div class="display-2 fw-bold {{ $colors['bg'] }}">{{ $score }}<small class="fs-4 text-secondary">/100</small></div>
            <div class="text-secondary mt-2">
                岗位匹配分析报告 · {{ $analysisHistory->created_at?->format('Y年m月d日') }}
                @if($resume) · 简历：{{ $resume->title }}@endif
            </div>
        </div>
    </div>

    {{-- 综合评价 --}}
    @if(!empty($result['summary']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">综合评价</h3></div>
        <div class="card-body">
            <p class="mb-0">{{ $result['summary'] }}</p>
        </div>
    </div>
    @endif

    {{-- 分项得分 --}}
    @if(!empty($result['breakdown']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">分项得分</h3></div>
        <div class="card-body">
            @php
                $dimLabels = [
                    'skill_match' => '技能匹配',
                    'experience_fit' => '经验契合',
                    'quantified_results' => '成果量化',
                    'expression_structure' => '表达与结构',
                    'differentiation' => '差异化亮点',
                ];
            @endphp
            <table class="table table-bordered mb-0">
                <thead><tr><th>维度</th><th>得分</th><th>满分</th><th>占比</th></tr></thead>
                <tbody>
                @foreach($result['breakdown'] as $key => $dim)
                    @php $pct = $dim['max_score'] > 0 ? round($dim['score'] / $dim['max_score'] * 100) : 0; @endphp
                    <tr>
                        <td>{{ $dimLabels[$key] ?? $key }}</td>
                        <td class="fw-bold">{{ $dim['score'] }}</td>
                        <td>{{ $dim['max_score'] }}</td>
                        <td>{{ $pct }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- 匹配优势 --}}
    @if(!empty($result['strengths']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">匹配优势</h3></div>
        <div class="card-body">
            <ul class="mb-0 ps-3">
                @foreach($result['strengths'] as $s)<li class="mb-1">{{ $s }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- 不足之处 --}}
    @if(!empty($result['weaknesses']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">不足之处</h3></div>
        <div class="card-body">
            <ul class="mb-0 ps-3">
                @foreach($result['weaknesses'] as $w)<li class="mb-1">{{ $w }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- 改进建议 --}}
    @if(!empty($result['suggestions']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">改进建议</h3></div>
        <div class="card-body">
            <table class="table table-bordered table-sm mb-0">
                <thead><tr><th>#</th><th>领域</th><th>建议</th><th>优先级</th></tr></thead>
                <tbody>
                @foreach($result['suggestions'] as $i => $s)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $s['area'] ?? '-' }}</td>
                        <td>{{ $s['action'] ?? $s['text'] ?? '' }}</td>
                        <td>{{ match($s['priority'] ?? 'medium') { 'high' => '高', 'low' => '低', default => '中' } }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- 技能缺口 --}}
    @if(!empty($result['skill_gaps']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">技能缺口分析</h3></div>
        <div class="card-body">
            @if(!empty($result['skill_gaps']['missing_hard_skills']))
            <div class="mb-3">
                <div class="fw-medium text-danger mb-2">缺失硬技能（核心）</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($result['skill_gaps']['missing_hard_skills'] as $kw)
                        <span class="badge bg-danger-lt text-danger">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($result['skill_gaps']['missing_soft_skills']))
            <div class="mb-3">
                <div class="fw-medium text-warning mb-2">缺失软技能</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($result['skill_gaps']['missing_soft_skills'] as $kw)
                        <span class="badge bg-warning-lt text-warning">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(!empty($result['skill_gaps']['nice_to_have']))
            <div>
                <div class="fw-medium text-info mb-2">加分项</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($result['skill_gaps']['nice_to_have'] as $kw)
                        <span class="badge bg-info-lt text-info">+ {{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- 面试准备重点 --}}
    @if(!empty($result['interview_focus_areas']))
    <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">面试准备重点</h3></div>
        <div class="card-body">
            <ul class="mb-0 ps-3">
                @foreach($result['interview_focus_areas'] as $f)<li class="mb-1">{{ $f }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- 页脚 --}}
    <div class="text-center text-secondary small py-4">
        本报告由 AI 生成，仅供参考 · 生成时间：{{ now()->format('Y-m-d H:i:s') }}
    </div>
</div></div>
@endsection
