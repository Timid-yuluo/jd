@section('content')
<div class="voice-interview-container" x-data="voiceInterview" x-init="init()">
    @php
        $planSummary = is_array($interviewPlanSummary ?? null) ? $interviewPlanSummary : [];
        $evaluationQuotaNotice = is_array($planSummary['interviewEvaluationNotice'] ?? null) ? $planSummary['interviewEvaluationNotice'] : null;
        $interviewEvaluationAutoCreditCheck = is_array($planSummary['interviewEvaluationAutoCreditCheck'] ?? null) ? $planSummary['interviewEvaluationAutoCreditCheck'] : [];
        $evaluationAutoCreditReady = (($evaluationQuotaNotice['credit_available'] ?? false) === true)
            && (($interviewEvaluationAutoCreditCheck['allowed'] ?? false) === true);
        $evaluationAutoCreditName = (string) ($interviewEvaluationAutoCreditCheck['credit_name'] ?? '次卡');
    @endphp
    @if($evaluationQuotaNotice)
        <div class="alert alert-warning py-2 mb-3">
            <div><i class="ti ti-alert-triangle me-1"></i>{{ $evaluationQuotaNotice['message'] }}</div>
            @if($evaluationAutoCreditReady)
                <div class="small text-secondary mt-1">
                    提交回答时，系统会先检查面试评估免费次数；若已用完，将弹出次卡确认框，默认优先推荐面试评估专用次卡，其次推荐通用次卡。当前推荐：{{ $evaluationAutoCreditName }}。
                </div>
            @elseif(($evaluationQuotaNotice['credit_available'] ?? false) === true)
                <div class="small text-secondary mt-1">
                    提交回答时，系统会先检查面试评估免费次数；若已用完，将弹出次卡确认框，并继续检查面试评估专用次卡或通用次卡，确认后再继续评估。
                </div>
            @endif
        </div>
    @endif
    {{-- 顶部信息 --}}
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="flex-fill">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="mb-0 fw-bold">{{ $interview->position }}</h5>
                        @if($interview->company)
                            <span class="text-secondary">·</span>
                            <span class="text-secondary">{{ $interview->company }}</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @php
                            $candidateProfileLabel = match ($interview->candidate_profile ?? 'fresh_graduate') {
                                'no_experience' => '无经验转岗',
                                'junior' => '1-3年经验',
                                'experienced' => '3年+经验',
                                default => '应届生',
                            };
                        @endphp
                        @php
                            $typeLabels = ['technical'=>'技术面','behavioral'=>'行为面','mixed'=>'综合面','sales'=>'销售/BD','management'=>'管理岗','creative'=>'创意/设计','finance'=>'金融/财务','retail'=>'零售/电商','manufacturing'=>'制造/工业','service'=>'服务行业','media'=>'传媒/广告','education'=>'教育/培训','deep'=>'深度面谈'];
                            $typeColors = ['technical'=>'primary','behavioral'=>'info','mixed'=>'warning','sales'=>'success','management'=>'danger','creative'=>'primary','finance'=>'warning','retail'=>'success','manufacturing'=>'secondary','service'=>'info','media'=>'primary','education'=>'info','deep'=>'secondary'];
                            $typeIcons = ['technical'=>'ti ti-code','behavioral'=>'ti ti-users','mixed'=>'ti ti-adjustments','sales'=>'ti ti-chart-bar','management'=>'ti ti-building','creative'=>'ti ti-palette','finance'=>'ti ti-report-money','retail'=>'ti ti-shopping-cart','manufacturing'=>'ti ti-tool','service'=>'ti ti-headset','media'=>'ti ti-speakerphone','education'=>'ti ti-book','deep'=>'ti ti-brain'];
                            $tKey = $interview->type ?? 'mixed';
                        @endphp
                        <span class="badge bg-{{ $typeColors[$tKey] ?? 'secondary' }}-lt text-{{ $typeColors[$tKey] ?? 'secondary' }} rounded-pill">
                            <i class="{{ $typeIcons[$tKey] ?? 'ti ti-message-circle' }} me-1" style="font-size:.7rem;"></i>{{ $typeLabels[$tKey] ?? $tKey }}
                        </span>
                        <span class="badge bg-secondary-lt text-secondary">{{ $candidateProfileLabel }}</span>
                        <span class="badge bg-success-lt text-success">
                            <i class="ti ti-microphone me-1"></i>语音面试
                        </span>
                        <span class="text-secondary small">
                            第 <span x-text="currentRound" class="fw-bold text-primary">{{ $interview->questions->where('answer', '!=', null)->count() + 1 }}</span> 
                            / {{ max(1, (int) ($interview->question_count ?? config('interview.max_questions', 5))) }} 题
                        </span>
                    </div>
                </div>
                <div class="text-end ps-3">
                    {{-- 录音/播放动画 --}}
                    <div class="voice-wave" :class="{ 'recording': isRecording }" x-show="isRecording || isPlaying">
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                        <div class="voice-wave-bar"></div>
                    </div>
                    {{-- 静音/暂停图标 --}}
                    <div x-show="!isRecording && !isPlaying" class="voice-muted-icon">
                        <i class="ti ti-microphone-off"></i>
                    </div>
                </div>
            </div>
            
            {{-- 进度条 --}}
            @php
                $totalQuestions = max(1, (int) ($interview->question_count ?? config('interview.max_questions', 5)));
                $answeredCount = $interview->questions->where('answer', '!=', null)->count();
                $progressPercent = min(100, ($answeredCount / $totalQuestions) * 100);
            @endphp
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-secondary">面试进度</small>
                    <small class="text-secondary">{{ $answeredCount }}/{{ $totalQuestions }} 题</small>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 语音面试主体 --}}
    <div class="card flex-fill">
        <div class="card-body voice-interview-container">
                        {{-- 提示信息 --}}
                        <div class="voice-tips" x-show="showTips">
                            <i class="ti ti-bulb"></i>
                            <div class="flex-fill">
                                <strong class="voice-tips-title">语音面试提示</strong>
                                <div class="small mt-1 voice-tips-text">按住麦克风按钮说话，松开自动提交。AI 面试官会通过语音提问和点评。</div>
                            </div>
                            <button type="button" @click="showTips = false" aria-label="Close" class="voice-tips-close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

            {{-- 对话区域 --}}
            <div class="voice-message-container" id="voice-messages" x-ref="messagesContainer">
                {{-- 历史消息 --}}
                @foreach($interview->questions as $question)
                    @if($question->answer)
                        {{-- AI问题 --}}
                        <div class="voice-message ai">
                            <div class="voice-message-avatar">
                                <i class="ti ti-robot"></i>
                            </div>
                            <div class="voice-message-content">
                                <div class="mb-1"><strong>面试官</strong></div>
                                <div>{{ $question->question }}</div>
                                <button class="voice-play-btn" @click="showPlayConfirmDialog('{{ addslashes($question->question) }}')">
                                    <i class="ti ti-volume"></i> 播放
                                </button>
                            </div>
                        </div>
                        
                        {{-- 用户回答 --}}
                        <div class="voice-message user">
                            <div class="voice-message-avatar">
                                <i class="ti ti-user"></i>
                            </div>
                            <div class="voice-message-content">
                                <div class="mb-1"><strong>我的回答</strong></div>
                                <div>{{ $question->answer }}</div>
                                @if($question->score !== null)
                                    <div class="mt-2">
                                        <span class="score-badge-voice {{ $question->score >= 7 ? 'high' : ($question->score >= 5 ? 'medium' : 'low') }}">
                                            <i class="ti ti-star"></i> {{ $question->score }}/10
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- AI点评 --}}
                        @if(isset($question->feedback['comment']))
                            <div class="voice-message ai">
                                <div class="voice-message-avatar">
                                    <i class="ti ti-sparkles"></i>
                                </div>
                                <div class="voice-message-content">
                                    <div class="mb-1"><strong>AI点评</strong></div>
                                    <div>{{ $question->feedback['comment'] }}</div>
                                    @if(isset($question->feedback['suggestion']))
                                        <div class="mt-2 text-secondary small">{{ $question->feedback['suggestion'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                @endforeach

                {{-- 当前问题 --}}
                @php $nextQuestion = $interview->questions->firstWhere('answer', null); @endphp
                @if($nextQuestion && $interview->status !== 'completed')
                    <div class="voice-message ai" id="current-question">
                        <div class="voice-message-avatar">
                            <i class="ti ti-robot"></i>
                        </div>
                        <div class="voice-message-content">
                            <div class="mb-1"><strong>当前问题</strong></div>
                            <div>{{ $nextQuestion->question }}</div>
                            <button @click="isPlaying ? togglePlay() : showPlayConfirmDialog('{{ addslashes($nextQuestion->question) }}')"
                                class="playing-wave"
                                :class="{ 'is-playing': isPlaying && !isPaused }"
                                :style="isPlaying && !isPaused 
                                    ? 'background: linear-gradient(135deg, #ef4444, #dc2626); color: white; box-shadow: 0 6px 20px rgba(239,68,68,0.5), 0 0 0 3px rgba(239,68,68,0.3); animation: play-btn-pulse 1.2s ease-in-out infinite;' 
                                    : (isPaused 
                                        ? 'background: linear-gradient(135deg, #22c55e, #16a34a); color: white; box-shadow: 0 6px 20px rgba(34,197,94,0.5);' 
                                        : 'background: linear-gradient(135deg, #e8f4ff, #d6ebff); color: #206ee9; box-shadow: 0 3px 8px rgba(32,110,233,0.2);')"
                            >
                                <template x-if="!isPlaying">
                                    <i class="ti ti-volume playing-wave-icon"></i>
                                </template>
                                <template x-if="isPlaying && !isPaused">
                                    <i class="ti ti-player-pause playing-wave-icon"></i>
                                </template>
                                <template x-if="isPaused">
                                    <i class="ti ti-player-play playing-wave-icon"></i>
                                </template>
                                <span x-text="isPlaying && !isPaused ? '暂停' : (isPaused ? '继续' : '播放问题')"></span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- AI思考中 --}}
                <div class="voice-message ai" x-show="isProcessing">
                    <div class="voice-message-avatar">
                        <i class="ti ti-robot"></i>
                    </div>
                    <div class="voice-message-content">
                        <div class="d-flex align-items-center gap-2">
                            <div class="loading-spinner"></div>
                            <span>AI正在分析你的回答...</span>
                        </div>
                    </div>
                </div>

                {{-- 实时转录 --}}
                <div class="voice-message user" x-show="transcript && isRecording">
                    <div class="voice-message-avatar">
                        <i class="ti ti-microphone"></i>
                    </div>
                    <div class="voice-message-content">
                        <div class="mb-1"><strong>识别中...</strong></div>
                        <div x-text="transcript" class="transcript-text"></div>
                    </div>
                </div>
            </div>

            {{-- 控制区域 --}}
            @if($interview->status !== 'completed' && $nextQuestion)
                <div class="voice-controls">
                    {{-- 状态提示 --}}
                    <div class="status-text" :class="{ 'recording': isRecording }" x-text="statusText">
                        点击麦克风开始回答
                    </div>
                    
                    {{-- 麦克风按钮 --}}
                    <div class="mic-wrapper">
                        <button 
                            type="button"
                            class="mic-btn-main"
                            :class="{ 'recording': isRecording }" 
                            :disabled="isProcessing || !recognitionSupported"
                            @mousedown="startRecording" 
                            @mouseup="stopRecording"
                            @touchstart.prevent="startRecording"
                            @touchend.prevent="stopRecording"
                            :title="recognitionSupported ? '按住说话' : '浏览器不支持'"
                            :style="isRecording 
                                ? 'width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(145deg, #ff4757, #ff3344); color: white; border: none; box-shadow: 0 10px 35px rgba(255,71,87,0.6), 0 0 0 12px rgba(255,71,87,0.25); display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0 auto; transition: all 0.3s; animation: mic-pulse-recording 1s ease-in-out infinite;' 
                                : 'width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(145deg, #3370ff, #2f63e6); color: white; border: none; box-shadow: 0 10px 30px rgba(51,112,255,0.6), 0 0 0 12px rgba(51,112,255,0.2); display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0 auto; transition: all 0.3s;'"
                        >
                            <i class="ti ti-microphone" x-show="!isRecording" style="font-size: 48px;"></i>
                            <i class="ti ti-player-stop" x-show="isRecording" style="font-size: 48px;"></i>
                        </button>
                    </div>

                    {{-- 操作提示 --}}
                    <div class="voice-hints">
                        {{-- 浏览器不支持提示 --}}
                        <div x-show="!recognitionSupported" class="voice-hint-pill hint-warning">
                            <i class="ti ti-alert-circle"></i>
                            <span>浏览器不支持语音识别，请用 Chrome 或 Edge</span>
                        </div>
                        
                        {{-- 正常操作提示 --}}
                        <div x-show="recognitionSupported && !isRecording && !isProcessing" class="voice-hint-pill hint-normal">
                            <i class="ti ti-hand-click"></i>
                            <span>按住麦克风说话，松开自动提交</span>
                        </div>
                        
                        {{-- 录音中提示 --}}
                        <div x-show="isRecording" class="voice-hint-pill hint-recording">
                            <i class="ti ti-record-mail"></i>
                            <span>正在录音... 松开按钮提交回答</span>
                        </div>
                        
                        {{-- 处理中提示 --}}
                        <div x-show="isProcessing" class="voice-hint-pill hint-processing">
                            <div class="loading-spinner loading-spinner-sm"></div>
                            <span>AI正在分析，请稍候...</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="voice-controls text-center">
                    <div class="mb-3">
                        <i class="ti ti-check-circle text-success" style="font-size: 48px;"></i>
                    </div>
                    <h5>面试已完成</h5>
                    <p class="text-secondary">感谢参与AI语音面试</p>
                    <a href="{{ route('user.interviews.report', $interview) }}" class="btn btn-primary">
                        <i class="ti ti-file-text me-2"></i>查看面试报告
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- 播放确认弹窗 --}}
    <div class="play-confirm-modal" x-show="showPlayConfirm" x-transition.opacity>
        <div class="play-confirm-content">
            <div class="play-confirm-icon">
                <i class="ti ti-volume"></i>
            </div>
            <div class="play-confirm-title">准备播放</div>
            <div class="play-confirm-text">即将播放面试问题，请准备好</div>
            
            <template x-if="playCountdown > 0">
                <div class="play-confirm-countdown">
                    <span x-text="playCountdown"></span> 秒后开始播放
                </div>
            </template>
            
            <div class="play-confirm-buttons">
                <button type="button" @click="cancelPlay" class="btn-confirm-cancel">
                    关闭
                </button>
                <button type="button" @click="confirmPlay" class="btn-confirm-ok">
                    确定
                </button>
            </div>
        </div>
    </div>


</div>
@endsection
