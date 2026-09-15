@extends('layouts.user')

@section('title', '薪资查询')
@section('page-pretitle', '薪资助手')
@section('page-title', '薪资查询')

@section('page-actions')
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal">
        <i class="ti ti-plus me-1"></i>上报薪资
    </button>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('user.salary.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label">岗位名称</label>
                    <input type="text" name="job_title" class="form-control" placeholder="如：前端工程师" value="{{ $jobTitle }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">城市</label>
                    <input type="text" name="city" class="form-control" placeholder="如：北京" value="{{ $city }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">经验级别</label>
                    <select name="experience_level" class="form-select">
                        <option value="">全部</option>
                        <option value="junior" @selected($experienceLevel === 'junior')>初级 0-3年</option>
                        <option value="mid" @selected($experienceLevel === 'mid')>中级 3-5年</option>
                        <option value="senior" @selected($experienceLevel === 'senior')>高级 5-10年</option>
                        <option value="expert" @selected($experienceLevel === 'expert')>专家 10年+</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-search me-1"></i>查询
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible">
    <i class="ti ti-check me-2"></i>{{ session('success') }}
</div>
@endif

@if($jobTitle)
    @include('user.salary.partials.salary-chart')
@else
<div class="card">
    <div class="card-body">
        <h3 class="card-title mb-3">热门查询</h3>
        <div class="d-flex flex-wrap gap-2">
            @foreach($hotJobs as $job => $count)
            <a href="{{ route('user.salary.index', ['job_title' => $job]) }}" class="btn btn-outline-secondary btn-sm">
                {{ $job }}
                <span class="badge bg-secondary-lt ms-1">{{ $count }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card mt-4">
    <div class="card-body text-center">
        <i class="ti ti-message-chatbot fs-1 text-primary"></i>
        <h3 class="mt-2">AI 薪资谈判助手</h3>
        <p class="text-secondary">输入你的薪资情况，AI 帮你制定谈判策略</p>
        <a href="{{ route('user.salary.negotiate') }}" class="btn btn-primary">
            <i class="ti ti-sparkles me-1"></i>开始谈判
        </a>
    </div>
</div>

@include('user.salary.report-modal')
@endsection
