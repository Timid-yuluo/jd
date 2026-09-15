@extends('layouts.user')

@section('title', '用量统计')
@section('page-pretitle', '会员中心')
@section('page-title', '用量统计')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">本月用量（{{ $period }}）</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>功能</th>
                                <th style="width: 40%">用量</th>
                                <th>已用/限额</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usages as $item)
                                <tr>
                                    <td>{{ $item['label'] }}</td>
                                    <td>
                                        @if($item['unlimited'])
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-success" style="width: 0%"></div>
                                            </div>
                                        @else
                                            @php $percent = $item['limit'] > 0 ? min(100, round($item['used'] / $item['limit'] * 100)) : 100; @endphp
                                            <div class="progress progress-sm">
                                                <div class="progress-bar {{ $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $percent }}%"></div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item['unlimited'])
                                            <span class="badge bg-success-lt">{{ $item['used'] }} / 不限</span>
                                        @else
                                            <span class="badge {{ $item['used'] >= $item['limit'] ? 'bg-danger-lt' : 'bg-primary-lt' }}">
                                                {{ $item['used'] }} / {{ $item['limit'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('user.membership.pricing') }}" class="btn btn-outline-primary">
                <i class="ti ti-arrow-up me-1"></i>升级套餐获取更多配额
            </a>
            <a href="{{ route('user.membership.credit-packs') }}" class="btn btn-outline-secondary ms-2">
                <i class="ti ti-ticket me-1"></i>购买次卡
            </a>
        </div>
    </div>
</div>
@endsection
