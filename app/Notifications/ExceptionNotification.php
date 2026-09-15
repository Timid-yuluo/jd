<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

final class ExceptionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Throwable $exception,
        private readonly array $context = [],
    ) {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $exception = $this->exception;
        $file = $exception->getFile();
        $line = $exception->getLine();
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $appName = config('app.name');
        $env = config('app.env');
        $time = now()->toIso8601String();

        $trace = collect($exception->getTrace())
            ->take(15)
            ->map(fn (array $frame): string => sprintf(
                '%s:%d%s',
                $frame['file'] ?? 'unknown',
                $frame['line'] ?? 0,
                isset($frame['function']) ? " ({$frame['function']})" : '',
            ))
            ->join("\n");

        $previous = null;
        if ($prevException = $exception->getPrevious()) {
            $previous = sprintf(
                "%s: %s\n  at %s:%d",
                get_class($prevException),
                $prevException->getMessage(),
                $prevException->getFile(),
                $prevException->getLine()
            );
        }

        $mail = (new MailMessage)
            ->subject("[{$appName}] 生产环境异常告警 - {$message}")
            ->greeting('运维团队，您好')
            ->line("**应用**: {$appName} ({$env})")
            ->line("**时间**: {$time}")
            ->line("**异常类型**: ".get_class($exception))
            ->line("**错误代码**: {$code}")
            ->line("**错误消息**: {$message}")
            ->line("**文件位置**: {$file}:{$line}");

        if ($previous !== null) {
            $mail->line("**上游异常**: `{$previous}`");
        }

        if (! empty($this->context)) {
            $mail->line('**上下文信息**:');
            foreach ($this->context as $key => $value) {
                $mail->line("- **{$key}**: {$value}");
            }
        }

        return $mail
            ->line('---')
            ->line('**调用栈（前 15 帧）**:')
            ->line("```{$trace}```");
    }
}
