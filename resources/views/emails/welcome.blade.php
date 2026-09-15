<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎加入</title>
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
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid #3b82f6;
            color: #3b82f6;
        }
        .btn-outline:hover {
            background: #3b82f6;
            color: white;
        }
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .feature h3 {
            margin-top: 0;
            color: #4b5563;
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 10px;
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
            <h1>🎉 欢迎加入 {{ config('app.name') }}！</h1>
            <p>亲爱的 {{ $userName }}，感谢您注册使用我们的服务</p>
        </div>

        <p style="text-align: center; font-size: 16px;">
            我们致力于帮助您打造完美简历、轻松应对面试，顺利找到理想工作！
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" class="btn">进入控制台</a>
        </div>

        <div class="feature">
            <div class="feature-icon">📝</div>
            <h3>智能简历优化</h3>
            <p>AI驱动的简历优化工具，帮助您提升简历质量，提高通过率</p>
            <a href="{{ $createResumeUrl }}" class="btn btn-outline btn-sm">创建简历</a>
        </div>

        <div class="feature">
            <div class="feature-icon">🎯</div>
            <h3>模拟面试练习</h3>
            <p>多种面试类型模拟，实时反馈，帮您提升面试表现</p>
            <a href="{{ $startInterviewUrl }}" class="btn btn-outline btn-sm">开始练习</a>
        </div>

        <div class="feature">
            <div class="feature-icon">📊</div>
            <h3>求职进度管理</h3>
            <p>轻松管理所有职位申请，追踪进度，不再错过任何机会</p>
        </div>

        <div class="footer">
            <p>如果您有任何问题，请随时联系我们的客服团队</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
