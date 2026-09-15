<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\ViewServiceProvider;

return [
    AppServiceProvider::class,
    AiServiceProvider::class,
    RateLimitServiceProvider::class,
    ViewServiceProvider::class,
];
