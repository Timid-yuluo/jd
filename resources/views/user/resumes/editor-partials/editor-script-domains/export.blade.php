        async exportPdf() {
            const notify = typeof window.appNotify === 'function'
                ? window.appNotify
                : () => {};
            const readiness = this.exportReadinessCheck();
            if (!readiness.pass) {
                notify(readiness.message, 'warning');
                if (readiness.focusIssue) {
                    this.focusChecklistItem(readiness.focusIssue);
                }
                return;
            }
            if (Array.isArray(readiness.warningIssues) && readiness.warningIssues.length > 0) {
                const ok = typeof window.appConfirm === 'function'
                    ? await window.appConfirm(readiness.message, { title: '导出确认' })
                    : false;
                if (!ok) {
                    return;
                }
            }
            const createUrl = this.config.exportTaskCreateUrl || '';
            const statusTemplate = this.config.exportTaskStatusUrlTemplate || '';
            const resumeId = Number(this.config.resumeId || 0);
            if (!createUrl || !statusTemplate || !resumeId) {
                notify('导出配置缺失，请刷新页面后重试。', 'error');
                return;
            }

            const wait = (ms) => new Promise(resolve => window.setTimeout(resolve, ms));
            const pollTask = async (taskId, maxRounds = 45, intervalMs = 2000) => {
                for (let i = 0; i < maxRounds; i++) {
                    const statusUrl = statusTemplate.replace('__TASK_ID__', encodeURIComponent(taskId));
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) {
                        throw new Error('查询导出任务状态失败');
                    }
                    const data = await response.json();
                    if (!data?.success || !data?.task) {
                        throw new Error(data?.message || '导出任务状态异常');
                    }
                    if (data.task.status === 'completed') {
                        return data.task;
                    }
                    if (data.task.status === 'failed') {
                        throw new Error(data.task.error_message || '导出任务失败');
                    }
                    await wait(intervalMs);
                }
                throw new Error('导出耗时较长，请稍后在导出历史中下载');
            };

            const idempotencyKey = `editor-pdf-${resumeId}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            notify('导出任务已创建，正在生成 PDF...', 'info');
            fetch(createUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Idempotency-Key': idempotencyKey,
                },
                body: JSON.stringify({
                    type: 'pdf',
                    resume_ids: [resumeId],
                    include_optimized: false,
                }),
            })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok || !data?.success || !data?.task?.task_id) {
                    throw new Error(data?.message || '创建导出任务失败');
                }
                return pollTask(data.task.task_id);
            })
            .then((task) => {
                if (!task.download_url) {
                    throw new Error('导出已完成，但下载链接不可用');
                }
                notify('PDF 已生成，开始下载', 'success');
                window.location.href = task.download_url;
            })
            .catch((err) => {
                                notify('PDF 导出失败：' + (err?.message || err || '未知错误'), 'error');
            });
        },
