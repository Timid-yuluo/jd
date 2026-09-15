<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\ExceptionNotification;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class SendExceptionEmailListener
{
    private const THROTTLE_KEY_PREFIX = 'exception-email:';

    public function handle(MessageLogged $event): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (! in_array($event->level, ['error', 'critical', 'emergency'], true)) {
            return;
        }

        $exception = $event->context['exception'] ?? null;

        if (! $exception instanceof Throwable) {
            return;
        }

        if ($this->shouldSkip($exception)) {
            return;
        }

        $exceptionClass = get_class($exception);
        $throttleKey = self::THROTTLE_KEY_PREFIX.$exceptionClass;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        RateLimiter::hit($throttleKey, 300);

        $recipients = config('exception-email.recipients', []);

        if (empty($recipients)) {
            return;
        }

        Notification::route('mail', $recipients)
            ->notify(new ExceptionNotification($exception, [
                'url' => url()->current() ?? 'CLI',
                'method' => request()->method() ?? 'N/A',
                'ip' => request()->ip() ?? 'N/A',
                'user_id' => auth()->id() ?? 'guest',
            ]));
    }

    private function shouldSkip(Throwable $exception): bool
    {
        $skipClasses = config('exception-email.skip_exceptions', [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ]);

        foreach ($skipClasses as $skipClass) {
            if ($exception instanceof $skipClass) {
                return true;
            }
        }

        return false;
    }
}
