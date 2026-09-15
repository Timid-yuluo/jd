<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>面试准备提醒</title>
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
            color: #f59e0b;
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
            background: #f59e0b;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .btn:hover {
            background: #d97706;
        }
        .checklist {
            background: #fffbeb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .checklist h3 {
            margin-top: 0;
            color: #92400e;
        }
        .checklist ul {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        .checklist li:before {
            content: "☐";
            position: absolute;
            left: 0;
            font-size: 20px;
            color: #f59e0b;
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
            <h1>⏰ 面试准备提醒</h1>
            <p>亲爱的 {{ $userName }}，您的面试模拟还未完成</p>
        </div>

        <div class="info-section">
            <h3>📋 会话信息</h3>
            <p><strong>公司名称：</strong>{{ $company ?? '未指定' }}</p>
            <p><strong>应聘职位：</strong>{{ $position ?? '未指定' }}</p>
            <p><strong>面试类型：</strong>{{ $typeLabel }}</p>
            <p><strong>当前进度：</strong>{{ $answeredCount }}/{{ $questionCount }} 题</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $sessionUrl }}" class="btn">继续面试</a>
        </div>

        <div class="checklist">
            <h3>✅ 面试前检查清单</h3>
            <ul>
                <li>回顾目标公司的业务和文化</li>
                <li>研究应聘职位的要求和职责</li>
                <li>准备 STAR 法则的案例</li>
                <li>准备 3-5 个要问面试官的问题</li>
                <li>检查设备和网络连接</li>
                <li>准备好简历和相关材料</li>
            </ul>
        </div>

        <div class="info-section" style="background: #ecfdf5;">
            <h3>💪 加油！</h3>
            <p>充分的准备是成功的关键。相信自己，你一定可以！</p>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
