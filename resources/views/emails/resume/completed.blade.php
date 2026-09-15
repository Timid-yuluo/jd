<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>简历优化完成</title>
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
        .score-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .score-number {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-section h3 {
            margin-top: 0;
            color: #4b5563;
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
        .btn:hover {
            background: #2563eb;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .highlight {
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 恭喜！您的简历优化已完成</h1>
            <p>亲爱的 {{ $userName }}，您的简历已经成功优化</p>
        </div>

        @if($atsScore > 0)
        <div class="score-card">
            <div>ATS 兼容性评分</div>
            <div class="score-number">{{ $atsScore }}</div>
            <div>满分 100 分</div>
        </div>
        @endif

        <div class="info-section">
            <h3>📄 简历信息</h3>
            <p><strong>简历标题：</strong>{{ $resumeTitle }}</p>
            @if($targetJob)
            <p><strong>目标职位：</strong><span class="highlight">{{ $targetJob }}</span></p>
            @endif
            @if($targetCompany)
            <p><strong>目标公司：</strong>{{ $targetCompany }}</p>
            @endif
        </div>

        @if(!empty($stats))
        <div class="info-section">
            <h3>📊 优化统计</h3>
            <ul>
                @foreach($stats as $key => $value)
                <li>{{ $key }}: {{ $value }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $viewUrl }}" class="btn">查看优化后的简历</a>
        </div>

        <div class="info-section" style="background: #ecfdf5;">
            <h3>💡 后续建议</h3>
            <ul>
                <li>仔细查看优化后的内容，确保信息准确无误</li>
                <li>根据目标职位特点，针对性调整关键词</li>
                <li>可以使用我们的面试模拟功能准备面试</li>
                <li>定期更新简历，保持信息时效性</li>
            </ul>
        </div>

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
