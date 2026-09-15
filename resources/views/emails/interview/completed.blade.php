<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>面试模拟报告</title>
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
        .score-section {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .score-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            min-width: 100px;
        }
        .score-number {
            font-size: 32px;
            font-weight: bold;
            color: #3b82f6;
        }
        .score-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
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
        .strength {
            background: #ecfdf5;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #10b981;
        }
        .improvement {
            background: #fffbeb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 面试模拟完成报告</h1>
            <p>亲爱的 {{ $userName }}，您的面试模拟已经结束</p>
        </div>

        <div class="info-section">
            <h3>🎯 面试信息</h3>
            <p><strong>公司名称：</strong>{{ $company ?? '未指定' }}</p>
            <p><strong>应聘职位：</strong>{{ $position ?? '未指定' }}</p>
            <p><strong>面试类型：</strong>{{ $typeLabel }}</p>
        </div>

        <div class="score-section">
            <div class="score-box">
                <div class="score-number">{{ $overallScore }}</div>
                <div class="score-label">综合得分</div>
            </div>
            <div class="score-box">
                <div class="score-number">{{ $answeredCount }}/{{ $questionCount }}</div>
                <div class="score-label">答题进度</div>
            </div>
        </div>

        @if($overallScore >= 80)
        <div class="strength">
            <strong>🌟 表现优秀！</strong> 您的面试表现非常出色，继续保持！
        </div>
        @elseif($overallScore >= 60)
        <div class="improvement">
            <strong>💪 表现良好</strong> 有进步空间，建议针对薄弱环节加强练习。
        </div>
        @else
        <div class="improvement">
            <strong>📚 需要加强</strong> 建议系统学习相关知识，多进行模拟练习。
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ $sessionUrl }}" class="btn">查看详细报告</a>
        </div>

        @if(!empty($reportData) && isset($reportData['strengths']))
        <div class="info-section" style="background: #ecfdf5;">
            <h3>✅ 优势领域</h3>
            <ul>
                @foreach($reportData['strengths'] as $strength)
                <li>{{ $strength }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($reportData) && isset($reportData['weaknesses']))
        <div class="info-section" style="background: #fffbeb;">
            <h3>📈 改进建议</h3>
            <ul>
                @foreach($reportData['weaknesses'] as $weakness)
                <li>{{ $weakness }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="footer">
            <p>此邮件由系统自动发送，请勿直接回复</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} 保留所有权利</p>
        </div>
    </div>
</body>
</html>
