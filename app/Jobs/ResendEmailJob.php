<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 重发邮件队列任务
 */
class ResendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly EmailLog $emailLog,
    ) {
        // 邮件重发归入 notification 队列，与通知发送共用邮件通道
        $this->onQueue('notification');
    }

    public function handle(): void
    {
        try {
            if ($this->emailLog->status === 'sent') {
                return;
            }

            if (! empty($this->emailLog->content)) {
                Mail::raw('', function ($message) {
                    $message->to($this->emailLog->recipient_email)
                        ->subject($this->emailLog->subject ?? '重发邮件');

                    $message->getSwiftMessage()->setBody(
                        $this->emailLog->content,
                        'text/html'
                    );
                });
            } else {
                Log::warning('ResendEmailJob: 邮件日志无原始内容，跳过', ['email_log_id' => $this->emailLog->id]);

                return;
            }

            $this->emailLog->markAsSent();

            Log::info('邮件重发成功', ['email_log_id' => $this->emailLog->id]);
        } catch (\Exception $e) {
            $this->emailLog->markAsFailed($e->getMessage());

            Log::error('邮件重发失败', [
                'email_log_id' => $this->emailLog->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
