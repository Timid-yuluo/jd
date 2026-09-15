<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $documentTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/tabler/icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/user-resumes-print-pdf.css') }}?v={{ filemtime(public_path('css/pages/user-resumes-print-pdf.css')) }}">
    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">{!! \App\Support\ResumeRenderStyle::sharedCss() !!}</style>
</head>
<body data-document-title="{{ $documentTitle }}" data-register-download-url="{{ route('user.resumes.pdf-download', $items[0]['resume']) }}" @if($autoPrint) data-auto-print="1" @endif>
    <div class="print-toolbar" data-pdf-export-ignore="true">
        <div>
            <h1>打印版 PDF 导出</h1>
            <p>如果当前浏览器没有"另存为 PDF"，可直接点击"直接下载 PDF"。</p>
        </div>
        <div class="print-toolbar-actions">
            <div id="print-toolbar-status" class="print-toolbar-status"></div>
            <div class="print-toolbar-select">
                <label for="paper-size-select" class="print-toolbar-label">纸张</label>
                <select id="paper-size-select" class="print-toolbar-dropdown" data-paper-size-select>
                    <option value="a4" data-width="210" data-height="297" data-unit="mm" selected>A4 (210×297mm)</option>
                </select>
            </div>
            <button type="button" class="btn-secondary" data-print>打开打印窗口</button>
            <button type="button" data-action="download-pdf">直接下载 PDF</button>
        </div>
    </div>

    <div class="print-tip" data-pdf-export-ignore="true">
        如果系统没有自动弹出保存窗口，请在打印面板里选择"另存为 PDF"。分页以浏览器打印预览为准。
    </div>

    <div id="print-export-root" class="print-wrap">
        @foreach($items as $item)
            @php
                $resumeRenderStyle = \App\Support\ResumeRenderStyle::fromResume($item['resume']);
            @endphp
            <div class="print-page">
                <div class="print-page-inner resume-render-context theme-{{ $item['theme'] }}"
                    style="{{ $resumeRenderStyle['wrapper_style'] }}">
                    @if(($withWatermark ?? false) === true)
                        <div style="position: absolute; inset: 0; pointer-events: none; z-index: 2; overflow: hidden;">
                            <div style="position: absolute; top: 45%; left: -10%; right: -10%; text-align: center; transform: rotate(-28deg); color: rgba(59, 130, 246, 0.15); font-size: 44px; font-weight: 700; letter-spacing: 4px;">
                                职路通 免费版导出
                            </div>
                        </div>
                    @endif
                    @include('user.resumes.templates.' . $item['template'], ['theme' => $item['theme'], 'forceModules' => $item['modules']])
                </div>
            </div>
        @endforeach
    </div>

    <script src="{{ asset('vendor/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('js/pages/user-resumes-print-pdf.js') }}?v={{ filemtime(public_path('js/pages/user-resumes-print-pdf.js')) }}"></script>
</body>
</html>
