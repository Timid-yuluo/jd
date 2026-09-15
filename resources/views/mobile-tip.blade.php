<!DOCTYPE html>
<html lang="zh-CN" data-theme="auto">
<head>
    @include('partials.theme-init')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>建议使用电脑访问</title>
  <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
  <noscript><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet"></noscript>
  <link rel="stylesheet" href="{{ asset('css/pages/mobile-tip.css') }}">
  <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
</head>
<body>
  <div class="tip-card">
    <div class="icon-wrap">
      <i class="bi bi-display"></i>
    </div>
    <h1>建议使用电脑访问</h1>
    <p>当前页面面向桌面端设计，在电脑上可获得最佳体验：</p>

    <ul class="feature-list">
      <li>
        <i class="bi bi-check-circle-fill"></i>
        简历编辑器完整排版预览
      </li>
      <li>
        <i class="bi bi-check-circle-fill"></i>
        AI 优化与对比视图并排展示
      </li>
      <li>
        <i class="bi bi-check-circle-fill"></i>
        面试模拟与看板高效操作
      </li>
      <li>
        <i class="bi bi-check-circle-fill"></i>
        键盘快捷键与批量导出
      </li>
    </ul>

    <p>请在电脑浏览器中打开：</p>
    <div class="url-box">{{ $siteUrl }}</div>

    <form action="{{ route('mobile-tip.continue') }}" method="POST">
      @csrf
      @if(isset($redirect))
        <input type="hidden" name="redirect" value="{{ $redirect }}">
      @endif
      <button type="submit" class="btn-continue">
        仍要在手机上继续访问 →
      </button>
    </form>
  </div>
  <script src="{{ asset('js/theme-switcher.js') }}"></script>
</body>
</html>
