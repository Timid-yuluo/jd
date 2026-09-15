@extends('layouts.user')

@section('title', '面试日历')

@section('page-pretitle', '日程管理')
@section('page-title', '面试日历')

@section('page-actions')
<div class="d-flex gap-2 align-items-center">
    <a href="{{ route('user.interviews.calendar', ['month' => $startOfMonth->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-chevron-left"></i>
    </a>
    <span class="fw-semibold">{{ $startOfMonth->format('Y年m月') }}</span>
    <a href="{{ route('user.interviews.calendar', ['month' => $startOfMonth->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-chevron-right"></i>
    </a>
    <a href="{{ route('user.interviews.calendar') }}" class="btn btn-outline-secondary btn-sm ms-2">本月</a>
</div>
@endsection

@section('content')
<div class="page-body"><div class="container-xl">

@php
    $calendarStart = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $calendarEnd = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $interviewsByDate = $interviews->groupBy(fn($i) => $i->interview_at->format('Y-m-d'));
    $sessionsByDate = $interviewSessions->groupBy(fn($s) => \Carbon\Carbon::parse($s->created_at)->format('Y-m-d'));
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="table-layout:fixed;">
                <thead>
                    <tr>
                        <th class="text-center small py-2">周一</th>
                        <th class="text-center small py-2">周二</th>
                        <th class="text-center small py-2">周三</th>
                        <th class="text-center small py-2">周四</th>
                        <th class="text-center small py-2">周五</th>
                        <th class="text-center small py-2" style="color:#94a3b8;">周六</th>
                        <th class="text-center small py-2" style="color:#94a3b8;">周日</th>
                    </tr>
                </thead>
                <tbody>
                @php $day = $calendarStart->copy(); @endphp
                @while($day <= $calendarEnd)
                    <tr>
                    @for($i = 0; $i < 7; $i++)
                        @php
                            $isCurrentMonth = $day->month === $startOfMonth->month;
                            $isToday = $day->isToday();
                            $dateKey = $day->format('Y-m-d');
                            $dayInterviews = $interviewsByDate->get($dateKey, collect());
                            $daySessions = $sessionsByDate->get($dateKey, collect());
                        @endphp
                        <td class="align-top p-1 {{ !$isCurrentMonth ? 'bg-body' : '' }}" style="height:100px;min-width:100px;">
                            <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                                <span class="small {{ $isToday ? 'badge bg-primary rounded-pill px-2' : ($isCurrentMonth ? 'fw-semibold' : 'text-secondary') }}">{{ $day->day }}</span>
                            </div>
                            @foreach($dayInterviews as $interview)
                                <div class="px-1 mb-1">
                                    <span class="badge bg-warning-lt text-warning w-100 text-start" style="font-size:11px;white-space:normal;line-height:1.3;">
                                        <i class="ti ti-briefcase me-1"></i>{{ Str::limit($interview->company, 8) }}
                                    </span>
                                </div>
                            @endforeach
                            @foreach($daySessions as $session)
                                <div class="px-1 mb-1">
                                    <span class="badge bg-info-lt text-info w-100 text-start" style="font-size:11px;white-space:normal;line-height:1.3;">
                                        <i class="ti ti-microphone me-1"></i>{{ Str::limit($session->position, 8) }}
                                    </span>
                                </div>
                            @endforeach
                        </td>
                        @php $day->addDay(); @endphp
                    @endfor
                    </tr>
                @endwhile
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 图例 --}}
<div class="d-flex gap-3 mt-2 mb-3">
    <span class="small"><span class="badge bg-warning-lt text-warning"><i class="ti ti-briefcase me-1"></i>现场面试</span></span>
    <span class="small"><span class="badge bg-info-lt text-info"><i class="ti ti-microphone me-1"></i>模拟面试</span></span>
</div>

{{-- 当月面试列表 --}}
@if($interviews->isNotEmpty())
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-list me-2"></i>本月面试安排</h3>
    </div>
    <div class="list-group list-group-flush">
        @foreach($interviews as $interview)
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="badge bg-warning-lt text-warning"><i class="ti ti-briefcase me-1"></i>现场</span>
                </div>
                <div class="col">
                    <div class="fw-semibold">{{ $interview->company }} · {{ $interview->position }}</div>
                    <div class="small text-secondary">{{ $interview->interview_at?->format('m月d日 H:i') }}</div>
                </div>
                <div class="col-auto">
                    @if($interview->interview_info)
                    <span class="text-secondary small">{{ Str::limit($interview->interview_info, 30) }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

</div></div>
@endsection
