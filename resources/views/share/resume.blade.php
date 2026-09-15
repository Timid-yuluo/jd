<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $resume->title }} - 简历分享</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; line-height: 1.6; background: #f1f5f9; }
        .share-page { display: flex; justify-content: center; padding: 24px 16px; }
        .resume-wrapper { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); overflow: hidden; }
        .share-footer { text-align: center; color: #9ca3af; font-size: 12px; padding: 20px 16px 32px; }
        @media print { body { background: #fff; } .share-page { padding: 0; } .resume-wrapper { box-shadow: none; border-radius: 0; } .share-footer { display: none; } }
    </style>
</head>
<body>
    <div class="share-page">
        <div class="resume-wrapper">
            @include('user.resumes.templates.' . ($resume->template ?: 'classic'), ['theme' => $resume->theme ?? 'blue'])
        </div>
    </div>
    <div class="share-footer">
        由 {{ config('app.name') }} 生成 · {{ now()->format('Y-m-d') }}
    </div>
</body>
</html>
