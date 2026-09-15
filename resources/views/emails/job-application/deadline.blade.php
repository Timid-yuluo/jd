<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请截止提醒</title>
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
        .urgent {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .days-left {
            font-size: 48px;
            font-weight: bold;
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
            <h1>⏰ 申请截止提醒</h1>
            <p>亲爱的 {{ $userName }}，您的职位申请即将截止</p>
        </div>

        @if($daysUntilDeadline !== null)
        <div class="urgent">
            @if($daysUntilDeadline > 0)
            <div>还有</div>
            <div class="days-left">{{ $daysUntilDeadline }}</div>
            <div>天截止</div>
            @elseif($daysUntilDeadline == 0)
            <div class="days-left">今天截止！</div>
            @else
            <div class="days-left">已过期 {{ abs($daysUntilDeadline) }} 天</div>
            @endif
        </div>
        @endif

        <div class="info-section">
            <h3>📋 申请信息</h3>
            <p><strong>公司名称：</strong>{{ $company }}</p>
            <p><strong>应聘职位：</strong>{{ $position }}</p>
            <p><strong>截止日期：</strong>{{ $deadline ? $deadline->format('Y年m月d日') : '未设置' }}</p>
            <p><strong>当前状态：</strong>{{ $statusLabel }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $applicationUrl }}" class="btn">查看申请</a>
        </div>

        <div class="checklist">
            <h3>✅ 截止前检查清单</h3>
            <ul>
                <li>确认申请材料是否完整</li>
                <li>检查简历是否为最新版本</li>
                <li>确认求职信（如有需要）</li>
                <li>准备面试（如已进入面试阶段）</li>
                <li>关注后续通知</li>
            </ul>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
