<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Resume Export Retry
    |--------------------------------------------------------------------------
    */
    'max_attempts' => (int) env('RESUME_EXPORT_MAX_ATTEMPTS', 2),

    /*
    |--------------------------------------------------------------------------
    | Resume Export Retention (Days)
    |--------------------------------------------------------------------------
    */
    'retention_days' => (int) env('RESUME_EXPORT_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    */
    'queue' => (string) env('RESUME_EXPORT_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Export Working Directory
    |--------------------------------------------------------------------------
    */
    'working_directory' => (string) env('RESUME_EXPORT_WORKING_DIRECTORY', ''),

    /*
    | PDF Export (Browsershot)
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'driver' => (string) env('RESUME_EXPORT_PDF_DRIVER', 'browsershot'),

        // 中文字体文件路径（仅支持 ttf/ttc），为空则按内置候选路径自动查找
        'cjk_font_path' => (string) env('RESUME_EXPORT_PDF_CJK_FONT_PATH', ''),

        // 相同简历版本复用已完成 PDF 的缓存时长（分钟）
        'reuse_ttl_minutes' => (int) env('RESUME_EXPORT_PDF_REUSE_TTL_MINUTES', 10080),

        // 正在导出中的任务签名占位时长（分钟）
        'processing_ttl_minutes' => (int) env('RESUME_EXPORT_PDF_PROCESSING_TTL_MINUTES', 15),

        // Node.js 可执行文件路径（可选，Browsershot 自动检测）
        'node_binary' => (string) env('RESUME_EXPORT_PDF_NODE_BINARY', ''),

        // npm 可执行文件路径（可选，Browsershot 自动检测）
        'npm_binary' => (string) env('RESUME_EXPORT_PDF_NPM_BINARY', ''),

        // Chromium/Chrome 可执行文件路径（可选，Browsershot 自动下载或检测）
        'chromium_path' => (string) env('RESUME_EXPORT_PDF_CHROMIUM_PATH', ''),
    ],
];
