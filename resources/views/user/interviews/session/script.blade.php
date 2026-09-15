@php
    $interviewSessionConfig = [
        'interviewId' => $interview->id,
        'heartbeatUrl' => route('user.interviews.heartbeat', $interview),
        'pollIntervalMs' => max(1000, (int) config('interview.evaluation_poll_interval_ms', 2000)),
        'pollFastAttempts' => max(1, (int) config('interview.evaluation_poll_fast_attempts', 3)),
        'pollFastIntervalMs' => max(300, (int) config('interview.evaluation_poll_fast_interval_ms', 600)),
        'pollSlowIntervalMs' => max(700, (int) config('interview.evaluation_poll_slow_interval_ms', 1300)),
        'pollJitterMs' => max(0, (int) config('interview.evaluation_poll_jitter_ms', 120)),
        'pollRequestTimeoutMs' => max(1500, (int) config('interview.evaluation_poll_request_timeout_ms', 6000)),
        'pollMaxAttempts' => max(5, (int) config('interview.evaluation_poll_max_attempts', 30)),
        'resumePollDelayMs' => max(3000, (int) config('interview.evaluation_resume_poll_delay_ms', 15000)),
        'currentQuestionId' => $interview->questions->firstWhere('answer', null)?->id ?? null,
        'currentRound' => ($interview->questions->firstWhere('answer', null)?->round_no) ?? 1,
        'submitAnswerUrl' => route('user.interviews.submitAnswer', $interview),
        'evaluationStatusUrl' => route('user.interviews.evaluationStatus', $interview),
    ];
@endphp
<div id="interview-session-config" data-config='@json($interviewSessionConfig)' class="d-none"></div>
<script src="{{ asset('js/pages/user-interviews-session-config.js') }}"></script>
<script src="{{ asset('js/pages/user-interviews-session-script.js') }}?v={{ @filemtime(public_path('js/pages/user-interviews-session-script.js')) ?: time() }}"></script>
{{-- 面试倒计时 --}}
@if($interview->status === 'in_progress')
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var timerEl = document.getElementById('timerDisplay');
    if (!timerEl) return;
    var seconds = 0;
    var paused = false;

    // 从面试开始时间计算已用时间
    @if($interview->created_at)
    seconds = Math.floor((Date.now() - new Date('{{ $interview->created_at->toIso8601String() }}').getTime()) / 1000);
    @endif

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function updateDisplay() {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        timerEl.textContent = pad(m) + ':' + pad(s);
    }

    updateDisplay();
    var timer = setInterval(function() {
        if (!paused) {
            seconds++;
            updateDisplay();
        }
    }, 1000);

    // 暂停/恢复
    window.addEventListener('interview:pause', function() { paused = true; });
    window.addEventListener('interview:resume', function() { paused = false; });
})();
</script>
@endif

{{-- Auto-save answer draft to localStorage --}}
<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
(function() {
    var interviewId = '{{ $interview->id }}';
    var draftKey = 'interview_draft_' + interviewId;
    var textarea = document.querySelector('#answer-form textarea');
    if (!textarea) return;

    // Restore draft on load
    try {
        var saved = localStorage.getItem(draftKey);
        if (saved && saved.trim()) {
            // Use Alpine's $nextTick to set after Alpine initializes
            document.addEventListener('alpine:initialized', function() {
                if (textarea.value === '') {
                    textarea.value = saved;
                    textarea.dispatchEvent(new Event('input'));
                }
            });
            // Also try immediately
            setTimeout(function() {
                if (textarea.value === '') {
                    textarea.value = saved;
                    textarea.dispatchEvent(new Event('input'));
                }
            }, 500);
        }
    } catch(e) {}

    // Auto-save on input (debounced)
    var saveTimer = null;
    textarea.addEventListener('input', function() {
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(function() {
            try {
                if (textarea.value.trim()) {
                    localStorage.setItem(draftKey, textarea.value);
                } else {
                    localStorage.removeItem(draftKey);
                }
            } catch(e) {}
        }, 1000);
    });

    // Clear draft on successful submit
    document.getElementById('answer-form')?.addEventListener('submit', function() {
        try { localStorage.removeItem(draftKey); } catch(e) {}
    });
})();
</script>
