<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简历导出失败</title>
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
            color: #ef4444;
            margin: 0 0 10px 0;
        }
        .error-box {
            background: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #fecaca;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>❌ 简历导出失败</h1>
            <p>亲爱的 {{ $userName }}，很抱歉您的简历导出遇到了问题</p>
        </div>

        <div class="error-box">
            <h3>导出任务失败</h3>
            <p>任务编号：#{{ $taskId }}</p>
            <p>导出格式：{{ $formatLabel }}</p>
            @if($errorMessage)
            <p style="color: #dc2626; margin-top: 15px;">
                <strong>错误信息：</strong>{{ $errorMessage }}
            </p>
            @endif
        </div>

        <div style="text-align: center;">
            <a href="{{ $tasksUrl }}" class="btn">重新导出</a>
        </div>

        <div style="text-align: center; color: #6b7280; margin-top: 20px;">
            <p>如果问题持续存在，请联系客服获取帮助</p>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
