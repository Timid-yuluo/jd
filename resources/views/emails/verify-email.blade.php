<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>验证您的邮箱</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 40px;
            margin: 20px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #206bc4;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .content {
            color: #555;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            background: #206bc4;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background: #1a5aa3;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-top: 30px;
        }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 30px 0;
        }
        .info-box {
            background: #f3f4f6;
            border-left: 4px solid #206bc4;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning {
            color: #d97706;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>📧 验证您的邮箱</h1>
                <p style="color: #666;">{{ config('app.name') }}</p>
            </div>

            <div class="content">
                <p>您好，<strong>{{ $user->name }}</strong>，</p>

                <p>感谢您注册 {{ config('app.name') }}！请点击下方按钮验证您的邮箱地址：</p>

                <div style="text-align: center;">
                    <a href="{{ $verificationUrl }}" class="button">验证邮箱地址</a>
                </div>

                <p style="text-align: center; color: #666; font-size: 14px;">
                    或复制以下链接到浏览器打开：<br>
                    <code style="word-break: break-all; color: #206bc4;">{{ $verificationUrl }}</code>
                </p>

                <div class="info-box">
                    <p class="warning">
                        <strong>⏰ 链接有效期：</strong>此验证链接将在 {{ $expiresAt ?? '24小时后' }} 过期。
                    </p>
                </div>

                <hr class="divider">

                <p style="font-size: 14px; color: #888;">
                    如果您没有注册 {{ config('app.name') }}，请忽略此邮件。<br>
                    如有任何问题，请联系我们的客服团队。
                </p>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. 保留所有权利。</p>
            </div>
        </div>
    </div>
</body>
</html>
