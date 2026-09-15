@extends('layouts.user')

@section('title', '我的简历')

@section('page-pretitle', '简历管理')
@section('page-title', '我的简历')

@section('page-actions')
@if(($resumeQuota['allowed'] ?? true) === true)
    <a href="{{ route('user.resumes.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-2"></i>新建简历
    </a>
@else
    <a href="{{ route('user.membership.pricing') }}" class="btn btn-outline-primary" title="当前套餐简历数量已达上限，请升级后继续创建">
        <i class="ti ti-lock me-2"></i>新建简历（已达上限）
    </a>
@endif
<a href="{{ route('user.resumes.trash') }}" class="btn btn-ghost-secondary ms-2">
    <i class="ti ti-trash me-2"></i>回收站
</a>
@endsection

@section('content')
@include('user.resumes.index.content')
@endsection
