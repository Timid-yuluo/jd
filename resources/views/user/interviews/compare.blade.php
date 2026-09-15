@extends('layouts.user')

@section('title', '面试对比')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.interviews.index') }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回</a>
                <h2 class="page-title">面试对比</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        {{-- 总分对比柱状图 --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-chart-bar me-2 text-primary"></i>总分对比</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-3 justify-content-center" style="min-height:160px;">
                    @foreach($interviews as $i)
                        @php $score = $i->overall_score ?? 0; @endphp
                        <div class="text-center" style="flex:1;max-width:120px;">
                            <div class="fw-bold text-{{ $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger') }}" style="font-size:1.5rem;">{{ $score }}</div>
                            <div class="progress progress-vertical" style="height:100px;width:40px;margin:0 auto;">
                                <div class="progress-bar bg-{{ $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger') }}" style="height:{{ $score }}%;width:100%;"></div>
                            </div>
                            <div class="small text-secondary mt-2 text-truncate">{{ $i->position }}</div>
                            <div class="text-secondary" style="font-size:.7rem;">{{ $i->created_at?->format('m/d') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 维度对比表格 --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-dimensions me-2 text-primary"></i>维度对比</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th style="min-width:100px;">维度</th>
                            @foreach($interviews as $i)
                                <th class="text-center" style="min-width:120px;">
                                    {{ $i->position }}
                                    @if($i->company) <br><small class="text-secondary">{{ $i->company }}</small> @endif
                                    <br><small class="text-secondary">{{ $i->created_at?->format('m/d H:i') }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $reports = [];
                            $allDimensions = [];
                            foreach ($interviews as $i) {
                                $r = is_array($i->report) ? $i->report : [];
                                $reports[$i->id] = $r;
                                $dimScores = $r['dimension_scores'] ?? [];
                                foreach ($dimScores as $ds) {
                                    $dimName = $ds['dimension'] ?? '未知';
                                    if (!in_array($dimName, $allDimensions)) {
                                        $allDimensions[] = $dimName;
                                    }
                                }
                            }
                        @endphp

                        <tr>
                            <td class="fw-medium">总分</td>
                            @foreach($interviews as $i)
                                @php $score = $i->overall_score ?? 0; @endphp
                                <td class="text-center">
                                    <span class="h3 text-{{ $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger') }}">{{ $score }}</span>
                                </td>
                            @endforeach
                        </tr>

                        @foreach($allDimensions as $dimName)
                            <tr>
                                <td class="fw-medium">{{ $dimName }}</td>
                                @foreach($interviews as $i)
                                    @php
                                        $dimScore = null;
                                        $dimScores = $reports[$i->id]['dimension_scores'] ?? [];
                                        foreach ($dimScores as $ds) {
                                            if (($ds['dimension'] ?? '') === $dimName) {
                                                $dimScore = $ds['score'] ?? 0;
                                                break;
                                            }
                                        }
                                    @endphp
                                    <td class="text-center">
                                        @if($dimScore !== null)
                                            <span class="text-{{ $dimScore >= 80 ? 'success' : ($dimScore >= 60 ? 'warning' : 'danger') }}">{{ $dimScore }}</span>
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr>
                            <td class="fw-medium">题数 / 已答</td>
                            @foreach($interviews as $i)
                                <td class="text-center">{{ $i->question_count }} / {{ $i->answered_count }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 各面试详细评语 --}}
        @foreach($interviews as $i)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ $i->position }} @if($i->company) <small class="text-secondary ms-1">{{ $i->company }}</small> @endif</h3>
                </div>
                <div class="card-body">
                    @php $r = $reports[$i->id]; @endphp
                    <p class="text-secondary">{{ $r['overall_comment'] ?? '暂无评语' }}</p>
                    @if(!empty($r['strengths']))
                        <div class="mb-2">
                            <span class="badge bg-success-lt text-success me-1">优势</span>
                            {{ implode('、', $r['strengths']) }}
                        </div>
                    @endif
                    @if(!empty($r['weak_dimensions']))
                        <div>
                            <span class="badge bg-warning-lt text-warning me-1">待提升</span>
                            {{ implode('、', $r['weak_dimensions']) }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
