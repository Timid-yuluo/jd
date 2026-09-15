<?php

declare(strict_types=1);

return [
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'amap' => [
        'key' => env('AMAP_KEY', ''),
    ],
];
