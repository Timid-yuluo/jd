<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简历导出已启动</title>
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
        .progress-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .progress-bar {
            background: #e5e7eb;
            border-radius: 10px;
            height: 20px;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
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
            <h1>🚀 简历导出已启动</h1>
            <p>亲爱的 {{ $userName }}，您的简历导出任务已开始处理</p>
        </div>

        <div class="progress-section">
            <div>任务 #{{ $taskId }}</div>
            <div>导出格式：{{ $formatLabel }}</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $progress }}%"></div>
            </div>
            <div>{{ $progress }}% 完成</div>
        </div>

        <div style="text-align: center; color: #6b7280;">
            <p>导出完成后，我们会立即发送邮件通知您</p>
            <p>通常只需几秒钟，请耐心等待...</p>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
