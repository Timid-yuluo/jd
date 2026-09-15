(function() {
    if (!window.interviewSessionConfig || typeof window.interviewSessionConfig !== 'object') {
        return;
    }

    if (window.__interviewSessionComponentRegistered === true) {
        return;
    }
    window.__interviewSessionComponentRegistered = true;

    var component = {
        interviewId: window.interviewSessionConfig.interviewId,
        heartbeatUrl: window.interviewSessionConfig.heartbeatUrl,
        pollIntervalMs: window.interviewSessionConfig.pollIntervalMs,
        pollFastAttempts: window.interviewSessionConfig.pollFastAttempts,
        pollFastIntervalMs: window.interviewSessionConfig.pollFastIntervalMs,
        pollSlowIntervalMs: window.interviewSessionConfig.pollSlowIntervalMs,
        pollJitterMs: window.interviewSessionConfig.pollJitterMs,
        pollRequestTimeoutMs: window.interviewSessionConfig.pollRequestTimeoutMs,
        pollMaxAttempts: window.interviewSessionConfig.pollMaxAttempts,
        resumePollDelayMs: window.interviewSessionConfig.resumePollDelayMs,
        answer: '',
        loading: false,
        networkOnline: navigator.onLine,
        heartbeatFailedCount: 0,
        dialog: {
            visible: false,
            title: '提示',
            message: '',
            confirmText: '确定',
            cancelText: '取消',
            showCancel: false,
            variant: 'primary',
            resolver: null,
        },
        toast: {
            visible: false,
            message: '',
            variant: 'success',
            timer: null,
        },
        compactView: false,
        currentQuestionId: window.interviewSessionConfig.currentQuestionId,
        currentRound: window.interviewSessionConfig.currentRound,
        lastSubmitSignature: null,
        heartbeatTimer: null,
        lastTimeDividerLabel: '',

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
            this.initDisplayMode();
            this.startHeartbeat();
            this.bindMessageActions();
            window.addEventListener('online', () => {
                this.networkOnline = true;
                this.sendHeartbeat();
                this.showToast('网络已恢复，已继续同步面试状态。', 'success');
            });
            window.addEventListener('offline', () => {
                this.networkOnline = false;
                this.showToast('当前网络离线，提交与评分轮询已暂停。', 'warning');
            });
            window.addEventListener('interview:pause', async () => {
                try {
                    const r = await fetch(this.heartbeatUrl.replace('heartbeat', 'pause'), {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}});
                    const d = await r.json();
                    if (d.success) {
                        if (this.heartbeatTimer) { clearInterval(this.heartbeatTimer); this.heartbeatTimer = null; }
                        document.getElementById('pauseBtn').classList.add('d-none');
                        document.getElementById('resumeBtn').classList.remove('d-none');
                        document.getElementById('pauseOverlay').classList.remove('d-none');
                    }
                } catch(e) {}
            });
            window.addEventListener('interview:resume', async () => {
                try {
                    const r = await fetch(this.heartbeatUrl.replace('heartbeat', 'resume'), {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}});
                    const d = await r.json();
                    if (d.success) {
                        this.startHeartbeat();
                        document.getElementById('resumeBtn').classList.add('d-none');
                        document.getElementById('pauseBtn').classList.remove('d-none');
                        document.getElementById('pauseOverlay').classList.add('d-none');
                    }
                } catch(e) {}
            });
            this.scrollToBottom();
        },

        displayModeStorageKey() {
            return `interview:display:compact:${this.interviewId}`;
        },

        initDisplayMode() {
            const raw = localStorage.getItem(this.displayModeStorageKey());
            if (raw === '1') {
                this.compactView = true;
            } else if (raw === '0') {
                this.compactView = false;
            } else {
                this.compactView = false;
            }
        },

        setDisplayMode(isCompact) {
            this.compactView = !!isCompact;
            localStorage.setItem(this.displayModeStorageKey(), this.compactView ? '1' : '0');
        },

        startHeartbeat() {
            this.sendHeartbeat();
            this.heartbeatTimer = window.setInterval(() => this.sendHeartbeat(), 60000);
        },

        async sendHeartbeat() {
            if (!this.networkOnline) return;
            try {
                await fetch(this.heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                this.heartbeatFailedCount = 0;
            } catch (error) {
                this.heartbeatFailedCount += 1;
                            }
        },

        async submitAnswer() {
            if (!this.currentQuestionId) {
                this.showToast('当前题目状态异常，请刷新页面后重试。', 'danger');
                return;
            }
            if (this.loading || !this.networkOnline) return;
            if (this.answer.trim().length < 10) {
                this.showToast('回答至少需要 10 个字', 'warning');
                return;
            }

            this.loading = true;

            const userMessage = document.createElement('div');
            userMessage.className = 'message-user chat-enter';
            this.appendTimeDividerIfNeeded();
            userMessage.innerHTML = `
                <div class="message-row">
                    <div class="message-body">
                        <div class="message-meta message-meta-user">
                            <span class="message-sender">我</span>
                            <span class="message-meta-separator">·</span>
                            <span class="message-scene">回答</span>
                        </div>
                        <div class="message-content message-content-answer">
                            <div class="answer-card-title"><i class="ti ti-pencil"></i>我的回答</div>
                            <div class="answer-card-text">${this.escapeHtml(this.answer)}</div>
                        </div>
                        <div class="message-time message-time-user">${this.getCurrentTimeLabel()}</div>
                    </div>
                    <div class="message-avatar message-avatar-user"><i class="ti ti-user"></i></div>
                </div>
            `;
            document.getElementById('messages-container').appendChild(userMessage);
            this.onMessageInserted(true);

            const questionId = this.currentQuestionId;
            const answerText = this.answer;
            let serverAccepted = false;
            const aiThinkingId = `ai-thinking-${questionId}`;
            const aiThinkingHtml = `
                <div class="message-ai chat-enter" id="${aiThinkingId}">
                    <div class="message-row">
                        <div class="message-avatar message-avatar-ai"><i class="ti ti-robot"></i></div>
                        <div class="message-body">
                            <div class="message-meta">
                                <span class="message-sender">面试官</span>
                                <span class="message-meta-separator">·</span>
                                <span class="message-scene">分析中</span>
                            </div>
                            <div class="message-content text-secondary">
                                <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
                                正在分析你的回答...
                            </div>
                            <div class="message-time message-time-ai">${this.getCurrentTimeLabel()}</div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('messages-container').insertAdjacentHTML('beforeend', aiThinkingHtml);
            this.onMessageInserted(true);

            const lowQualityCheck = this.clientSideQualityCheck(answerText);
            if (lowQualityCheck.shouldConfirm) {
                const reasonText = lowQualityCheck.reasons.join('、');
                const proceed = await this.openDialog({
                    title: '提交前确认',
                    message: `检测到你的回答可能过于简短或不够具体（${reasonText}）。建议先补充再提交，是否仍要继续提交？`,
                    confirmText: '继续提交',
                    cancelText: '返回补充',
                    showCancel: true,
                    variant: 'warning',
                });
                if (!proceed) {
                    this.loading = false;
                    userMessage.remove();
                    document.getElementById(aiThinkingId)?.remove();
                    return;
                }
            }
            const submitSignature = `${questionId}:${answerText.trim()}`;
            if (this.lastSubmitSignature === submitSignature) {
                this.loading = false;
                return;
            }
            this.answer = '';

            const abortController = new AbortController();
            const requestTimeoutMs = 35000;
            const timeoutId = window.setTimeout(() => abortController.abort(), requestTimeoutMs);

            try {

                const response = await this.quotaFetch(window.interviewSessionConfig.submitAnswerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    signal: abortController.signal,
                    body: JSON.stringify({
                        question_id: questionId,
                        answer: answerText
                    })
                });
                const data = await response.json();

                if (!response.ok || data?.success === false || data?.error) {
                    this.showToast(this.resolveApiErrorMessage(data), 'danger');
                    this.answer = answerText;
                    userMessage.remove();
                    document.getElementById(aiThinkingId)?.remove();
                    this.lastSubmitSignature = null;
                    this.loading = false;
                    return;
                }

                serverAccepted = true;
                this.lastSubmitSignature = submitSignature;
                if (data.idempotent) {
                    userMessage.remove();
                }
                document.getElementById(aiThinkingId)?.remove();

                if (!data.idempotent && data.answer_reply) {
                    this.appendTimeDividerIfNeeded();
                    const replyHtml = this.renderAssistantReplyHtml(data.answer_reply);
                    document.getElementById('messages-container').insertAdjacentHTML('beforeend', replyHtml);
                    this.onMessageInserted();
                }
                const counterQuestions = Array.isArray(data.counter_questions)
                    ? data.counter_questions
                    : (data.counter_question ? [data.counter_question] : []);
                const dialogueAction = data?.dialogue?.action || 'continue';
                const dialogueConfidence = Number(data?.dialogue?.confidence || 0);
                if (!data.idempotent && dialogueAction !== 'continue') {
                    const modeLabel = dialogueAction === 'deep_probe' ? '深度追问' : '追问';
                    this.showToast(`AI已进入${modeLabel}模式（置信度 ${Math.round(dialogueConfidence * 100)}%）`, 'warning', 3400);
                }
                if (!data.idempotent && counterQuestions.length > 0) {
                    this.insertCounterQuestionsSequentially(counterQuestions, data.answer_quality || null);
                }

                if (!data.idempotent) {
                    if (data.scoring_pending) {
                        this.appendTimeDividerIfNeeded();
                        const pendingHtml = `
                            <div class="message-feedback chat-enter" id="score-pending-${data.question_id}">
                                <div class="message-content text-secondary">
                                    <i class="ti ti-loader me-1"></i>AI评分中...
                                </div>
                            </div>
                        `;
                        document.getElementById('messages-container').insertAdjacentHTML('beforeend', pendingHtml);
                        this.onMessageInserted();
                        this.showToast('回答已提交，AI 正在评分。', 'success');
                        this.pollEvaluationStatus(data.question_id);
                    } else {
                        this.appendTimeDividerIfNeeded();
                        const feedbackHtml = this.renderFeedbackHtml(data.score, data.feedback);
                        document.getElementById('messages-container').insertAdjacentHTML('beforeend', feedbackHtml);
                        this.onMessageInserted();
                        this.showToast('回答已提交，评分已返回。', 'success');
                    }
                }

                if (data.finished) {
                    document.getElementById('next-question-area')?.remove();
                    document.getElementById('next-round-divider')?.remove();
                    const finishTitle = data.terminated_early
                        ? '面试已由AI提前结束'
                        : '面试已结束';
                    const finishReason = data.terminated_early && data.termination_reason
                        ? `<div class="small text-warning mt-1"><i class="ti ti-alert-circle me-1"></i>${this.escapeHtml(data.termination_reason)}</div>`
                        : '';
                    this.appendTimeDividerIfNeeded();
                    document.getElementById('messages-container').insertAdjacentHTML('beforeend', `
                        <div class="text-center py-4 text-secondary">
                            <i class="ti ti-check-circle me-1"></i>${finishTitle}
                            ${finishReason}
                            <a href="${data.redirect}" class="btn btn-primary btn-sm ms-2">查看报告</a>
                        </div>
                    `);
                    this.onMessageInserted();
                    document.getElementById('answer-form')?.remove();
                    document.getElementById('progress-badge').outerHTML = '<span class="badge bg-success">已完成</span>';
                } else if (data.question) {
                    document.getElementById('next-round-divider')?.remove();
                    document.getElementById('next-question-area')?.remove();
                    const nextQuestionHtml = `
                        ${this.renderRoundDivider(data.question.round_no, '当前题目')}
                        <div class="message-ai chat-enter" id="next-question-area">
                            <div class="message-row">
                                <div class="message-avatar message-avatar-ai"><i class="ti ti-robot"></i></div>
                                <div class="message-body">
                                    <div class="message-meta">
                                        <span class="message-sender">面试官</span>
                                        <span class="message-meta-separator">·</span>
                                        <span class="message-scene">提问</span>
                                    </div>
                                    <span class="question-badge" id="next-round-badge">面试题 ${data.question.round_no}</span>
                                    <div class="message-content message-content-question" id="next-question-content">
                                        <div class="question-card-title"><i class="ti ti-message-chatbot"></i>问题</div>
                                        <div class="question-card-text">${this.escapeHtml(data.question.question)}</div>
                                    </div>
                                    <div class="message-time message-time-ai">${this.getCurrentTimeLabel()}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    this.appendTimeDividerIfNeeded();
                    document.getElementById('messages-container').insertAdjacentHTML('beforeend', nextQuestionHtml);
                    this.onMessageInserted();
                    this.currentQuestionId = data.question.id;
                    this.currentRound = data.question.round_no;
                    const currentRoundEl = document.getElementById('current-round');
                    if (currentRoundEl) {
                        currentRoundEl.textContent = data.question.round_no;
                    }
                }
            } catch (error) {
                if (error?.quotaHandled) {
                    this.loading = false;
                    if (!serverAccepted) {
                        this.answer = answerText;
                    }
                    userMessage.remove();
                    document.getElementById(aiThinkingId)?.remove();
                    this.lastSubmitSignature = null;
                    return;
                }

                const msg = error?.name === 'AbortError'
                    ? 'AI评分超时，请重试（建议稍后再提交）'
                    : '提交失败，请重试';
                this.showToast(msg, 'danger');
                if (!serverAccepted) {
                    this.answer = answerText;
                }
                userMessage.remove();
                document.getElementById(aiThinkingId)?.remove();
                this.lastSubmitSignature = null;
            } finally {
                window.clearTimeout(timeoutId);
                if (serverAccepted) {
                    const submittedText = answerText.trim();
                    if (submittedText !== '' && this.answer.trim() === submittedText) {
                        this.answer = '';
                    }
                    this.$nextTick(() => {
                        const input = this.$refs.answerInput;
                        if (!input) return;
                        if (submittedText !== '' && input.value.trim() === submittedText) {
                            input.value = '';
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                }
            }

            this.loading = false;
            this.scrollToBottom();
        },

        async pollEvaluationStatus(questionId, attempt = 0) {
            const maxAttempts = this.pollMaxAttempts;
            if (!questionId || attempt >= maxAttempts) {
                const pendingNode = document.getElementById(`score-pending-${questionId}`);
                if (pendingNode) {
                    pendingNode.outerHTML = `
                        <div class="message-feedback">
                            <div class="message-content text-secondary">
                                <i class="ti ti-clock me-1"></i>评分耗时较长，面试流程不受影响。系统仍在后台补评分，稍后会自动更新到报告页。
                            </div>
                        </div>
                    `;
                }
                window.setTimeout(() => this.pollEvaluationStatus(questionId, 0), this.resumePollDelayMs);
                return;
            }

            try {
                const abortController = new AbortController();
                const timeoutId = window.setTimeout(() => abortController.abort(), this.pollRequestTimeoutMs);
                const response = await fetch(`${window.interviewSessionConfig.evaluationStatusUrl}?question_id=${questionId}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                    signal: abortController.signal,
                });
                window.clearTimeout(timeoutId);
                const data = await response.json();

                if (!data.scoring_pending) {
                    const pendingNode = document.getElementById(`score-pending-${questionId}`);
                    if (pendingNode) {
                        pendingNode.outerHTML = this.renderFeedbackHtml(data.score, data.feedback);
                    }
                    this.onMessageInserted();
                    return;
                }
            } catch (error) {
                            }

            const delay = this.resolvePollDelay(attempt);
            window.setTimeout(() => {
                this.pollEvaluationStatus(questionId, attempt + 1);
            }, delay);
        },

        resolvePollDelay(attempt) {
            const useFast = attempt < this.pollFastAttempts;
            const base = useFast ? this.pollFastIntervalMs : this.pollSlowIntervalMs;
            const jitter = this.pollJitterMs > 0
                ? Math.floor(Math.random() * (this.pollJitterMs + 1))
                : 0;
            return Math.max(200, base + jitter);
        },

        renderFeedbackHtml(score, feedback) {
            const safeScore = Number.isInteger(score) ? score : 6;
            const commentText = this.formatReadableText(feedback?.comment || '');
            const suggestionText = this.formatReadableText(feedback?.suggestion || '');
            return `
                <div class="message-feedback chat-enter">
                    <div class="message-content">
                        <div class="feedback-card-title"><i class="ti ti-sparkles"></i>AI点评</div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="score-badge bg-${safeScore >= 7 ? 'success' : (safeScore >= 5 ? 'warning' : 'danger')} text-white me-2">
                                ${safeScore}/10
                            </span>
                        </div>
                        ${feedback?.comment ? `<div class="mb-1 feedback-text"><strong>点评：</strong>${commentText}</div>` : ''}
                        ${feedback?.suggestion ? `<div class="text-secondary small feedback-text"><strong>建议：</strong>${suggestionText}</div>` : ''}
                        ${Array.isArray(feedback?.fluency_issues) && feedback.fluency_issues.length > 0
                            ? `<div class="text-secondary small mt-1"><strong>AI连贯性提示：</strong>${feedback.fluency_issues.map((item) => this.escapeHtml(item)).join('；')}</div>`
                            : ''
                        }
                        ${this.renderAiEvidenceHtml(feedback)}
                    </div>
                    <div class="message-time message-time-ai">${this.getCurrentTimeLabel()}</div>
                </div>
            `;
        },

        renderAiEvidenceHtml(feedback) {
            const dialogueConfidence = Number(feedback?.dialogue_confidence ?? 0);
            const fluencyConfidence = Number(feedback?.fluency_confidence ?? 0);
            if (!dialogueConfidence && !fluencyConfidence) {
                return '';
            }
            const dialogueAction = this.escapeHtml(String(feedback?.dialogue_action || 'continue'));
            const fluencyDetectedBy = this.escapeHtml(String(feedback?.fluency_detected_by || 'rule'));
            return `
                <details class="text-secondary small mt-1">
                    <summary>AI判定依据</summary>
                    <div class="mt-1">对话决策 ${Math.round(dialogueConfidence * 100)}%（${dialogueAction}）</div>
                    <div>连贯性 ${Math.round(fluencyConfidence * 100)}%（${fluencyDetectedBy}）</div>
                </details>
            `;
        },

        formatReadableText(text) {
            const safe = this.escapeHtml(String(text || ''));
            return safe.replace(/\s*([0-9]+[\.、])\s*/g, '<br>$1 ');
        },

        renderAssistantReplyHtml(reply) {
            return `
                <div class="message-ai chat-enter">
                    <div class="message-row">
                        <div class="message-avatar message-avatar-ai"><i class="ti ti-robot"></i></div>
                        <div class="message-body">
                            <div class="message-meta">
                                <span class="message-sender">面试官</span>
                                <span class="message-meta-separator">·</span>
                                <span class="message-scene">反馈</span>
                            </div>
                            <div class="message-content">${this.escapeHtml(reply)}</div>
                            <div class="message-time message-time-ai">${this.getCurrentTimeLabel()}</div>
                        </div>
                    </div>
                </div>
            `;
        },

        renderCounterQuestionHtml(counterQuestion, quality, order = 1) {
            const encodedCounter = encodeURIComponent(counterQuestion || '');
            const reasons = Array.isArray(quality?.reasons) && quality.reasons.length > 0
                ? `<div class="text-secondary small mt-1">触发原因：${quality.reasons.map((i) => this.escapeHtml(i)).join('、')}</div>`
                : '';
            return `
                <div class="message-ai chat-enter message-ai-followup">
                    <div class="message-row">
                        <div class="message-avatar message-avatar-ai"><i class="ti ti-robot"></i></div>
                        <div class="message-body">
                            <div class="message-meta">
                                <span class="message-sender">面试官</span>
                                <span class="message-meta-separator">·</span>
                                <span class="message-scene">追问${order > 1 ? ` #${order}` : ''}</span>
                            </div>
                            <div class="message-content border border-warning-subtle bg-warning-lt">
                                <div class="followup-chip"><i class="ti ti-help-circle"></i>追问${order > 1 ? ` #${order}` : ''}</div>
                                <div>${this.escapeHtml(counterQuestion)}</div>
                                ${reasons}
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="fill-followup" data-question="${encodedCounter}">
                                        <i class="ti ti-edit-circle me-1"></i>快速补充回答
                                    </button>
                                </div>
                            </div>
                            <div class="message-time message-time-ai">${this.getCurrentTimeLabel()}</div>
                        </div>
                    </div>
                </div>
            `;
        },

        insertCounterQuestionsSequentially(counterQuestions, quality) {
            const list = Array.isArray(counterQuestions) ? counterQuestions : [];
            list.forEach((question, index) => {
                const delay = Math.min(1400, index * 520);
                window.setTimeout(() => {
                    this.appendTimeDividerIfNeeded();
                    const html = this.renderCounterQuestionHtml(question, quality, index + 1);
                    document.getElementById('messages-container').insertAdjacentHTML('beforeend', html);
                    this.onMessageInserted();
                }, delay);
            });
        },

        answerLength() {
            return this.answer.trim().length;
        },

        answerProgressPercent() {
            const target = 80;
            return Math.max(0, Math.min(100, Math.round((this.answerLength() / target) * 100)));
        },

        hasActionKeyword() {
            return /(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成)/u.test(this.answer);
        },

        hasMetricKeyword() {
            return /(\d+%|\d+\+|[1-9]\d{1,}|提升|增长|降低|缩短|减少|节省)/u.test(this.answer);
        },

        hasFluencyIssue(text = null) {
            const target = (typeof text === 'string' ? text : this.answer).trim();
            if (!target) return false;
            const fillerCount = (target.match(/(然后|就是|那个|嗯|啊|呃)/gu) || []).length;
            if (fillerCount >= 6) return true;
            if (/(然后然后|就是就是|那个那个|嗯嗯|啊啊|。。。|，，，|！！！|？？？|是否是|是不是是)/u.test(target)) return true;
            const hasAction = /(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成|分析|设计|实现|处理|沟通)/u.test(target);
            const hasConnector = /(首先|然后|最后|因此|所以|因为|同时|另外|通过|为了|并且|从而)/u.test(target);
            const hasPunctuation = /[，。！？；,!.?;]/u.test(target);
            if (target.length >= 14 && !hasPunctuation && !hasAction && !hasConnector) return true;
            const shiDeCount = (target.match(/[的是]/gu) || []).length;
            if (target.length >= 18 && (shiDeCount / Math.max(1, target.length)) >= 0.34 && !hasAction) return true;
            if (target.length >= 60 && !hasPunctuation) return true;
            return false;
        },

        hasNonAnswerKeyword(text = null) {
            const target = typeof text === 'string' ? text : this.answer;
            return /(不知道|不清楚|不会|不了解|没做过|没经验|想不起来|不太会)/u.test(target);
        },

        clientSideQualityCheck(text) {
            const answer = (text || '').trim();
            const reasons = [];
            if (this.hasNonAnswerKeyword(answer)) reasons.push('信息量偏低');
            if (answer.length < 20) reasons.push('回答过短');
            if (!/(负责|推动|制定|执行|协调|落地|复盘|优化|跟进|达成)/u.test(answer)) reasons.push('缺少行动过程');
            if (!/(\d+%|\d+\+|[1-9]\d{1,}|提升|增长|降低|缩短|减少|节省)/u.test(answer)) reasons.push('缺少结果指标');
            if (this.hasFluencyIssue(answer)) reasons.push('语言不够通顺');
            return {
                shouldConfirm: reasons.length >= 2,
                reasons,
            };
        },

        appendSnippet(type) {
            const snippetMap = {
                background: '背景：当时业务目标是___，主要挑战是___。',
                action: '行动：我先___，再___，并与___协同推进。',
                result: '结果：最终___，关键指标从___提升到___（或下降___）。',
                review: '复盘：这次经验让我意识到___，后续我会___。',
            };
            const snippet = snippetMap[type] || '';
            if (!snippet) return;
            const separator = this.answer.trim().length > 0 ? '\n' : '';
            this.answer = `${this.answer}${separator}${snippet}`;
            this.$nextTick(() => this.$refs.answerInput?.focus());
        },

        openDialog(options = {}) {
            return new Promise((resolve) => {
                this.dialog.title = options.title || '提示';
                this.dialog.message = options.message || '';
                this.dialog.confirmText = options.confirmText || '确定';
                this.dialog.cancelText = options.cancelText || '取消';
                this.dialog.showCancel = !!options.showCancel;
                this.dialog.variant = options.variant || 'primary';
                this.dialog.visible = true;
                this.dialog.resolver = resolve;
            });
        },

        resolveDialog(result) {
            const resolver = this.dialog.resolver;
            this.dialog.visible = false;
            this.dialog.resolver = null;
            if (typeof resolver === 'function') {
                resolver(result);
            }
        },

        handleDialogEscape() {
            if (!this.dialog.visible) return;
            if (this.dialog.showCancel) {
                this.resolveDialog(false);
                return;
            }
            this.resolveDialog(true);
        },

        showToast(message, variant = 'success', durationMs = 2800) {
            if (this.toast.timer) {
                clearTimeout(this.toast.timer);
            }
            this.toast.message = message;
            this.toast.variant = variant;
            this.toast.visible = true;
            this.toast.timer = setTimeout(() => this.hideToast(), durationMs);
        },

        hideToast() {
            if (this.toast.timer) {
                clearTimeout(this.toast.timer);
                this.toast.timer = null;
            }
            this.toast.visible = false;
            this.toast.message = '';
        },

        bindMessageActions() {
            const container = document.getElementById('messages-container');
            if (!container || container.dataset.actionsBound === '1') return;
            container.dataset.actionsBound = '1';
            container.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action]');
                if (!button) return;
                const action = button.dataset.action || '';
                if (action === 'fill-followup') {
                    const followupQuestion = decodeURIComponent(button.dataset.question || '');
                    this.fillFollowupAnswerDraft(followupQuestion);
                }
            });
        },

        fillFollowupAnswerDraft(counterQuestion) {
            const prompt = (counterQuestion || '').trim();
            const draft = [
                `针对追问：${prompt}`,
                '背景：',
                '行动：',
                '结果（含数据）：',
                '复盘：',
            ].join('\n');

            if (this.answer.trim().length === 0) {
                this.answer = draft;
            } else if (!this.answer.includes('针对追问：')) {
                this.answer = `${this.answer.trim()}\n\n${draft}`;
            }

            this.$nextTick(() => {
                this.$refs.answerInput?.focus();
            });
            this.showToast('已生成追问补充模板，请补充真实细节后提交。', 'success');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        renderRoundDivider(roundNo, label = '') {
            const safeRound = Number.isFinite(Number(roundNo)) ? Number(roundNo) : this.currentRound;
            const suffix = label ? ` · ${this.escapeHtml(label)}` : '';
            return `
                <div class="chat-divider chat-enter">
                    <span class="chat-divider-badge">第 ${safeRound} 轮${suffix}</span>
                </div>
            `;
        },

        getCurrentTimeLabel() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            return `${h}:${m}`;
        },

        renderTimeDivider(label) {
            return `
                <div class="chat-time-divider chat-enter">
                    <span>${this.escapeHtml(label)}</span>
                </div>
            `;
        },

        appendTimeDividerIfNeeded() {
            const label = this.getCurrentTimeLabel();
            if (!label || this.lastTimeDividerLabel === label) return;
            this.lastTimeDividerLabel = label;
            document.getElementById('messages-container').insertAdjacentHTML('beforeend', this.renderTimeDivider(label));
        },

        onMessageInserted(forceScroll = false) {
            this.scrollToBottom();
        },

        scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (!container) return;
            container.scrollTop = container.scrollHeight;
        }
    };

    if (typeof Alpine !== 'undefined') {
        Alpine.data('interviewSession', function() { return component; });
    } else {
        document.addEventListener('alpine:init', function() {
            Alpine.data('interviewSession', function() { return component; });
        }, { once: true });
    }
})();

(() => {
    const dialogMask = document.getElementById('global-dialog-mask');
    const dialogTitle = document.getElementById('global-dialog-title');
    const dialogMessage = document.getElementById('global-dialog-message');
    const dialogCancel = document.getElementById('global-dialog-cancel');
    const dialogConfirm = document.getElementById('global-dialog-confirm');

    const openGlobalDialog = (options = {}) => new Promise((resolve) => {
        if (!dialogMask) {
            resolve(false);
            return;
        }

        dialogTitle.textContent = options.title || '提示';
        dialogMessage.textContent = options.message || '';
        dialogConfirm.textContent = options.confirmText || '确定';
        dialogCancel.textContent = options.cancelText || '取消';
        dialogConfirm.className = `btn ${options.variant === 'danger' ? 'btn-danger' : (options.variant === 'warning' ? 'btn-warning' : 'btn-primary')}`;

        if (options.showCancel) {
            dialogCancel.classList.remove('d-none');
        } else {
            dialogCancel.classList.add('d-none');
        }

        dialogMask.style.display = 'flex';

        const cleanup = () => {
            dialogMask.style.display = 'none';
            dialogCancel.onclick = null;
            dialogConfirm.onclick = null;
            dialogMask.onclick = null;
            window.removeEventListener('keydown', escHandler);
        };

        const escHandler = (event) => {
            if (event.key !== 'Escape') return;
            cleanup();
            resolve(options.showCancel ? false : true);
        };
        window.addEventListener('keydown', escHandler);

        dialogCancel.onclick = () => {
            cleanup();
            resolve(false);
        };
        dialogConfirm.onclick = () => {
            cleanup();
            resolve(true);
        };
        dialogMask.onclick = (event) => {
            if (event.target !== dialogMask) return;
            cleanup();
            resolve(options.showCancel ? false : true);
        };
    });

    const finishForm = document.getElementById('finish-interview-form');
    if (finishForm && finishForm.dataset.boundSubmit !== '1') {
        finishForm.dataset.boundSubmit = '1';
        finishForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const confirmed = await openGlobalDialog({
                title: '结束面试确认',
                message: '结束后本场面试将立即进入报告页，当前未答题目不会继续生成。确定结束吗？',
                confirmText: '确认结束',
                cancelText: '继续作答',
                showCancel: true,
                variant: 'warning',
            });
            if (confirmed) {
                finishForm.submit();
            }
        });
    }
})();
