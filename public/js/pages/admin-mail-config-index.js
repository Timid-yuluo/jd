// 切换 SMTP 配置显示
function toggleSmtpConfig() {
    const mailer = document.getElementById('mailer-select').value;
    const smtpConfig = document.getElementById('smtp-config');
    smtpConfig.style.display = mailer === 'smtp' ? 'block' : 'none';
}

// 快速配置 - QQ邮箱
function applyQQConfig() {
    document.getElementById('mailer-select').value = 'smtp';
    document.querySelector('input[name="host"]').value = 'smtp.qq.com';
    document.querySelector('input[name="port"]').value = '465';
    document.querySelector('select[name="encryption"]').value = 'ssl';
    toggleSmtpConfig();
    showConfigAlert('已填入QQ邮箱配置，请输入您的QQ邮箱地址和授权码', 'info');
}

// 快速配置 - 163邮箱
function apply163Config() {
    document.getElementById('mailer-select').value = 'smtp';
    document.querySelector('input[name="host"]').value = 'smtp.163.com';
    document.querySelector('input[name="port"]').value = '465';
    document.querySelector('select[name="encryption"]').value = 'ssl';
    toggleSmtpConfig();
    showConfigAlert('已填入163邮箱配置，请输入您的163邮箱地址和授权码', 'info');
}

// 快速配置 - Gmail
function applyGmailConfig() {
    document.getElementById('mailer-select').value = 'smtp';
    document.querySelector('input[name="host"]').value = 'smtp.gmail.com';
    document.querySelector('input[name="port"]').value = '587';
    document.querySelector('select[name="encryption"]').value = 'tls';
    toggleSmtpConfig();
    showConfigAlert('已填入Gmail配置，请输入您的Gmail地址和应用专用密码', 'info');
}

// 显示配置提示
function showConfigAlert(message, type) {
    const smtpConfig = document.getElementById('smtp-config');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        <i class="ti ti-info-circle me-1"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    smtpConfig.insertBefore(alertDiv, smtpConfig.firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
}

// 发送测试邮件
async function sendTestEmail() {
    const email = document.getElementById('test-email').value;
    const btn = document.getElementById('test-btn');
    const result = document.getElementById('test-result');

    if (!email) {
        result.innerHTML = '<div class="alert alert-warning">请输入测试邮箱地址</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>发送中...';
    result.innerHTML = '';

    try {
        const response = await fetch(document.querySelector('[data-route-admin-mail-config-test]')?.getAttribute('data-route-admin-mail-config-test') || '', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ test_email: email })
        });

        const data = await response.json();

        if (data.success) {
            result.innerHTML = `<div class="alert alert-success">
                <i class="ti ti-check me-1"></i>${data.message}
                <div class="small mt-1">发送耗时: ${data.duration}</div>
            </div>`;
        } else {
            result.innerHTML = `<div class="alert alert-danger">
                <i class="ti ti-x me-1"></i>${data.message}
            </div>`;
        }
    } catch (error) {
        result.innerHTML = `<div class="alert alert-danger">
            <i class="ti ti-x me-1"></i>请求失败: ${error.message}
        </div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send me-1"></i>发送测试邮件';
    }
}

// 重试发送邮件
async function retryEmail(logId) {
    const confirmed = typeof window.appConfirm === 'function'
        ? await window.appConfirm('确定要重新发送这封邮件吗？', { title: '重发确认', showCancel: true })
        : false;
    if (!confirmed) return;

    try {
        const response = await fetch(`/admin/mail-config/logs/${logId}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            window.appNotify(data.message);
            location.reload();
        } else {
            window.appNotify('重试失败: ' + data.message);
        }
    } catch (error) {
        window.appNotify('请求失败: ' + error.message);
    }
}

// 加载发送趋势图表
async function loadEmailTrend() {
    try {
        const response = await fetch(document.querySelector('[data-route-admin-mail-config-stats]')?.getAttribute('data-route-admin-mail-config-stats') || '');
        const data = await response.json();

        if (data.success) {
            const options = {
                series: [{
                    name: '发送量',
                    data: data.trend.map(item => item.count)
                }],
                chart: {
                    type: 'area',
                    height: 250,
                    toolbar: { show: false }
                },
                xaxis: {
                    categories: data.trend.map(item => item.date),
                    labels: { style: { fontSize: '12px' } }
                },
                yaxis: {
                    labels: { style: { fontSize: '12px' } }
                },
                colors: ['#206bc4'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.1
                    }
                },
                stroke: { curve: 'smooth', width: 2 },
                dataLabels: { enabled: false },
                grid: { strokeDashArray: 4 }
            };

            new ApexCharts(document.querySelector('#email-trend-chart'), options).render();
        }
    } catch (error) {
        console.error('Failed to load email trend:', error);
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    loadEmailTrend();
});
