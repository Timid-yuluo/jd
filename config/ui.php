<?php

return [

    'pagination' => [
        'admin_list' => (int) env('UI_PAGINATION_ADMIN_LIST', 15),
        'admin_table' => (int) env('UI_PAGINATION_ADMIN_TABLE', 20),
        'user_grid' => (int) env('UI_PAGINATION_USER_GRID', 9),
        'user_list' => (int) env('UI_PAGINATION_USER_LIST', 10),
        'kanban' => (int) env('UI_PAGINATION_KANBAN', 20),
    ],

    'limit' => [
        'dashboard_recent' => (int) env('UI_LIMIT_DASHBOARD_RECENT', 5),
        'sidebar_hot' => (int) env('UI_LIMIT_SIDEBAR_HOT', 8),
        'related_items' => (int) env('UI_LIMIT_RELATED_ITEMS', 10),
        'notification_preview' => (int) env('UI_LIMIT_NOTIFICATION_PREVIEW', 20),
        'session_history' => (int) env('UI_LIMIT_SESSION_HISTORY', 30),
        'notification_dropdown' => (int) env('UI_LIMIT_NOTIFICATION_DROPDOWN', 5),
        'admin_audit' => (int) env('UI_LIMIT_ADMIN_AUDIT', 20),
        'login_history' => (int) env('UI_LIMIT_LOGIN_HISTORY', 100),
        'export_batch' => (int) env('UI_LIMIT_EXPORT_BATCH', 50),
        'mail_test' => (int) env('UI_LIMIT_MAIL_TEST', 10),
        'data_preview' => (int) env('UI_LIMIT_DATA_PREVIEW', 5),
        'ai_config_history' => (int) env('UI_LIMIT_AI_CONFIG_HISTORY', 5),
        'template_preview' => (int) env('UI_LIMIT_TEMPLATE_PREVIEW', 2),
        'template_list' => (int) env('UI_LIMIT_TEMPLATE_LIST', 20),
        'template_search' => (int) env('UI_LIMIT_TEMPLATE_SEARCH', 30),
        'template_hot' => (int) env('UI_LIMIT_TEMPLATE_HOT', 12),
        'interview_recent' => (int) env('UI_LIMIT_INTERVIEW_RECENT', 3),
        'interview_weakness' => (int) env('UI_LIMIT_INTERVIEW_WEAKNESS', 2),
        'optimize_gain' => (int) env('UI_LIMIT_OPTIMIZE_GAIN', 200),
        'oauth_diag_recent' => (int) env('UI_LIMIT_OAUTH_DIAG_RECENT', 5),
        'oauth_diag_list' => (int) env('UI_LIMIT_OAUTH_DIAG_LIST', 20),
        'login_history_list' => (int) env('UI_LIMIT_LOGIN_HISTORY_LIST', 10),
        'kanban_sidebar' => (int) env('UI_LIMIT_KANBAN_SIDEBAR', 8),
        'template_content' => (int) env('UI_LIMIT_TEMPLATE_CONTENT', 5000),
    ],

    'ats_threshold' => [
        'high' => (int) env('UI_ATS_THRESHOLD_HIGH', 80),
        'medium_low' => (int) env('UI_ATS_THRESHOLD_MEDIUM_LOW', 50),
    ],

    'upload' => [
        'image_max_kb' => (int) env('UI_UPLOAD_IMAGE_MAX_KB', 2048),
        'document_max_kb' => (int) env('UI_UPLOAD_DOCUMENT_MAX_KB', 10240),
    ],

    'chart' => [
        'default_days' => (int) env('UI_CHART_DEFAULT_DAYS', 30),
        'recent_days' => (int) env('UI_CHART_RECENT_DAYS', 7),
    ],

];
