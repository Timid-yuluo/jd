@extends('layouts.user')

@section('title', 'Offer 对比')

@section('page-pretitle', '求职决策')
@section('page-title', 'Offer 对比')

@section('content')
<div class="page-body"><div class="container-xl">

@if($offers->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="ti ti-scale text-secondary" style="font-size:2.5rem;"></i>
        <h4 class="mt-3">暂无 Offer</h4>
        <p class="text-secondary">当你收到 Offer 后，可以在这里对比不同 Offer 的优劣</p>
        <a href="{{ route('user.dashboard') }}" class="btn btn-primary mt-2"><i class="ti ti-arrow-left me-1"></i>返回控制台</a>
    </div>
</div>
@else

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-scale me-2"></i>横向对比 {{ $offers->count() }} 个 Offer</h3>
    </div>
</div>

<div class="row g-3">
@foreach($offers as $offer)
    <div class="col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-status-start bg-success"></div>
            <div class="card-header">
                <h3 class="card-title">{{ $offer->company }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-secondary small">岗位</div>
                    <div class="fw-semibold">{{ $offer->position }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-secondary small">薪资范围</div>
                    <div class="fw-bold text-success fs-4">
                        @if($offer->salary_min || $offer->salary_max)
                            {{ $offer->salary_min ? number_format($offer->salary_min / 1000) . 'K' : '?' }} - {{ $offer->salary_max ? number_format($offer->salary_max / 1000) . 'K' : '?' }}
                        @else
                            <span class="text-secondary">未填写</span>
                        @endif
                    </div>
                </div>
                <div class="mb-2">
                    <div class="text-secondary small">地点</div>
                    <div>{{ $offer->location ?: '未填写' }}</div>
                </div>
                <div class="mb-2">
                    <div class="text-secondary small">公司规模</div>
                    <div>{{ $offer->company_size ?: '未填写' }}</div>
                </div>
                <div class="mb-2">
                    <div class="text-secondary small">行业</div>
                    <div>{{ $offer->industry ?: '未填写' }}</div>
                </div>
                @if($offer->note)
                <div class="mt-3">
                    <div class="text-secondary small">备注</div>
                    <div class="small">{{ Str::limit($offer->note, 100) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endforeach
</div>

{{-- 维度对比表 --}}
@if($offers->count() >= 2)
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-table me-2"></i>维度对比</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>对比维度</th>
                    @foreach($offers as $offer)
                    <th>{{ $offer->company }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-semibold">岗位</td>
                    @foreach($offers as $offer)
                    <td>{{ $offer->position }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold">薪资下限</td>
                    @php $maxSalaryMin = $offers->max('salary_min') ?: 0; @endphp
                    @foreach($offers as $offer)
                    <td>
                        @if($offer->salary_min)
                            <span class="{{ $offer->salary_min == $maxSalaryMin && $maxSalaryMin > 0 ? 'text-success fw-bold' : '' }}">{{ number_format($offer->salary_min / 1000) }}K</span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold">薪资上限</td>
                    @php $maxSalaryMax = $offers->max('salary_max') ?: 0; @endphp
                    @foreach($offers as $offer)
                    <td>
                        @if($offer->salary_max)
                            <span class="{{ $offer->salary_max == $maxSalaryMax && $maxSalaryMax > 0 ? 'text-success fw-bold' : '' }}">{{ number_format($offer->salary_max / 1000) }}K</span>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold">地点</td>
                    @foreach($offers as $offer)
                    <td>{{ $offer->location ?: '-' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold">公司规模</td>
                    @foreach($offers as $offer)
                    <td>{{ $offer->company_size ?: '-' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold">行业</td>
                    @foreach($offers as $offer)
                    <td>{{ $offer->industry ?: '-' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

</div></div>
@endsection
