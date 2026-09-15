<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>面试模拟开始</title>
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
        .tips h3 {
            margin-top: 0;
            color: #1e40af;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 面试模拟已开始</h1>
            <p>亲爱的 {{ $userName }}，您的面试模拟会话已启动</p>
        </div>

        <div class="info-section">
            <h3>📋 会话信息</h3>
            <p><strong>公司名称：</strong>{{ $company ?? '未指定' }}</p>
            <p><strong>应聘职位：</strong>{{ $position ?? '未指定' }}</p>
            <p><strong>面试类型：</strong>{{ $typeLabel }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $sessionUrl }}" class="btn">继续面试模拟</a>
        </div>

        <div class="tips">
            <h3>💡 面试小贴士</h3>
            <ul>
                <li>保持冷静，深呼吸有助于缓解紧张</li>
                <li>认真听题，确保理解问题后再回答</li>
                <li>结构化回答：先给出结论，再阐述理由</li>
                <li>适当举例，用具体经历支撑你的观点</li>
                <li>展现热情，让面试官感受到你的积极性</li>
            </ul>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
