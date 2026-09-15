<?php

declare(strict_types=1);

return [
    'recipients' => array_filter(
        array_map('trim', explode(',', (string) env('EXCEPTION_EMAIL_RECIPIENTS', '')))
    ),

    'skip_exceptions' => [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ],
];
