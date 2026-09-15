@extends('layouts.admin')

@section('title', '投递看板')
@section('page-pretitle', '业务中心')
@section('page-title', '投递看板管理')

@section('content')
{{-- 统计卡片 --}}
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">总投递</div>
                <div class="h2 mb-0">{{ number_format($stats['total']) }}</div>
                <div class="text-secondary small">今日 +{{ $stats['today'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">愿望单</div>
                <div class="h2 mb-0">{{ number_format($stats['wishlist']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">已投递</div>
                <div class="h2 mb-0">{{ number_format($stats['applied']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">面试中</div>
                <div class="h2 mb-0">{{ number_format($stats['interview']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">Offer</div>
                <div class="h2 mb-0">{{ number_format($stats['offer']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2">
        <div class="card">
            <div class="card-body p-2 text-center">
                <div class="text-secondary">淘汰</div>
                <div class="h2 mb-0">{{ number_format($stats['rejected']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">系统投递记录</h3>
            </div>
            {{-- 筛选栏 --}}
            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('admin.job-applications.index') }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label">搜索</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="公司/岗位/用户">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">状态</label>
                        <select name="status" class="form-select">
                            <option value="">全部</option>
                            <option value="wishlist" {{ request('status') === 'wishlist' ? 'selected' : '' }}>愿望单</option>
                            <option value="applied" {{ request('status') === 'applied' ? 'selected' : '' }}>已投递</option>
                            <option value="written" {{ request('status') === 'written' ? 'selected' : '' }}>笔试</option>
                            <option value="interview" {{ request('status') === 'interview' ? 'selected' : '' }}>面试</option>
                            <option value="offer" {{ request('status') === 'offer' ? 'selected' : '' }}>Offer</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>淘汰</option>
                        </select>
                    </div>
                    @if($channels->isNotEmpty())
                    <div class="col-auto">
                        <label class="form-label">渠道</label>
                        <select name="channel" class="form-select">
                            <option value="">全部</option>
                            @foreach($channels as $ch)
                            <option value="{{ $ch }}" {{ request('channel') === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-auto">
                        <label class="form-label">起始日期</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">截止日期</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-search me-1"></i>筛选</button>
                        <a href="{{ route('admin.job-applications.index') }}" class="btn btn-secondary">重置</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>用户</th>
                            <th>公司</th>
                            <th>岗位</th>
                            <th>状态</th>
                            <th>渠道</th>
                            <th>截止日期</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                        <tr>
                            <td><span class="text-secondary">{{ $app->id }}</span></td>
                            <td>{{ $app->user?->name ?? '未知用户' }}</td>
                            <td>{{ $app->company }}</td>
                            <td>{{ $app->position }}</td>
                            <td>
                                @php
                                    $statusMap = [
                                        'wishlist' => ['label' => '愿望单', 'class' => 'bg-secondary-lt'],
                                        'applied' => ['label' => '已投递', 'class' => 'bg-blue-lt'],
                                        'written' => ['label' => '笔试', 'class' => 'bg-azure-lt'],
                                        'interview' => ['label' => '面试', 'class' => 'bg-orange-lt'],
                                        'offer' => ['label' => 'Offer', 'class' => 'bg-green-lt'],
                                        'rejected' => ['label' => '淘汰', 'class' => 'bg-red-lt'],
                                    ];
                                    $s = $statusMap[$app->status] ?? ['label' => $app->status, 'class' => 'bg-secondary-lt'];
                                @endphp
                                <span class="badge {{ $s['class'] }}">{{ $s['label'] }}</span>
                            </td>
                            <td>{{ $app->channel ?? '-' }}</td>
                            <td>{{ $app->deadline ?? '-' }}</td>
                            <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.job-applications.show', $app) }}" class="btn btn-sm btn-outline-info">详情</a>
                                <a href="{{ route('admin.job-applications.edit', $app) }}" class="btn btn-sm btn-outline-primary">编辑</a>
                                <form action="{{ route('admin.job-applications.destroy', $app) }}" method="POST" class="d-inline" data-app-confirm='确定要删除此投递记录吗？'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary">暂无数据</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $applications->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
