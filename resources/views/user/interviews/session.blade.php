@extends('layouts.user')

@section('title', '面试会话 - ' . $interview->position)

@push('styles')
@include('user.interviews.session.styles')
@endpush

@section('content')
@include('user.interviews.session.content')
@endsection

@push('scripts')
@include('user.interviews.session.script')
@endpush
