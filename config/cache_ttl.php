<?php

return [

    'ttl' => [
        'realtime' => (int) env('CACHE_TTL_REALTIME', 30),

        'short' => (int) env('CACHE_TTL_SHORT', 60),

        'admin_stats' => (int) env('CACHE_TTL_ADMIN_STATS', 120),

        'user_dashboard' => (int) env('CACHE_TTL_USER_DASHBOARD', 180),

        'standard' => (int) env('CACHE_TTL_STANDARD', 300),

        'daily_stats' => (int) env('CACHE_TTL_DAILY_STATS', 600),

        'export_processing' => (int) env('CACHE_TTL_EXPORT_PROCESSING', 1800),

        'long' => (int) env('CACHE_TTL_LONG', 3600),
    ],

];
