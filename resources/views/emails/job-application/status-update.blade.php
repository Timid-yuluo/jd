<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请状态更新</title>
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
            color: #3b82f6;
            margin: 0 0 10px 0;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status-change {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            font-size: 18px;
        }
        .status-old {
            color: #9ca3af;
            text-decoration: line-through;
        }
        .status-arrow {
            margin: 0 15px;
            color: #3b82f6;
            font-size: 24px;
        }
        .status-new {
            color: #10b981;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .success-box {
            background: #ecfdf5;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .warning-box {
            background: #fffbeb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 申请状态更新</h1>
            <p>亲爱的 {{ $userName }}，您的职位申请状态有变化</p>
        </div>

        <div class="info-section">
            <h3>📋 申请信息</h3>
            <p><strong>公司名称：</strong>{{ $company }}</p>
            <p><strong>应聘职位：</strong>{{ $position }}</p>
        </div>

        <div class="status-change">
            <span class="status-old">{{ $statusLabel }}</span>
            <span class="status-arrow">→</span>
            <span class="status-new">{{ $statusLabel }}</span>
        </div>

        @if($status === 'interview')
        <div class="success-box">
            <h3>🎉 恭喜进入面试阶段！</h3>
            <p>您可以开始使用我们的面试模拟功能来准备面试</p>
        </div>
        @elseif($status === 'offer')
        <div class="success-box">
            <h3>🎊 恭喜获得 Offer！</h3>
            <p>您的努力得到了回报，祝您前程似锦！</p>
        </div>
        @elseif($status === 'rejected')
        <div class="warning-box">
            <h3>💪 不要气馁</h3>
            <p>每一次尝试都是成长的机会，继续加油！</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $applicationUrl }}" class="btn">查看详情</a>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
