@extends('layouts.user')

@section('title', '语音面试 - ' . $interview->position)

@section('page-pretitle', 'AI语音面试')
@section('page-title', $interview->position)

@push('styles')
@include('user.interviews.voice-session.styles')
@endpush

@section('content')
@include('user.interviews.voice-session.content')
@endsection

@push('scripts')
@include('user.interviews.voice-session.script')
@endpush
