<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简历导出完成</title>
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
        .success-box {
            background: #ecfdf5;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .file-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .file-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .note {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ 简历导出完成</h1>
            <p>亲爱的 {{ $userName }}，您的简历已成功导出</p>
        </div>

        <div class="success-box">
            <div class="file-icon">📄</div>
            <h3>导出成功！</h3>
            <p>您的简历已经准备好，可以直接下载使用</p>
        </div>

        <div class="file-info">
            <h3>📋 导出信息</h3>
            <p><strong>任务编号：</strong>#{{ $taskId }}</p>
            <p><strong>导出格式：</strong>{{ $formatLabel }}</p>
            @if($completedAt)
            <p><strong>完成时间：</strong>{{ $completedAt->format('Y-m-d H:i:s') }}</p>
            @endif
        </div>

        <div style="text-align: center;">
            @if($fileUrl)
            <a href="{{ $fileUrl }}" class="btn">下载简历</a>
            @endif
            <a href="{{ $tasksUrl }}" class="btn btn-secondary">查看所有导出</a>
        </div>

        <div class="note">
            <strong>💡 提示：</strong>如果邮件附件中没有收到文件，请点击上方"下载简历"按钮获取。文件将在服务器保存30天。
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
