(function() {
    var component = function() {
        return {
        interviewId: window.voiceSessionConfig.interviewId,
        currentQuestionId: window.voiceSessionConfig.currentQuestionId,
        currentRound: window.voiceSessionConfig.currentRound,
        
        // 语音识别相关
        recognition: null,
        recognitionSupported: false,
        isRecording: false,
        transcript: '',
        finalTranscript: '',
        
        // 语音合成相关
        synthesis: window.speechSynthesis,
        isPlaying: false,
        isPaused: false,
        currentText: '',
        
        // 状态
        isProcessing: false,
        showTips: true,
        statusText: '点击麦克风开始回答',
        pendingAnswerText: '',
        
        // 播放确认
        showPlayConfirm: false,
        pendingPlayText: '',
        playCountdown: 0,
        playTimer: null,

        quotaFetch(input, init) {
            if (window.quotaInterceptor?.fetch) {
                return window.quotaInterceptor.fetch(input, init);
            }

            return fetch(input, init);
        },

        resolveApiErrorMessage(payload, fallback = '提交失败，请重试') {
            if (!payload || typeof payload !== 'object') {
                return fallback;
            }

            if (typeof payload.error === 'string' && payload.error.trim() !== '') {
                return payload.error.trim();
            }

            if (typeof payload.message === 'string' && payload.message.trim() !== '') {
                const retryAfter = Number(payload.retry_after || 0);
                if (retryAfter > 0) {
                    return `${payload.message.trim()} 请在 ${retryAfter} 秒后重试。`;
                }

                return payload.message.trim();
            }

            if (payload.errors && typeof payload.errors === 'object') {
                const firstKey = Object.keys(payload.errors)[0];
                const firstError = firstKey ? payload.errors[firstKey] : null;
                if (Array.isArray(firstError) && firstError.length > 0) {
                    return String(firstError[0] || '').trim() || fallback;
                }
            }

            return fallback;
        },
        
        init() {
            this.initSpeechRecognition();
            this.initSpeechSynthesis();
            
            // 事件委托：处理动态创建的播放按钮点击
            this.$refs.messagesContainer?.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-voice-play]');
                if (btn) {
                    this.showPlayConfirmDialog(btn.dataset.voicePlay);
                }
            });
            
            // 自动显示播放确认
            setTimeout(() => {
                const questionElement = document.getElementById('current-question');
                if (questionElement && !this.hasUserInteracted()) {
                    const questionText = questionElement.querySelector('.voice-message-content div:last-of-type')?.textContent;
                    if (questionText) {
                        this.showPlayConfirmDialog(questionText);
                    }
                }
            }, 500);
            
            // 滚动到底部
            this.scrollToBottom();
        },
        
        hasUserInteracted() {
            return this.interview?.questions?.some(q => q.answer) ?? false;
        },
        
        initSpeechRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            
            if (!SpeechRecognition) {
                this.recognitionSupported = false;
                this.statusText = '浏览器不支持语音识别';
                return;
            }
            
            this.recognitionSupported = true;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'zh-CN';
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.maxAlternatives = 1;
            
            this.recognition.onstart = () => {
                this.isRecording = true;
                this.statusText = '正在录音...';
                this.transcript = '';
                this.finalTranscript = '';
            };
            
            this.recognition.onresult = (event) => {
                let interimTranscript = '';
                
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const transcript = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        this.finalTranscript += transcript;
                    } else {
                        interimTranscript += transcript;
                    }
                }
                
                this.transcript = this.finalTranscript + interimTranscript;
            };
            
            this.recognition.onerror = (event) => {
                if (event.error === 'no-speech') {
                    this.statusText = '未检测到语音，请重试';
                } else if (event.error === 'audio-capture') {
                    this.statusText = '无法访问麦克风，请检查设备';
                } else if (event.error === 'not-allowed') {
                    this.statusText = '麦克风权限被拒绝，请点击地址栏图标开启';
                    // 5秒后恢复默认提示
                    setTimeout(() => {
                        if (!this.isRecording && !this.isProcessing) {
                            this.statusText = '点击麦克风开始回答';
                        }
                    }, 5000);
                } else {
                    this.statusText = '识别出错，请重试';
                }
                this.isRecording = false;
            };
            
            this.recognition.onend = () => {
                if (this.isRecording) {
                    // 如果是意外结束，尝试重新启动
                    try {
                        this.recognition.start();
                    } catch (e) {
                        this.isRecording = false;
                    }
                }
            };
        },
        
        initSpeechSynthesis() {
            // 预加载语音
            if (this.synthesis) {
                this.synthesis.cancel();
            }
        },
        
        startRecording() {
            if (!this.recognitionSupported || this.isProcessing || !this.currentQuestionId) return;
            
            try {
                this.recognition.start();
            } catch (e) {
                // 可能已经在录音中，忽略重复启动错误
            }
        },
        
        stopRecording() {
            if (!this.isRecording) return;
            
            try {
                this.recognition.stop();
            } catch (e) {}
            
            this.isRecording = false;
            
            // 如果有识别内容，提交答案
            const answer = (this.finalTranscript || this.transcript).trim();
            if (answer.length >= 5) {
                this.submitAnswer(answer);
            } else if (answer.length > 0) {
                this.statusText = '回答太短，请重试';
                setTimeout(() => {
                    this.statusText = '点击麦克风开始回答';
                }, 2000);
            } else {
                this.statusText = '未识别到语音，请重试';
                setTimeout(() => {
                    this.statusText = '点击麦克风开始回答';
                }, 2000);
            }
        },
        
        speakText(text) {
            if (!this.synthesis) return;
            
            // 如果正在播放，先停止
            if (this.isPlaying && !this.isPaused) {
                this.synthesis.cancel();
                this.isPlaying = false;
                return;
            }
            
            // 保存当前文本
            this.currentText = text;
            this.isPaused = false;
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'zh-CN';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            
            // 尝试选择中文语音
            const voices = this.synthesis.getVoices();
            const zhVoice = voices.find(v => v.lang.includes('zh') || v.lang.includes('CN'));
            if (zhVoice) {
                utterance.voice = zhVoice;
            }
            
            utterance.onstart = () => {
                this.isPlaying = true;
                this.isPaused = false;
            };
            
            utterance.onend = () => {
                this.isPlaying = false;
                this.isPaused = false;
            };
            
            utterance.onerror = () => {
                this.isPlaying = false;
                this.isPaused = false;
            };
            
            this.synthesis.speak(utterance);
        },
        
        togglePlay() {
            if (!this.synthesis) return;
            
            if (this.isPlaying && !this.isPaused) {
                // 暂停播放
                this.synthesis.pause();
                this.isPaused = true;
            } else if (this.isPaused) {
                // 继续播放
                this.synthesis.resume();
                this.isPaused = false;
            } else if (this.currentText) {
                // 重新播放
                this.speakText(this.currentText);
            }
        },
        
        // 显示播放确认弹窗
        showPlayConfirmDialog(text) {
            this.pendingPlayText = text;
            this.showPlayConfirm = true;
            this.playCountdown = 0;
        },
        
        // 确认播放
        confirmPlay() {
            this.playCountdown = 2;
            
            // 倒计时
            this.playTimer = setInterval(() => {
                this.playCountdown--;
                if (this.playCountdown <= 0) {
                    clearInterval(this.playTimer);
                    this.showPlayConfirm = false;
                    this.speakText(this.pendingPlayText);
                    this.pendingPlayText = '';
                }
            }, 1000);
        },
        
        // 取消播放
        cancelPlay() {
            if (this.playTimer) {
                clearInterval(this.playTimer);
                this.playTimer = null;
            }
            this.showPlayConfirm = false;
            this.playCountdown = 0;
            this.pendingPlayText = '';
        },
        
        async submitAnswer(answer) {
            if (!this.currentQuestionId || this.isProcessing) return;
            
            this.isProcessing = true;
            this.statusText = 'AI分析中...';
            this.pendingAnswerText = answer;
            let shouldResetTranscript = true;
            
            // 添加用户消息到界面
            this.addMessage('user', answer);
            this.scrollToBottom();
            
            try {
                const response = await this.quotaFetch(window.voiceSessionConfig.submitAnswerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        question_id: this.currentQuestionId,
                        answer: answer
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok || data?.success === false || data?.error) {
                    this.statusText = this.resolveApiErrorMessage(data);
                    this.transcript = answer;
                    this.finalTranscript = answer;
                    shouldResetTranscript = false;
                    this.isProcessing = false;
                    return;
                }
                
                // 处理AI回复
                if (data.answer_reply) {
                    this.addMessage('ai-reply', data.answer_reply);
                    this.speakText(data.answer_reply);
                }
                
                // 处理追问
                if (data.counter_questions && data.counter_questions.length > 0) {
                    setTimeout(() => {
                        data.counter_questions.forEach((q, i) => {
                            setTimeout(() => {
                                this.addMessage('ai-followup', q);
                                if (i === data.counter_questions.length - 1) {
                                    this.speakText(q);
                                }
                            }, i * 500);
                        });
                    }, 500);
                }
                
                // 处理评分反馈
                if (!data.scoring_pending && data.feedback) {
                    setTimeout(() => {
                        const feedbackText = data.feedback.comment || '';
                        if (feedbackText) {
                            this.addMessage('ai-feedback', feedbackText, data.score);
                            this.speakText(feedbackText);
                        }
                    }, 1000);
                }
                
                // 处理下一题或结束
                if (data.finished) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else if (data.question) {
                    this.currentQuestionId = data.question.id;
                    this.currentRound = data.question.round_no;
                    
                    setTimeout(() => {
                        this.addMessage('ai-question', data.question.question, null, data.question.round_no);
                        this.speakText(data.question.question);
                    }, 2000);
                }
                
                this.statusText = '点击麦克风开始回答';
                
            } catch (error) {
                if (error?.quotaHandled) {
                    this.statusText = '已取消提交，可继续修改后重试';
                    this.transcript = answer;
                    this.finalTranscript = answer;
                    shouldResetTranscript = false;
                    return;
                }
                this.statusText = '提交失败，请重试';
                this.transcript = answer;
                this.finalTranscript = answer;
                shouldResetTranscript = false;
            } finally {
                this.isProcessing = false;
                this.pendingAnswerText = '';
                if (shouldResetTranscript) {
                    this.transcript = '';
                    this.finalTranscript = '';
                }
            }
            
            this.scrollToBottom();
        },
        
        addMessage(type, content, score = null, roundNo = null) {
            const container = this.$refs.messagesContainer;
            const messageDiv = document.createElement('div');
            messageDiv.className = 'voice-message ' + (type.startsWith('ai') ? 'ai' : 'user');
            
            let icon = type === 'user' ? 'ti-user' : 'ti-robot';
            let title = type === 'user' ? '我的回答' : 
                       type === 'ai-reply' ? '面试官' : 
                       type === 'ai-followup' ? '追问' : 
                       type === 'ai-feedback' ? 'AI点评' : '面试官';
            
            let scoreHtml = '';
            if (score !== null) {
                const scoreClass = score >= 7 ? 'high' : (score >= 5 ? 'medium' : 'low');
                scoreHtml = `<div class="mt-2"><span class="score-badge-voice ${scoreClass}"><i class="ti ti-star"></i> ${score}/10</span></div>`;
            }
            
            let badgeHtml = roundNo ? `<span class="badge bg-primary me-2">第${roundNo}题</span>` : '';
            
            messageDiv.innerHTML = `
                <div class="voice-message-avatar">
                    <i class="ti ${icon}"></i>
                </div>
                <div class="voice-message-content">
                    <div class="mb-1"><strong>${title}</strong></div>
                    ${badgeHtml}
                    <div>${this.escapeHtml(content)}</div>
                    ${scoreHtml}
                    ${type.startsWith('ai') ? `<button class="voice-play-btn" data-voice-play="${this.escapeHtml(content)}"><i class="ti ti-volume"></i> 播放</button>` : ''}
                </div>
            `;
            
            container.appendChild(messageDiv);
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },
        
        async confirmFinish() {
            const ok = typeof window.appConfirm === 'function'
                ? await window.appConfirm('确定要结束面试吗？结束后将无法继续答题。', { title: '结束确认', showCancel: true })
                : false;
            if (ok) {
                document.getElementById('finish-form').submit();
            }
        }
    };
    };

    if (typeof Alpine !== 'undefined') {
        Alpine.data('voiceInterview', function() { return component; });
        var el = document.querySelector('[x-data="voiceInterview"]');
        if (el) Alpine.initTree(el);
    } else {
        document.addEventListener('alpine:init', function() {
            Alpine.data('voiceInterview', function() { return component; });
        });
    }
})();
