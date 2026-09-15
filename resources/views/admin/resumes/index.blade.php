@extends('layouts.admin')

@section('title', '简历管理')
@section('page-pretitle', '业务中心')
@section('page-title', '简历列表')

@section('content')
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-file-text"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['total']) }}</div><div class="text-secondary">简历总数</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-plus"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['today']) }}</div><div class="text-secondary">今日新增</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-star"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['scored']) }}</div><div class="text-secondary">已评分</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto"><span class="bg-purple text-white avatar"><i class="ti ti-sparkles"></i></span></div>
                    <div class="col"><div class="font-weight-medium">{{ number_format($stats['optimized']) }}</div><div class="text-secondary">已优化</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">系统简历</h3>
                <div class="card-actions">
                    <form method="GET" action="{{ route('admin.resumes.index') }}" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="搜索标题/岗位/用户..." value="{{ request('search') }}" style="min-width: 200px;">
                        <select name="ats" class="form-select form-select-sm" style="min-width: 120px;">
                            <option value="">ATS 状态</option>
                            <option value="scored" {{ request('ats') === 'scored' ? 'selected' : '' }}>已评分</option>
                            <option value="unscored" {{ request('ats') === 'unscored' ? 'selected' : '' }}>未评分</option>
                            <option value="high" {{ request('ats') === 'high' ? 'selected' : '' }}>高分 (80+)</option>
                            <option value="medium" {{ request('ats') === 'medium' ? 'selected' : '' }}>中等 (50-79)</option>
                            <option value="low" {{ request('ats') === 'low' ? 'selected' : '' }}>低分 (<50)</option>
                        </select>
                        <select name="optimized" class="form-select form-select-sm" style="min-width: 100px;">
                            <option value="">优化状态</option>
                            <option value="yes" {{ request('optimized') === 'yes' ? 'selected' : '' }}>已优化</option>
                            <option value="no" {{ request('optimized') === 'no' ? 'selected' : '' }}>未优化</option>
                        </select>
                        <select name="sort" class="form-select form-select-sm" style="min-width: 120px;">
                            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>最新创建</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>最早创建</option>
                            <option value="ats_high" {{ request('sort') === 'ats_high' ? 'selected' : '' }}>ATS 高分</option>
                            <option value="ats_low" {{ request('sort') === 'ats_low' ? 'selected' : '' }}>ATS 低分</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">筛选</button>
                        @if(request()->hasAny(['search','ats','optimized','sort']))
                            <a href="{{ route('admin.resumes.index') }}" class="btn btn-sm btn-secondary">重置</a>
                        @endif
                        <a href="{{ route('admin.resumes.export', request()->only(['search','ats','optimized'])) }}" class="btn btn-sm btn-outline-success"><i class="ti ti-download me-1"></i>导出</a>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="w-1">ID</th>
                            <th>用户</th>
                            <th>标题</th>
                            <th>目标岗位</th>
                            <th>ATS 分数</th>
                            <th>优化</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumes as $resume)
                        <tr>
                            <td><span class="text-secondary">{{ $resume->id }}</span></td>
                            <td>{{ $resume->user?->name ?? '未知用户' }}</td>
                            <td>{{ $resume->title }}</td>
                            <td>{{ $resume->target_job ?? '-' }}</td>
                            <td>
                                @if($resume->ats_score > 0)
                                    <span class="badge bg-{{ $resume->ats_score >= 80 ? 'green' : ($resume->ats_score >= 50 ? 'azure' : 'orange') }} text-white">{{ $resume->ats_score }}</span>
                                @else
                                    <span class="badge bg-secondary text-secondary-fg">未评</span>
                                @endif
                            </td>
                            <td>
                                @if($resume->optimized_text)
                                    <span class="badge bg-purple text-white"><i class="ti ti-sparkles me-1"></i>已优化</span>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ $resume->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.resumes.show', $resume) }}" class="btn btn-sm btn-outline-primary">查看</a>
                                <form action="{{ route('admin.resumes.destroy', $resume) }}" method="POST" class="d-inline" data-app-confirm='确定要删除此简历吗？'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary">暂无数据</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($resumes->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $resumes->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection