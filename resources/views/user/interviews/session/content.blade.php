<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <a href="{{ route('user.interviews.index') }}" class="btn btn-link text-secondary p-0"><i class="ti ti-arrow-left me-1"></i>返回</a>
                <h2 class="page-title mb-0">{{ $interview->position }}</h2>
                <div class="small text-secondary">第 <span id="current-round">{{ $interview->questions->where('answer', '!=', null)->count() + 1 }}</span>/{{ max(1, (int)($interview->question_count ?? 5)) }} 题 · {{ match($interview->candidate_profile ?? '') { 'no_experience' => '转岗', 'junior' => '1-3年', 'experienced' => '3年+', default => '应届生' } }}</div>
                {{-- 面试倒计时 --}}
                @if($interview->status === 'in_progress')
                <div class="mt-1" id="interviewTimer">
                    <span class="badge bg-danger-lt text-danger"><i class="ti ti-clock me-1"></i><span id="timerDisplay">00:00</span></span>
                </div>
                @endif
            </div>
            <div class="col-auto d-flex gap-2">
                @if($interview->status === 'in_progress')
                    <button type="button" class="btn btn-outline-warning btn-sm" id="pauseBtn" onclick="window.dispatchEvent(new CustomEvent('interview:pause'))">
                        <i class="ti ti-player-pause me-1"></i>暂停
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm d-none" id="resumeBtn" onclick="window.dispatchEvent(new CustomEvent('interview:resume'))">
                        <i class="ti ti-player-play me-1"></i>恢复
                    </button>
                @endif
                @if($interview->status !== 'completed')
                    <form id="finish-interview-form" action="{{ route('user.interviews.finish', $interview) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">结束面试</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="page-body"><div class="container-xl" x-data="interviewSession" x-init="init()">
    <div id="pauseOverlay" class="d-none" style="position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;">
        <div class="card p-4 text-center" style="max-width:320px;">
            <i class="ti ti-player-pause text-warning" style="font-size:3rem;"></i>
            <h3 class="mt-2">面试已暂停</h3>
            <p class="text-secondary small mb-3">计时已停止，点击恢复继续面试。</p>
            <button class="btn btn-success" onclick="window.dispatchEvent(new CustomEvent('interview:resume'))">
                <i class="ti ti-player-play me-1"></i>恢复面试
            </button>
        </div>
    </div>

    <div class="interview-chat">
        <div class="interview-chat-body" id="messages-container">
            @php $nextQ = $interview->questions->firstWhere('answer', null); @endphp
            @foreach($interview->questions as $q)
                @if($q->answer)
                    <div class="chat-divider-line"><span>第 {{ $q->round_no }} 轮</span></div>
                    <div class="chat-msg ai">
                        <div class="avatar"><i class="ti ti-robot"></i></div>
                        <div>
                            <div class="bubble">{{ $q->question }}</div>
                            <div class="meta">面试官 · 提问
                                @if($q->dimension)<span class="badge bg-info-lt text-info ms-1" style="font-size:.65rem;">{{ $q->dimension }}</span>@endif
                                @if($q->difficulty_level)<span class="badge {{ match($q->difficulty_level) { 'easy' => 'bg-success-lt text-success', 'hard' => 'bg-danger-lt text-danger', default => 'bg-warning-lt text-warning' } }} ms-1" style="font-size:.65rem;">{{ match($q->difficulty_level) { 'easy' => '简单', 'hard' => '困难', default => '中等' } }}</span>@endif
                                @if($q->tags && is_array($q->tags))@foreach($q->tags as $tag)<span class="badge bg-azure-lt text-azure ms-1" style="font-size:.65rem;">{{ $tag }}</span>@endforeach@endif
                            </div>
                        </div>
                    </div>
                    <div class="chat-msg user">
                        <div class="avatar"><i class="ti ti-user"></i></div>
                        <div>
                            <div class="bubble">{{ $q->answer }}</div>
                            <div class="meta">我 · 回答 @if($q->score !== null)<span class="score-tag {{ $q->score>=7?'high':($q->score>=5?'mid':'low') }}">{{ $q->score }}/10</span>@endif</div>
                        </div>
                    </div>
                    @if(isset($q->feedback['comment']))
                        <div class="chat-feedback">
                            @if($q->score !== null)<strong>得分 {{ $q->score }}/10</strong> · @endif
                            {{ $q->feedback['comment'] }}
                            @if(!empty($q->feedback['suggestion']))<br><strong>建议：</strong>{{ $q->feedback['suggestion'] }}@endif
                        </div>
                    @endif
                @endif
            @endforeach

            @if($interview->status !== 'completed' && $nextQ)
                <div class="chat-divider-line" id="next-round-divider"><span>第 {{ $nextQ->round_no }} 轮 · 当前题目</span></div>
                <div class="chat-msg ai" id="next-question-area">
                    <div class="avatar"><i class="ti ti-robot"></i></div>
                    <div>
                        <div class="bubble" id="next-question-content">{{ $nextQ->question }}</div>
                        <div class="meta">面试官 · 提问
                            @if($nextQ->dimension)<span class="badge bg-info-lt text-info ms-1" style="font-size:.65rem;">{{ $nextQ->dimension }}</span>@endif
                            @if($nextQ->difficulty_level)<span class="badge {{ match($nextQ->difficulty_level) { 'easy' => 'bg-success-lt text-success', 'hard' => 'bg-danger-lt text-danger', default => 'bg-warning-lt text-warning' } }} ms-1" style="font-size:.65rem;">{{ match($nextQ->difficulty_level) { 'easy' => '简单', 'hard' => '困难', default => '中等' } }}</span>@endif
                            @if($nextQ->tags && is_array($nextQ->tags))@foreach($nextQ->tags as $tag)<span class="badge bg-azure-lt text-azure ms-1" style="font-size:.65rem;">{{ $tag }}</span>@endforeach@endif
                        </div>
                    </div>
                </div>
            @elseif($interview->status === 'completed')
                <div class="text-center py-4 text-secondary">
                    面试已结束 <a href="{{ route('user.interviews.report', $interview) }}" class="btn btn-primary btn-sm ms-2">查看报告</a>
                </div>
            @endif
        </div>

        @if($interview->status !== 'completed' && $nextQ)
        <div class="chat-composer">
            <form id="answer-form" @submit.prevent="submitAnswer()" autocomplete="off">
                @csrf
                <input type="hidden" name="question_id" :value="currentQuestionId">
                <textarea class="form-control" rows="3" x-model="answer" x-ref="answerInput" :disabled="loading"
                    placeholder="输入你的回答（建议 80 字以上，用 STAR 框架）..."
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    required minlength="10" @keydown.ctrl.enter.prevent="submitAnswer()"></textarea>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="d-flex gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary snippet-btn" @click="appendSnippet('background')">补背景</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary snippet-btn" @click="appendSnippet('action')">补动作</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary snippet-btn" @click="appendSnippet('result')">补数据</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary snippet-btn" @click="appendSnippet('review')">补复盘</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-secondary" x-show="networkOnline && answer.trim().length < 10">至少10字（已输<span x-text="answerLength()"></span>字）</span>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="loading || !networkOnline || answer.trim().length < 10">
                            <span x-show="!loading && networkOnline && answer.trim().length >= 10">发送</span>
                            <span x-show="!networkOnline">离线</span>
                            <span x-show="loading"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>

    <div class="chat-toast" :class="toast.variant === 'danger' ? 'error' : (toast.variant === 'warning' ? 'warning' : 'success')" x-show="toast.visible" x-cloak>
        <div class="d-flex justify-content-between align-items-start gap-2">
            <span x-text="toast.message"></span>
            <button type="button" class="btn-close btn-close-sm" @click="hideToast()"></button>
        </div>
    </div>

    <div class="interview-dialog-mask" x-show="dialog.visible" x-cloak @keydown.escape.window="handleDialogEscape()" @click.self="handleDialogEscape()">
        <div class="interview-dialog">
            <div class="p-3 border-bottom d-flex justify-content-between">
                <strong x-text="dialog.title"></strong>
                <button type="button" class="btn-close" @click="handleDialogEscape()"></button>
            </div>
            <div class="p-3 text-secondary" x-text="dialog.message" style="white-space:pre-line"></div>
            <div class="p-3 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" x-show="dialog.showCancel" @click="resolveDialog(false)" x-text="dialog.cancelText"></button>
            <button type="button" class="btn" :class="dialog.variant === 'danger' ? 'btn-danger' : 'btn-primary'" @click="resolveDialog(true)" x-text="dialog.confirmText"></button>
            </div>
        </div>
    </div>
</div>
</div>

<div id="global-dialog-mask" style="position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:1100;display:none;align-items:center;justify-content:center;padding:1rem;">
    <div style="width:min(480px,100%);background:#fff;border-radius:12px;box-shadow:0 12px 36px rgba(15,23,42,.18);">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;font-weight:600;" id="global-dialog-title">提示</div>
        <div style="padding:16px 20px;color:#64748b;white-space:pre-line;" id="global-dialog-message"></div>
        <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" class="btn btn-outline-secondary d-none" id="global-dialog-cancel">取消</button>
            <button type="button" class="btn btn-primary" id="global-dialog-confirm">确定</button>
        </div>
    </div>
</div>
