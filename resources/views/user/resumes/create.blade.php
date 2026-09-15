@extends('layouts.user')

@section('title', '创建简历')

@section('page-pretitle', '简历管理')
@section('page-title', '创建简历')

@section('content')
@include('user.resumes.create.styles')

<div class="row">
    @include('user.resumes.create.form-panels')
    @include('user.resumes.create.sidebar')
</div>
@endsection

@push('scripts')
@include('user.resumes.create.scripts')
@endpush
