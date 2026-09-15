function showLoading(form, text) {
    const btn = form.querySelector('.submit-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${text}`;
    }
    return true;
}
function copyOptimized() {
    const text = document.querySelector('.optimized-preview')?.textContent ?? '';
    navigator.clipboard.writeText(text).then(() => {
        if (typeof window.appNotify === 'function') window.appNotify('已复制到剪贴板', 'success');
    }).catch(() => {
        if (typeof window.appNotify === 'function') window.appNotify('复制失败，请手动复制', 'error');
    });
}

async function startStreamOptimize(form) {
    const btn = document.getElementById('optimize-btn');
    const targetJob = document.getElementById('target-job-input').value;
    const targetCompany = document.getElementById('target-company-input').value;
    const targetJobTitle = document.getElementById('target-job-title-input').value;
    const targetJobDescription = document.getElementById('target-job-description-input').value;
    const goalCheckboxes = form.querySelectorAll('input[name="optimize_goals[]"]:checked');
    const optimizeGoals = Array.from(goalCheckboxes).map(cb => cb.value);

    if (optimizeGoals.length === 0) {
        if (typeof window.appNotify === 'function') window.appNotify('请至少选择一项优化目标', 'warning');
        return false;
    }

    const emptyState = document.getElementById('optimize-empty-state');
    const outputArea = document.getElementById('stream-output-area');
    const streamText = document.getElementById('stream-text');
    const streamStatus = document.getElementById('stream-status');
    const highlightsArea = document.getElementById('stream-highlights');
    const highlightsList = document.getElementById('stream-highlights-list');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>AI 生成中...';

    if (emptyState) emptyState.classList.add('d-none');
    outputArea.classList.remove('d-none');
    streamText.textContent = '';
    highlightsArea.classList.add('d-none');
    highlightsList.innerHTML = '';
    streamStatus.textContent = '生成中...';
    streamStatus.className = 'badge bg-primary';

    let fullBuffer = '';
    const requestUrl = document.querySelector('[data-route-user-resumes-optimize-stream-0]')?.getAttribute('data-route-user-resumes-optimize-stream-0') || '';
    const baseBody = {
        target_job: targetJob,
        target_company: targetCompany,
        target_job_title: targetJobTitle,
        target_job_description: targetJobDescription,
        optimize_goals: optimizeGoals,
    };

    // 断线重连状态
    let streamId = null;
    let lastEventId = null;
    const maxReconnectAttempts = 3;
    let reconnectAttempts = 0;
    let completed = false;

    /**
     * 发起一次流式请求并处理 SSE 事件。
     * 返回 true 表示流正常结束（成功或失败），false 表示需要重连。
     */
    async function attemptStream() {
        const requestHeaders = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'text/event-stream',
        };
        // 断线重连时携带 Last-Event-ID 供服务端续传
        if (lastEventId) {
            requestHeaders['Last-Event-ID'] = lastEventId;
        }

        const requestInit = {
            method: 'POST',
            headers: requestHeaders,
            body: JSON.stringify(baseBody),
        };

        let response;
        try {
            response = await fetch(requestUrl, requestInit);
        } catch (networkErr) {
            // fetch 本身抛出（网络中断/DNS 失败等），可重连
            return { kind: 'reconnectable', error: networkErr };
        }

        if ((response.status === 403 || response.status === 429) && window.quotaInterceptor?.handleManualResponse) {
            response = await window.quotaInterceptor.handleManualResponse(
                response,
                window.quotaInterceptor.makeRetryRequest(requestUrl, requestInit, { preserveAccept: true })
            );
        }

        if (!response.ok || !response.body) {
            const message = response.status === 403
                ? '当前套餐不支持该优化模式，请升级后重试。'
                : '请求失败，请稍后重试';
            return { kind: 'fatal', error: new Error(message) };
        }

        // 读取服务端分配的 streamId，重连时无需再次携带（服务端按 Last-Event-ID 续传）
        const responseStreamId = response.headers.get('X-Stream-Id');
        if (responseStreamId) {
            streamId = responseStreamId;
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        // 心跳超时检测：60 秒无数据则提示用户
        let lastChunkTime = Date.now();
        const heartbeatTimeout = 60000;
        const heartbeatTimer = setInterval(() => {
            if (Date.now() - lastChunkTime > heartbeatTimeout) {
                streamStatus.textContent = '连接缓慢';
                streamStatus.className = 'badge bg-warning';
                if (typeof window.appNotify === 'function') {
                    window.appNotify('AI 响应较慢，正在继续生成，请耐心等待...', 'info');
                }
                lastChunkTime = Date.now(); // 重置以避免重复提示
            }
        }, 10000);

        try {
            while (true) {
                let readResult;
                try {
                    readResult = await reader.read();
                } catch (readErr) {
                    // 读取过程中网络中断，可重连
                    return { kind: 'reconnectable', error: readErr };
                }
                const { done, value } = readResult;
                if (done) break;

                lastChunkTime = Date.now();
                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');

                let currentEvent = null;
                for (const line of lines) {
                    // SSE id 字段：记录最后事件 ID，用于断线重连
                    if (line.startsWith('id: ')) {
                        lastEventId = line.slice(4).trim();
                        continue;
                    }
                    // SSE event 字段：当前事件类型
                    if (line.startsWith('event: ')) {
                        currentEvent = line.slice(7).trim();
                        continue;
                    }
                    if (!line.startsWith('data: ')) continue;
                    const data = line.slice(6);
                    if (!data) continue;

                    try {
                        const parsed = JSON.parse(data);
                        // 断线重连恢复提示
                        if (currentEvent === 'resume') {
                            streamStatus.textContent = '恢复中';
                            streamStatus.className = 'badge bg-info';
                            if (typeof window.appNotify === 'function' && parsed.message) {
                                window.appNotify(parsed.message, 'info');
                            }
                            currentEvent = null;
                            continue;
                        }
                        if (parsed.chunk) {
                            fullBuffer += parsed.chunk;
                            streamText.textContent = fullBuffer;
                            streamText.scrollTop = streamText.scrollHeight;
                        }
                        if (parsed.success) {
                            streamStatus.textContent = '已完成';
                            streamStatus.className = 'badge bg-success';
                            streamText.textContent = parsed.optimized_text;
                            completed = true;

                            if (parsed.highlights && parsed.highlights.length > 0) {
                                highlightsArea.classList.remove('d-none');
                                highlightsList.innerHTML = parsed.highlights.map(h => `
                                    <div class="col-md-6">
                                        <div class="card bg-success-lt">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-sparkles text-success me-2"></i>
                                                    <span class="text-break">${escapeHtml(h)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('');
                            }
                        }
                        if (parsed.error) {
                            streamStatus.textContent = '失败';
                            streamStatus.className = 'badge bg-danger';
                            streamText.textContent = '生成失败：' + parsed.error;
                            completed = true;
                        }
                    } catch (e) {
                        // ignore parse errors
                    }
                    currentEvent = null;
                }
            }
            // 流正常结束
            return { kind: 'done' };
        } finally {
            clearInterval(heartbeatTimer);
        }
    }

    // 主循环：带断线重连的流式请求
    try {
        while (reconnectAttempts <= maxReconnectAttempts) {
            const result = await attemptStream();

            if (result.kind === 'done' || completed) {
                break;
            }

            if (result.kind === 'fatal') {
                throw result.error;
            }

            if (result.kind === 'reconnectable') {
                reconnectAttempts++;
                if (reconnectAttempts > maxReconnectAttempts) {
                    throw new Error('网络连接多次中断，请检查网络后重试。');
                }
                streamStatus.textContent = '重连中';
                streamStatus.className = 'badge bg-warning';
                if (typeof window.appNotify === 'function') {
                    window.appNotify(`网络中断，正在尝试重连（第 ${reconnectAttempts}/${maxReconnectAttempts} 次）...`, 'warning');
                }
                // 指数退避：1s, 2s, 4s
                await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, reconnectAttempts - 1)));
                continue;
            }

            break;
        }
    } catch (err) {
        if (err?.quotaHandled) {
            return false;
        }
        streamStatus.textContent = '失败';
        streamStatus.className = 'badge bg-danger';
        streamText.textContent = '请求失败：' + err.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-sparkles me-2"></i>按目标岗位重新优化';
    }

    return false;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 事件委托
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    switch (btn.dataset.action) {
        case 'submit-optimize-form':
            document.getElementById('optimize-form').dispatchEvent(new Event('submit'));
            break;
        case 'copy-optimized':
            copyOptimized();
            break;
    }
});

document.addEventListener('submit', function(e) {
    if (e.target.matches('[data-action="stream-optimize"]')) {
        if (startStreamOptimize(e.target) === false) {
            e.preventDefault();
        }
    }
});
