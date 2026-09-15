<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    /* 主题初始化 - 内联执行以消除 FOUC */
    (function () {
        var storedTheme = null;
        try {
            storedTheme = window.localStorage.getItem('theme-preference');
        } catch (e) {
            storedTheme = null;
        }

        var preference = storedTheme || 'auto';
        var resolved = preference === 'auto'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : (preference === 'dark' ? 'dark' : 'light');

        document.documentElement.setAttribute('data-theme', preference);
        document.documentElement.setAttribute('data-bs-theme', resolved);

        /* 在 CSS 加载前立即设置背景色，防止白色闪烁 */
        var bgColor = resolved === 'dark' ? '#0B1120' : '#FFFFFF';
        document.documentElement.style.backgroundColor = bgColor;

        /* 立即设置 body 背景色（不等待 DOMContentLoaded） */
        var body = document.body;
        if (body) {
            body.style.backgroundColor = bgColor;
        } else {
            /* body 还不存在，轮询等待 */
            var timer = setInterval(function () {
                var b = document.body;
                if (b) {
                    b.style.backgroundColor = bgColor;
                    clearInterval(timer);
                }
            }, 1);
            /* 安全兜底：最多尝试 100ms */
            setTimeout(function () { clearInterval(timer); }, 100);
        }
    })();
</script>
<style id="theme-fouc-fix" nonce="{{ request()->attributes->get('csp_nonce', '') }}">
    /* 最高优先级 FOUC 修复：在 dark-mode.css 加载前生效 */
    html[data-bs-theme="dark"],
    html[data-bs-theme="dark"] body {
        background-color: #0B1120 !important;
    }
</style>
