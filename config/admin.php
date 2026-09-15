<?php

declare(strict_types=1);

return [
    'menu' => [
        [
            'title' => '控制台',
            'route' => 'admin.dashboard',
            'icon' => 'ti ti-dashboard',
            'permission' => 'access admin',
        ],
        [
            'title' => '核心业务',
            'icon' => 'ti ti-briefcase',
            'permission' => 'access admin',
            'children' => [
                ['title' => '用户管理', 'route' => 'admin.users.index', 'icon' => 'ti ti-users'],
                ['title' => '简历管理', 'route' => 'admin.resumes.index', 'icon' => 'ti ti-file-text'],
                ['title' => '面试记录', 'route' => 'admin.interviews.index', 'icon' => 'ti ti-message-chatbot'],
                ['title' => '投递看板', 'route' => 'admin.job-applications.index', 'icon' => 'ti ti-layout-kanban'],
                ['title' => '意见反馈', 'route' => 'admin.feedbacks.index', 'icon' => 'ti ti-message-circle'],
                ['title' => '外部招聘池', 'route' => 'admin.external-recruitments.index', 'icon' => 'ti ti-briefcase-2'],
                ['title' => '推荐效果看板', 'route' => 'admin.recommendation-insights.index', 'icon' => 'ti ti-chart-dots'],
            ],
        ],
        [
            'title' => 'AI 服务',
            'icon' => 'ti ti-robot',
            'permission' => 'ai-config.view',
            'children' => [
                ['title' => 'AI 配置', 'route' => 'admin.ai-config.index', 'icon' => 'ti ti-settings-cog'],
                ['title' => 'Prompt 模板', 'route' => 'admin.ai-prompts.index', 'icon' => 'ti ti-template'],
                ['title' => '用量分析', 'route' => 'admin.ai-analytics.index', 'icon' => 'ti ti-chart-bar'],
                ['title' => 'AI 用量日志', 'route' => 'admin.usage-logs.index', 'icon' => 'ti ti-api'],
            ],
        ],
        [
            'title' => '会员商业',
            'icon' => 'ti ti-crown',
            'permission' => 'plans.manage',
            'children' => [
                ['title' => '套餐管理', 'route' => 'admin.plans.index', 'icon' => 'ti ti-package'],
                ['title' => '次卡商品', 'route' => 'admin.credit-packs.index', 'icon' => 'ti ti-credit-card'],
                ['title' => '订阅订单', 'route' => 'admin.subscriptions.orders', 'icon' => 'ti ti-receipt'],
                ['title' => '次卡订单', 'route' => 'admin.credit-packs.orders', 'icon' => 'ti ti-receipt-2'],
            ],
        ],
        [
            'title' => '内容运营',
            'icon' => 'ti ti-articles',
            'permission' => 'help-center.view',
            'children' => [
                ['title' => '帮助文章', 'route' => 'admin.help-articles.index', 'icon' => 'ti ti-help-hexagon'],
                ['title' => '帮助分类', 'route' => 'admin.help-categories.index', 'icon' => 'ti ti-category'],
                ['title' => '通知中心', 'route' => 'admin.notifications.index', 'icon' => 'ti ti-bell'],
                ['title' => '平台事件', 'route' => 'admin.events.index', 'icon' => 'ti ti-speakerphone'],
                ['title' => '模板源管理', 'route' => 'admin.template-sources.index', 'icon' => 'ti ti-layout-list'],
                ['title' => '校招赛道', 'route' => 'admin.career-tracks.index', 'icon' => 'ti ti-compass'],
            ],
        ],
        [
            'title' => '系统管理',
            'icon' => 'ti ti-settings',
            'permission' => 'site-settings.view',
            'children' => [
                ['title' => '网站设置', 'route' => 'admin.site-settings.index', 'icon' => 'ti ti-world'],
                ['title' => '角色权限', 'route' => 'admin.roles-permissions.index', 'icon' => 'ti ti-shield-lock'],
                ['title' => '邮件配置', 'route' => 'admin.mail-config.index', 'icon' => 'ti ti-mail-cog'],
                ['title' => '文件管理', 'route' => 'admin.file-manager.index', 'icon' => 'ti ti-folder'],
                ['title' => '数据导出', 'route' => 'admin.data-export.index', 'icon' => 'ti ti-download'],
                ['title' => '系统审计', 'route' => 'admin.system-setting-audits.index', 'icon' => 'ti ti-file-search'],
            ],
        ],
        [
            'title' => '运维监控',
            'icon' => 'ti ti-heartbeat',
            'permission' => 'system-ops.view',
            'children' => [
                ['title' => '系统运维', 'route' => 'admin.system-ops.index', 'icon' => 'ti ti-tool'],
                ['title' => '访问统计', 'route' => 'admin.visitor-analytics.index', 'icon' => 'ti ti-chart-bar'],
                ['title' => '定时任务', 'route' => 'admin.schedule.index', 'icon' => 'ti ti-clock-hour-4'],
                ['title' => '操作日志', 'route' => 'admin.action-logs.index', 'icon' => 'ti ti-clipboard-list'],
                ['title' => '邮件日志', 'route' => 'admin.email-logs.index', 'icon' => 'ti ti-mail-check'],
                ['title' => '访问封禁', 'route' => 'admin.access-bans.index', 'icon' => 'ti ti-shield-x'],
                ['title' => '访问日志', 'route' => 'admin.access-bans.logs', 'icon' => 'ti ti-eye'],
            ],
        ],
    ],
    'notifications' => [],

    'security' => [
        'ip_whitelist' => env('ADMIN_IP_WHITELIST', '') !== ''
            ? array_map('trim', explode(',', env('ADMIN_IP_WHITELIST', '')))
            : [],

        'ip_whitelist_bypass_auto_ban' => (bool) env('ADMIN_IP_WHITELIST_BYPASS_AUTO_BAN', true),

        'auto_ban_enabled' => (bool) env('ADMIN_AUTO_BAN_ENABLED', true),

        'session_device_binding' => (bool) env('ADMIN_SESSION_DEVICE_BINDING', true),

        'session_timeout_minutes' => (int) env('ADMIN_SESSION_TIMEOUT_MINUTES', 120),

        'log_successful_access' => (bool) env('ADMIN_LOG_SUCCESSFUL_ACCESS', true),
    ],
];
