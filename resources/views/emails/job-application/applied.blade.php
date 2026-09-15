<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>职位申请确认</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #10b981;
            margin: 0 0 10px 0;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .tips {
            background: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .timeline {
            margin: 20px 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .timeline-dot {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 15px;
        }
        .timeline-text {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ 申请已记录</h1>
            <p>亲爱的 {{ $userName }}，您的职位申请已成功记录</p>
        </div>

        <div class="info-section">
            <h3>📋 申请信息</h3>
            <p><strong>公司名称：</strong>{{ $company }}</p>
            <p><strong>应聘职位：</strong>{{ $position }}</p>
            <p><strong>当前状态：</strong><span class="status-badge">{{ $statusLabel }}</span></p>
            @if($deadline)
            <p><strong>截止日期：</strong>{{ $deadline->format('Y年m月d日') }}</p>
            @endif
            @if($channel)
            <p><strong>申请渠道：</strong>{{ $channel }}</p>
            @endif
        </div>

        <div class="timeline">
            <h3>📝 申请进度</h3>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-text">
                    <strong>已申请</strong><br>
                    <small>{{ now()->format('Y-m-d H:i') }}</small>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot" style="background: #d1d5db;"></div>
                <div class="timeline-text" style="color: #9ca3af;">
                    <strong>简历筛选</strong><br>
                    <small>等待中...</small>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-dot" style="background: #d1d5db;"></div>
                <div class="timeline-text" style="color: #9ca3af;">
                    <strong>面试邀请</strong><br>
                    <small>期待中...</small>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $applicationUrl }}" class="btn">管理申请</a>
        </div>

        <div class="tips">
            <h3>💡 后续建议</h3>
            <ul>
                <li>关注申请状态变化，及时跟进</li>
                <li>准备面试，可以使用我们的面试模拟功能</li>
                <li>继续投递其他感兴趣的职位</li>
                <li>定期更新简历，提高竞争力</li>
            </ul>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
