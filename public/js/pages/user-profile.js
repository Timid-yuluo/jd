// 活跃度图表
const activityOptions = {
        series: [{
            name: '登录',
            data: JSON.parse(decodeURIComponent(document.querySelector('[data-json-activitystats-login]')?.getAttribute('data-json-activitystats-login') || 'null'))
        }, {
            name: '简历',
            data: JSON.parse(decodeURIComponent(document.querySelector('[data-json-activitystats-resume]')?.getAttribute('data-json-activitystats-resume') || 'null'))
        }, {
            name: '面试',
            data: JSON.parse(decodeURIComponent(document.querySelector('[data-json-activitystats-interview]')?.getAttribute('data-json-activitystats-interview') || 'null'))
        }],
        chart: {
            type: 'bar',
            height: 150,
            stacked: true,
            toolbar: { show: false },
            animations: { enabled: false }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 2
            }
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        xaxis: {
            categories: JSON.parse(decodeURIComponent(document.querySelector('[data-json-activitystats-labels]')?.getAttribute('data-json-activitystats-labels') || 'null')),
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { show: false },
        grid: { show: false },
        colors: ['#10B981', '#0EA5E9', '#F59E0B'],
        tooltip: {
            theme: 'light',
            y: {
                formatter: function(val) {
                    return val + ' 次';
                }
            }
        }
    };

const activityChartElement = document.querySelector('#activityChart');
if (activityChartElement && typeof ApexCharts !== 'undefined') {
    const activityChart = new ApexCharts(activityChartElement, activityOptions);
    activityChart.render();
}

    // Toast 提示函数
    function showToast(message, type = 'success') {
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
        const icon = type === 'success' ? 'ti-check' : type === 'error' ? 'ti-x' : 'ti-alert-triangle';
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center ${bgClass} text-white border-0 position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="ti ${icon} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // AJAX 提交基本资料表单
const profileUpdateRoute = document.querySelector('[data-route-user-profile-update]')?.getAttribute('data-route-user-profile-update') || '';
document.querySelector(`form[action="${profileUpdateRoute}"]`)?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>保存中...';
        
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json().catch(() => ({ success: true, redirect: true })))
        .then(data => {
            if (data.success || data.redirect) {
                showToast(data.message || '个人资料已更新', 'success');
                if (data.email_changed) {
                    window.location.reload();
                    return;
                }
                // 更新页面上的名字显示
                const nameInput = form.querySelector('input[name="name"]');
                if (nameInput) {
                    document.querySelector('h3').textContent = nameInput.value;
                }
                const emailInput = form.querySelector('input[name="email"]');
                if (emailInput) {
                    const emailText = document.querySelector('.card .card-body .text-secondary');
                    if (emailText) {
                        emailText.textContent = emailInput.value;
                    }
                }
            } else {
                showToast(data.message || '保存失败', 'error');
            }
        })
        .catch(() => {
            form.submit(); // 降级为普通提交
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // AJAX 提交密码修改表单
const passwordUpdateRoute = document.querySelector('[data-route-user-profile-password-update]')?.getAttribute('data-route-user-profile-password-update') || '';
document.querySelector(`form[action="${passwordUpdateRoute}"]`)?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>更新中...';
        
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json().catch(() => ({ success: true })))
        .then(data => {
            if (data.success) {
                showToast('密码已更新', 'success');
                form.reset();
                updatePasswordStrength('');
            } else {
                showToast(data.message || '更新失败', 'error');
            }
        })
        .catch(() => {
            form.submit();
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // 显示/隐藏密码
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(inputId + 'Icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('ti-eye', 'ti-eye-off');
        } else {
            input.type = 'password';
            icon.classList.replace('ti-eye-off', 'ti-eye');
        }
    }

    // 密码强度检测
    function updatePasswordStrength(password) {
        const bar = document.getElementById('passwordStrengthBar');
        const text = document.getElementById('passwordStrengthText');
        
        if (!password) {
            bar.style.width = '0%';
            bar.className = 'progress-bar';
            text.textContent = '密码强度：未输入';
            return;
        }
        
        let strength = 0;
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
        
        bar.style.width = strength + '%';
        bar.className = 'progress-bar ' + (strength < 40 ? 'bg-danger' : strength < 70 ? 'bg-warning' : 'bg-success');
        text.textContent = '密码强度：' + (strength < 40 ? '弱' : strength < 70 ? '中' : '强');
    }

document.getElementById('newPassword')?.addEventListener('input', function() {
    updatePasswordStrength(this.value);
});

document.querySelectorAll('.profile-password-toggle').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const inputId = trigger.getAttribute('data-password-target') || '';
        if (inputId) {
            togglePassword(inputId);
        }
    });
});

document.getElementById('triggerProfileExport')?.addEventListener('click', () => {
    const exportButton = document.querySelector('#profileExportForm button[type="submit"]');
    exportButton?.click();
});

    // 注销账号相关逻辑
function toggleFeedback(value) {
    const container = document.getElementById('feedbackContainer');
    if (!container) {
        return;
    }
    container.style.display = value ? 'block' : 'none';
}

document.getElementById('deletionReason')?.addEventListener('change', function () {
    toggleFeedback(this.value);
});

    // 注销确认勾选控制
    const confirmUnderstand = document.getElementById('confirmUnderstand');
    const confirmDataLoss = document.getElementById('confirmDataLoss');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    function updateDeleteButton() {
        if (confirmDeleteBtn) {
            confirmDeleteBtn.disabled = !(confirmUnderstand?.checked && confirmDataLoss?.checked);
        }
    }

    confirmUnderstand?.addEventListener('change', updateDeleteButton);
    confirmDataLoss?.addEventListener('change', updateDeleteButton);

    // 防止误关闭注销弹窗
    const deleteModal = document.getElementById('deleteAccountModal');
    let allowDeleteModalClose = false;
    deleteModal?.addEventListener('hide.bs.modal', async function(e) {
        if (allowDeleteModalClose) {
            return;
        }
        const form = document.getElementById('deleteAccountForm');
        const hasInput = form?.querySelector('input[name="password"]')?.value || 
                        form?.querySelector('select[name="deletion_reason"]')?.value;
        if (!hasInput) {
            return;
        }
        e.preventDefault();
        const appConfirm = window.appConfirm || null;
        const confirmed = typeof appConfirm === 'function'
            ? await appConfirm('您已填写注销信息，确定要取消吗？', { title: '取消确认', showCancel: true })
            : false;
        if (!confirmed) {
            e.preventDefault();
            return;
        }
        allowDeleteModalClose = true;
        const modal = window.bootstrap?.Modal?.getOrCreateInstance(deleteModal);
        modal?.hide();
        window.setTimeout(() => { allowDeleteModalClose = false; }, 0);
    });
