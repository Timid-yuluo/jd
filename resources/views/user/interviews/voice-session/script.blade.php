@php
    $voiceSessionConfig = [
        'interviewId' => $interview->id,
        'currentQuestionId' => $interview->questions->firstWhere('answer', null)?->id ?? null,
        'currentRound' => ($interview->questions->firstWhere('answer', null)?->round_no) ?? 1,
        'submitAnswerUrl' => route('user.interviews.submitAnswer', $interview),
    ];
@endphp
<div id="voice-session-config" data-config='{{ Illuminate\Support\Js::from($voiceSessionConfig) }}' class="d-none"></div>
@push('scripts')
<script src="{{ asset('js/pages/user-interviews-voice-session-config.js') }}"></script>
<script src="{{ asset('js/pages/user-interviews-voice-session-script.js') }}?v={{ @filemtime(public_path('js/pages/user-interviews-voice-session-script.js')) ?: time() }}"></script>
@endpush
