<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>访问验证 - 简历分享</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; line-height: 1.6; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .lock-card { background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.1); padding: 48px 40px; width: 100%; max-width: 400px; text-align: center; }
        .lock-icon { width: 64px; height: 64px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .lock-icon svg { width: 28px; height: 28px; color: #4c6fff; }
        .lock-card h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; }
        .lock-card p { color: #6b7280; font-size: .9rem; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; text-align: left; }
        .form-group label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: 6px; color: #374151; }
        .form-group input { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; transition: border-color .2s; }
        .form-group input:focus { outline: none; border-color: #4c6fff; box-shadow: 0 0 0 3px rgba(76,111,255,.15); }
        .form-group .error { color: #ef4444; font-size: .8rem; margin-top: 4px; }
        .btn { width: 100%; padding: 11px; background: #4c6fff; color: #fff; border: none; border-radius: 8px; font-size: .95rem; font-weight: 600; cursor: pointer; transition: background .2s; }
        .btn:hover { background: #3b5ce4; }
    </style>
</head>
<body>
    <div class="lock-card">
        <div class="lock-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2>该简历已设置访问密码</h2>
        <p>请输入密码查看简历内容</p>
        <form action="{{ route('share.verify-password', $token) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="password">访问密码</label>
                <input type="password" name="password" id="password" placeholder="请输入密码" required autofocus>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn">验证并查看</button>
        </form>
    </div>
</body>
</html>
